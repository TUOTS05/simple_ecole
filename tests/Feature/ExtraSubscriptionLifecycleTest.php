<?php

namespace Tests\Feature;

use App\Models\Extra;
use App\Models\ExtraSubscription;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Régression : un abonnement d'extra entièrement payé passe au statut « completed », mais le
 * service continue. Il était pourtant traité comme terminé : le parent ne pouvait plus suivre
 * le bus, la facturation mensuelle s'arrêtait et sa place était considérée comme libre.
 */
class ExtraSubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makePaidTransportSubscription(): array
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
        $categoryId = DB::table('extra_categories')->insertGetId(['school_id' => $school->id, 'name' => 'Services']);
        $extraId = DB::table('extras')->insertGetId(['school_id' => $school->id, 'extra_category_id' => $categoryId, 'code' => 'BUS', 'name' => 'Transport', 'capacity' => 1, 'billing_type' => 'recurring']);
        $tarifId = DB::table('extra_tarifs')->insertGetId(['extra_id' => $extraId, 'school_year_id' => $yearId, 'amount' => 15000, 'is_open_ended' => true, 'due_day' => 5]);
        $subscriptionId = DB::table('extra_subscriptions')->insertGetId([
            'school_id' => $school->id, 'student_id' => $student->id, 'extra_id' => $extraId, 'extra_tarif_id' => $tarifId,
            'school_year_id' => $yearId, 'total_amount' => 15000, 'paid_amount' => 15000, 'remaining_amount' => 0, 'status' => 'completed',
        ]);

        $vehicleId = DB::table('extra_vehicles')->insertGetId(['school_id' => $school->id, 'plate_number' => 'AB-123', 'capacity' => 20, 'status' => 'active']);
        $routeId = DB::table('extra_routes')->insertGetId(['extra_id' => $extraId, 'name' => 'Circuit Nord', 'extra_vehicle_id' => $vehicleId]);
        DB::table('extra_transport_assignments')->insert(['extra_subscription_id' => $subscriptionId, 'extra_route_id' => $routeId, 'extra_vehicle_id' => $vehicleId]);

        return [$parent, $student, ExtraSubscription::find($subscriptionId)];
    }

    public function test_parent_can_still_track_the_bus_once_everything_is_paid(): void
    {
        [$parent, $student, $subscription] = $this->makePaidTransportSubscription();
        $params = ['student' => $student->id, 'subscriptionId' => $subscription->id];

        $this->actingAs($parent)->get(route('parent.extras.track-bus', $params))->assertOk();
        $this->actingAs($parent)->get(route('parent.extras.track-bus.data', $params))->assertOk();
    }

    public function test_paid_open_ended_subscription_is_billed_again_next_month(): void
    {
        [, , $subscription] = $this->makePaidTransportSubscription();

        $this->artisan('extras:generate-monthly-installments')->assertSuccessful();

        $subscription->refresh();
        $this->assertSame(1, $subscription->installments()->where('period', now()->format('Y-m'))->count());
        $this->assertEquals(15000, $subscription->remaining_amount);
        $this->assertSame('active', $subscription->status);
    }

    public function test_paid_subscription_still_occupies_its_seat(): void
    {
        [, , $subscription] = $this->makePaidTransportSubscription();

        $this->assertFalse(Extra::find($subscription->extra_id)->hasAvailableCapacity());
    }
}
