<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Retour du cahier de test : après une demande d'abonnement en libre-service, afficher un message
 * confirmant qu'elle a été transmise (l'accueil, où l'on était renvoyé, ne l'affichait pas).
 */
class SubscriptionRequestConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_leads_to_a_confirmation_page(): void
    {
        Mail::fake();
        $planId = DB::table('subscription_plans')->insertGetId([
            'name' => 'Essentiel', 'slug' => 'essentiel', 'max_students' => 200, 'max_users' => 20, 'max_classes' => 10,
            'monthly_price' => 30000, 'yearly_price' => 300000, 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->post(route('request-account.store'), [
            'school_name' => 'École Les Palmiers', 'school_phone' => '0700000000', 'school_address' => 'Cocody',
            'director_name' => 'Awa Koné', 'director_email' => 'awa@example.test', 'plan_id' => $planId,
        ])->assertRedirect(route('request-account.sent'));

        $this->followingRedirects()->get(route('request-account.sent'))->assertOk();
        $this->withSession(['request_email' => 'awa@example.test'])
            ->get(route('request-account.sent'))
            ->assertSee('Votre demande a bien été transmise')
            ->assertSee('awa@example.test');
        $this->assertSame(1, SubscriptionPlan::count());
    }
}
