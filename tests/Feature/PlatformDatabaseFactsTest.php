<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Security\SecurityTestCase;

/** Database facts: owner only, counts match the data, slow questions are written down by shape only */
class PlatformDatabaseFactsTest extends SecurityTestCase
{
    private function owner(): User
    {
        $owner = User::factory()->create(['school_id' => null, 'status' => 'active']);
        $owner->assignRole('super-admin');

        return $owner;
    }

    public function test_only_the_owner_can_open_it(): void
    {
        $this->actingAs($this->makeUser($this->makeSchool('A'), 'school-admin'))->get('/super-admin/database')->assertForbidden();
    }

    public function test_counts_per_table_and_per_school_match_the_data(): void
    {
        $a = $this->makeSchool('Alpha School');
        $b = $this->makeSchool('Beta School');
        foreach (['1', '2', '3'] as $n) {
            SchoolClass::withoutGlobalScopes()->create(['school_id' => $a->id, 'name' => "A$n", 'numeric_name' => (int) $n]);
        }
        SchoolClass::withoutGlobalScopes()->create(['school_id' => $b->id, 'name' => 'B1', 'numeric_name' => 1]);

        $json = $this->actingAs($this->owner())->getJson('/super-admin/database?refresh=1')->assertOk();
        $this->assertGreaterThanOrEqual(3, collect($json->json('schools'))->firstWhere('name', 'Alpha School')['rows']);
        $this->assertSame('Alpha School', $json->json('schools.0.name')); // the biggest school comes first

        $chosen = $this->getJson("/super-admin/database?school={$a->id}")->json('chosen');
        $this->assertSame('Alpha School', $chosen['name']);
        $this->assertSame(3, collect($chosen['tables'])->firstWhere('name', 'classes')['rows']);
        $this->assertNull($this->getJson('/super-admin/database?school=99999')->json('chosen'));
    }

    private function slow(string $sql, array $bindings, float $ms): void
    {
        event(new \Illuminate\Database\Events\QueryExecuted($sql, $bindings, $ms, DB::connection()));
    }

    public function test_a_slow_question_is_kept_by_shape_without_its_values_and_fast_ones_are_not(): void
    {
        config(['platform_tools.slow_query_ms' => 500]);
        $this->slow('select * from students where email = ? and id in (?, ?, ?)', ['secret@person.test', 1, 2, 3], 900);
        $this->slow('select * from students where email = ? and id in (?, ?, ?, ?, ?)', ['secret@person.test', 1, 2, 3, 4, 5], 700);
        $this->slow('select 1', [], 20);

        $rows = DB::table('slow_queries')->get();
        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]->hash, $rows[1]->hash, 'lists of different lengths are the same question');
        $this->assertStringContainsString('(?...)', $rows[0]->sql);

        $body = $this->actingAs($this->owner())->getJson('/super-admin/database')->assertOk()->getContent();
        $this->assertStringNotContainsString('secret@person.test', $body);
        $slow = json_decode($body, true)['slow'];
        $this->assertCount(1, $slow);
        $this->assertSame(2, $slow[0]['times']);
        $this->assertSame(900, $slow[0]['worst_ms']);
    }

    public function test_recording_can_be_switched_off_with_a_zero_limit(): void
    {
        config(['platform_tools.slow_query_ms' => 0]);
        $this->slow('select * from students', [], 9000);
        $this->assertSame(0, DB::table('slow_queries')->count());
    }
}
