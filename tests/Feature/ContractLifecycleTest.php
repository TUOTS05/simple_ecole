<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Retours du cahier de test (contrats) : un contrat signé ne se modifie plus ; une seule école ne peut
 * avoir qu'un contrat en cours ; les dates sont calculées ; un changement passe par un avenant.
 */
class ContractLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private School $school;

    private SubscriptionPlan $essentiel;

    private SubscriptionPlan $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = new User(['first_name' => 'Super', 'last_name' => 'Admin', 'email' => 'super@example.test', 'password' => bcrypt('password')]);
        $this->superAdmin->role = 'super_admin';
        $this->superAdmin->save();

        $this->school = School::create(['name' => 'École Les Palmiers', 'slug' => 'palmiers', 'status' => 'suspended', 'trial_ends_at' => now()->addDays(10)]);
        $this->essentiel = $this->plan('Essentiel', 300000, 200);
        $this->pro = $this->plan('Pro', 500000, 500);
    }

    private function plan(string $name, int $yearly, int $students): SubscriptionPlan
    {
        $id = DB::table('subscription_plans')->insertGetId([
            'name' => $name, 'slug' => strtolower($name), 'max_students' => $students, 'max_users' => 20, 'max_classes' => 10,
            'monthly_price' => intdiv($yearly, 10), 'yearly_price' => $yearly, 'is_active' => true, 'sort_order' => 1,
        ]);

        return SubscriptionPlan::find($id);
    }

    private function createContract(string $start = '2026-11-10')
    {
        return $this->actingAs($this->superAdmin)->post(route('superadmin.subscriptions.store'), [
            'school_id' => $this->school->id, 'plan_id' => $this->essentiel->id, 'start_date' => $start, 'amount' => 300000,
            'end_date' => '2030-01-01', // ignorée : la fin est calculée
        ]);
    }

    public function test_contract_end_date_is_computed_and_school_leaves_trial(): void
    {
        $this->createContract()->assertSessionHasNoErrors();

        $contract = Contract::firstOrFail();
        $this->assertSame('2027-11-09', $contract->end_date->format('Y-m-d'));
        $this->assertSame('contrat', $contract->type);

        $this->school->refresh();
        $this->assertSame('active', $this->school->status);
        $this->assertFalse($this->school->isTrialActive());
    }

    public function test_a_second_contract_is_refused_while_one_is_running(): void
    {
        $this->createContract()->assertSessionHasNoErrors();
        $this->createContract('2026-12-01')->assertSessionHasErrors('school_id');

        $this->assertSame(1, Contract::count());
    }

    public function test_renewal_starts_the_day_after_and_ignores_submitted_dates(): void
    {
        $this->createContract();
        $contract = Contract::firstOrFail();

        $this->actingAs($this->superAdmin)->post(route('superadmin.subscriptions.store-renewal', $contract), [
            'amount' => 320000, 'start_date' => '2026-12-01', 'end_date' => '2035-01-01',
        ])->assertSessionHasNoErrors();

        $renewal = Contract::where('type', 'renouvellement')->firstOrFail();
        $this->assertSame('2027-11-10', $renewal->start_date->format('Y-m-d'));
        $this->assertSame('2028-11-09', $renewal->end_date->format('Y-m-d'));
        $this->assertSame($contract->id, $renewal->parent_contract_id);
        $this->assertSame('renewed', $contract->fresh()->status);
    }

    public function test_amendment_closes_the_contract_and_keeps_the_original_end(): void
    {
        $this->createContract();
        $contract = Contract::firstOrFail();

        $this->actingAs($this->superAdmin)->post(route('superadmin.subscriptions.store-amendment', $contract), [
            'plan_id' => $this->pro->id, 'effective_date' => '2027-03-01', 'amount' => 250000, 'reason' => "Ouverture d'une classe",
        ])->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame('amended', $contract->status);
        $this->assertSame('2027-02-28', $contract->end_date->format('Y-m-d'));

        $amendment = Contract::where('type', 'avenant')->firstOrFail();
        $this->assertSame('2027-03-01', $amendment->start_date->format('Y-m-d'));
        $this->assertSame('2027-11-09', $amendment->end_date->format('Y-m-d'));
        $this->assertSame('Pro', $amendment->plan_name);
        $this->assertSame(500, $this->school->fresh()->max_students);
    }

    public function test_school_edit_no_longer_changes_the_subscription(): void
    {
        $this->createContract();

        $this->actingAs($this->superAdmin)->put(route('superadmin.schools.update', $this->school), [
            'name' => 'École Les Palmiers', 'slug' => 'palmiers', 'school_type' => 'primaire', 'status' => 'active',
            'subscription_plan' => 'enterprise', 'subscription_end_date' => '2040-01-01', 'max_students' => 99999,
        ])->assertSessionHasNoErrors();

        $this->school->refresh();
        $this->assertSame('Essentiel', $this->school->subscription_plan);
        $this->assertSame('2027-11-09', $this->school->subscription_end_date->format('Y-m-d'));
        $this->assertSame(200, $this->school->max_students);
    }

    public function test_contract_pdf_is_viewable(): void
    {
        $this->createContract();

        $this->actingAs($this->superAdmin)->get(route('superadmin.subscriptions.contract-pdf', Contract::firstOrFail()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
