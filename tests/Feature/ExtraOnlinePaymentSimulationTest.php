<?php

namespace Tests\Feature;

use App\Models\ExtraOnlinePayment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Régression : la page de simulation CinetPay (mode démo) restait accessible quand le mode démo
 * était coupé, si bien qu'un parent pouvait lancer un vrai paiement en ligne puis le déclarer
 * « réussi » lui-même, et l'abonnement était marqué payé sans aucun encaissement.
 */
class ExtraOnlinePaymentSimulationTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingPayment(): array
    {
        $school = School::create(['name' => 'École A', 'slug' => 'a', 'status' => 'active']);

        $parent = new User(['first_name' => 'Parent', 'last_name' => 'A', 'email' => 'parent@example.test', 'password' => bcrypt('password')]);
        $parent->role = 'parent';
        $parent->school_id = $school->id;
        $parent->save();

        $student = new Student(['first_name' => 'Eleve', 'last_name' => 'A', 'birth_date' => '2018-01-01', 'gender' => 'M']);
        $student->school_id = $school->id;
        $student->save();
        $parent->children()->attach($student->id, ['school_id' => $school->id]);

        $yearId = DB::table('school_years')->insertGetId(['school_id' => $school->id, 'name' => '2026-2027', 'start_date' => '2026-09-01', 'end_date' => '2027-07-01', 'is_active' => true]);
        $categoryId = DB::table('extra_categories')->insertGetId(['school_id' => $school->id, 'name' => 'Activités']);
        $extraId = DB::table('extras')->insertGetId(['school_id' => $school->id, 'extra_category_id' => $categoryId, 'code' => 'DANSE', 'name' => 'Danse']);
        $tarifId = DB::table('extra_tarifs')->insertGetId(['extra_id' => $extraId, 'school_year_id' => $yearId, 'amount' => 20000]);
        $subscriptionId = DB::table('extra_subscriptions')->insertGetId([
            'school_id' => $school->id, 'student_id' => $student->id, 'extra_id' => $extraId, 'extra_tarif_id' => $tarifId,
            'school_year_id' => $yearId, 'total_amount' => 20000, 'remaining_amount' => 20000,
        ]);

        $payment = ExtraOnlinePayment::create([
            'school_id' => $school->id, 'extra_subscription_id' => $subscriptionId,
            'transaction_id' => 'TX-TEST-1', 'amount' => 20000, 'status' => 'pending',
        ]);

        return [$parent, $student, $payment];
    }

    public function test_simulation_is_refused_outside_dev_mode(): void
    {
        config(['services.cinetpay.dev_mode' => false]);
        [$parent, $student, $payment] = $this->makePendingPayment();
        $params = ['student' => $student->id, 'transaction' => $payment->transaction_id];

        $this->actingAs($parent)->get(route('parent.extras.pay-online.simulate', $params))->assertNotFound();
        $this->actingAs($parent)
            ->post(route('parent.extras.pay-online.simulate.confirm', $params), ['decision' => 'success'])
            ->assertNotFound();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('extra_payments', 0);
    }

    public function test_simulation_still_works_in_dev_mode(): void
    {
        config(['services.cinetpay.dev_mode' => true]);
        [$parent, $student, $payment] = $this->makePendingPayment();

        $this->actingAs($parent)
            ->post(route('parent.extras.pay-online.simulate.confirm', ['student' => $student->id, 'transaction' => $payment->transaction_id]), ['decision' => 'success'])
            ->assertRedirect(route('parent.extras.index', $student->id));

        $this->assertSame('completed', $payment->fresh()->status);
    }
}
