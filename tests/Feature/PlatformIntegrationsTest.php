<?php

namespace Tests\Feature;

use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Security\SecurityTestCase;

/** The integrations board and the environment viewer: owner only, honest about state, secrets never shown */
class PlatformIntegrationsTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_only_the_owner_sees_them(): void
    {
        $this->actingAs($this->makeUser($this->makeSchool('A'), 'school-admin'));
        $this->get('/super-admin/integrations')->assertForbidden();
        $this->get('/super-admin/environment')->assertForbidden();
        $this->post('/super-admin/integrations/gemini/test')->assertForbidden();
    }

    public function test_the_board_lists_every_service_and_counts_schools_per_payment_provider(): void
    {
        $school = $this->makeSchool('A');
        SchoolSetting::create(['school_id' => $school->id, 'key' => 'payment_provider', 'value' => 'paystack']);
        SchoolSetting::create(['school_id' => $school->id, 'key' => 'payment_enabled', 'value' => '1']);

        $rows = collect($this->actingAs($this->owner())->getJson('/super-admin/integrations')->assertOk()->json('integrations'));
        $this->assertCount(12, $rows);
        $this->assertSame('ready', $rows->firstWhere('key', 'pay-paystack')['state']);
        $this->assertSame('missing', $rows->firstWhere('key', 'pay-monnify')['state']);
        $this->assertSame('missing', $rows->firstWhere('key', 'sms')['state']);
        $this->assertFalse($rows->firstWhere('key', 'sms')['testable']);
    }

    public function test_a_passing_and_a_failing_test_are_remembered_with_the_last_success(): void
    {
        $this->actingAs($this->owner());

        Http::fake(['api.paystack.co*' => Http::sequence()->push('', 200)->push('', 503)]);
        $this->post('/super-admin/integrations/pay-paystack/test')->assertSessionHas('success');
        $this->post('/super-admin/integrations/pay-paystack/test')->assertSessionHas('error');

        $row = collect($this->getJson('/super-admin/integrations')->json('integrations'))->firstWhere('key', 'pay-paystack');
        $this->assertFalse($row['last_result']);
        $this->assertNotNull($row['last_ok']);
        $this->assertNotNull(Activity::where('log_name', 'platform')->where('description', 'tested connection: pay-paystack')->first());
    }

    public function test_an_unknown_service_is_404_and_a_network_failure_is_reported_not_thrown(): void
    {
        $this->actingAs($this->owner());
        $this->post('/super-admin/integrations/nonsense/test')->assertNotFound();

        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 6 secret-host'));
        $this->post('/super-admin/integrations/pay-remita/test')->assertSessionHas('error');
    }

    public function test_the_environment_never_shows_a_secret_in_full(): void
    {
        config(['services.supabase.service_role_key' => 'SUPERSECRETSERVICEKEY-9f8e7d6c', 'services.supabase.url' => 'https://your-project.supabase.co', 'database.connections.mysql.password' => 'abc']);
        $json = $this->actingAs($this->owner())->getJson('/super-admin/environment')->assertOk();
        $body = $json->getContent();

        $this->assertStringNotContainsString('SUPERSECRETSERVICEKEY', $body);
        $this->assertStringContainsString('ends in 7d6c', $body);
        $this->assertStringNotContainsString((string) config('app.key'), $body);

        $rows = collect($json->json('groups'))->flatMap(fn ($g) => $g['rows']);
        $this->assertSame('example', $rows->firstWhere('env', 'SUPABASE_URL')['state']);
        // a very short secret shows nothing of itself
        $this->assertSame('•••• set', $rows->firstWhere('env', 'DB_PASSWORD')['value']);
    }
}
