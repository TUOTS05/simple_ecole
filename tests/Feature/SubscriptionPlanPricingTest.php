<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_public_plans_without_trial(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);

        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        $this->assertSame(['Essentiel', 'Croissance', 'Premium', 'Sur Devis'], $plans->pluck('name')->all());
        $this->assertSame([10000.0, 20000.0, 35000.0, 0.0], $plans->map(fn ($p) => (float) $p->monthly_price)->all());
        $this->assertSame([120000.0, 240000.0, 420000.0, 0.0], $plans->map(fn ($p) => (float) $p->yearly_price)->all());
        $this->assertSame(['essentiel'], $plans->where('is_recommended', true)->pluck('slug')->values()->all());
        $this->assertFalse(SubscriptionPlan::where('name', 'Essai')->exists());

        // Relancer le seeder ne crée pas de doublons
        $this->seed(SubscriptionPlanSeeder::class);
        $this->assertSame(4, SubscriptionPlan::count());
    }

    public function test_landing_page_shows_plans_with_popular_badge_on_recommended_plan(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);

        $html = $this->get(route('landing'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Pour découvrir la plateforme avant de choisir un forfait', $html);
        // Le badge POPULAIRE précède immédiatement le nom "Essentiel", pas "Croissance"
        $this->assertMatchesRegularExpression('/POPULAIRE\s*<\/div>\s*<h3[^>]*>Essentiel<\/h3>/', $html);
        $this->assertSame(1, substr_count($html, '⭐ POPULAIRE'));
        $this->assertStringContainsString('Élèves illimités', $html);
    }

    public function test_request_account_page_shows_unlimited_limits_for_quote_plan(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);

        $this->get(route('request-account'))
            ->assertOk()
            ->assertSee('À discuter')
            ->assertSee('Élèves illimités')
            ->assertSee('Max 100 élèves')
            ->assertDontSee('99999');
    }

    public function test_super_admin_can_toggle_recommended_plan(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);
        $superAdmin = new User(['first_name' => 'Super', 'last_name' => 'Admin', 'email' => 'super@example.test', 'password' => bcrypt('password')]);
        $superAdmin->role = 'super_admin';
        $superAdmin->save();

        $croissance = SubscriptionPlan::where('slug', 'croissance')->first();

        $this->actingAs($superAdmin)->put(route('superadmin.plans.update', $croissance), [
            'name' => $croissance->name,
            'slug' => $croissance->slug,
            'description' => $croissance->description,
            'max_students' => $croissance->max_students,
            'max_users' => $croissance->max_users,
            'max_classes' => $croissance->max_classes,
            'monthly_price' => 20000,
            'sort_order' => 2,
            'is_active' => 1,
            'is_recommended' => 1,
        ])->assertRedirect(route('superadmin.plans.index'));

        $this->assertTrue($croissance->fresh()->is_recommended);
    }
}
