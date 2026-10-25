<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Crypt;
use Tests\Feature\Security\SecurityTestCase;

/** The parent home page sends a parent with fees owed straight to the fees page. */
class ParentDashboardPayTest extends SecurityTestCase
{
    public function test_dashboard_tells_the_page_whether_online_payment_is_on(): void
    {
        $school = $this->makeSchool('Pay School');
        $parent = $this->makeUser($school, 'parent');
        Guardian::create(['school_id' => $school->id, 'user_id' => $parent->id, 'name' => 'Mrs Test', 'phone' => '08030000000']);

        $this->actingAs($parent)->get('/school/parent/dashboard')->assertOk()
            ->assertInertia(fn ($page) => $page->where('payOnline', false));

        SchoolSetting::set($school->id, 'payment_provider', 'paystack');
        SchoolSetting::set($school->id, 'payment_enabled', '1');
        SchoolSetting::set($school->id, 'payment_keys_paystack', Crypt::encryptString(json_encode(['secret_key' => 'sk_test_x', 'public_key' => 'pk_test_x'])));

        $this->actingAs($parent)->get('/school/parent/dashboard')->assertOk()
            ->assertInertia(fn ($page) => $page->where('payOnline', true));
    }
}
