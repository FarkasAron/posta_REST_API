<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\County;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CountiesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_sums_population_and_builds_badge_url(): void
    {
        $pest = County::create(['name' => 'Pest', 'badge' => 'images/badges/pest.jpg']);
        County::create(['name' => 'Heves', 'badge' => 'https://example.com/heves.png']);
        City::create(['name' => 'Vác', 'zip_code' => 2600, 'population' => 33000, 'id_county' => $pest->id]);
        City::create(['name' => 'Gödöllő', 'zip_code' => 2100, 'population' => 32000, 'id_county' => $pest->id]);

        $response = $this->getJson('/api/counties')->assertOk()->assertJsonCount(2, 'data');

        $response->assertJsonPath('data.0.name', 'Heves')
            ->assertJsonPath('data.0.population', 0)
            ->assertJsonPath('data.0.badge_url', 'https://example.com/heves.png')
            ->assertJsonPath('data.1.population', 65000);

        $this->assertStringEndsWith('/images/badges/pest.jpg', $response->json('data.1.badge_url'));
    }

    public function test_search_filters_by_name(): void
    {
        County::create(['name' => 'Pest']);
        County::create(['name' => 'Heves']);

        $this->getJson('/api/counties?search=hev')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Heves');
    }

    public function test_show_and_not_found(): void
    {
        $county = County::create(['name' => 'Pest']);

        $this->getJson('/api/counties/'.$county->id)->assertOk()->assertJsonPath('data.name', 'Pest');
        $this->getJson('/api/counties/999999')->assertNotFound();
    }

    public function test_writes_require_a_token(): void
    {
        $county = County::create(['name' => 'Pest']);

        $this->postJson('/api/counties', ['name' => 'Teszt megye'])->assertUnauthorized();
        $this->patchJson('/api/counties/'.$county->id, ['name' => 'Másik'])->assertUnauthorized();
        $this->deleteJson('/api/counties/'.$county->id)->assertUnauthorized();
    }

    public function test_store_update_and_delete(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/counties', ['name' => 'Teszt megye'])->assertCreated()->json('data.id');
        $this->patchJson('/api/counties/'.$id, ['name' => 'Új név'])->assertOk()->assertJsonPath('data.name', 'Új név');
        $this->postJson('/api/counties', ['name' => 'ab'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->deleteJson('/api/counties/'.$id)->assertOk();
        $this->assertDatabaseMissing('counties', ['id' => $id]);
    }

    public function test_county_with_cities_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $county = County::create(['name' => 'Pest']);
        City::create(['name' => 'Vác', 'zip_code' => 2600, 'population' => 33000, 'id_county' => $county->id]);

        $this->deleteJson('/api/counties/'.$county->id)->assertStatus(409);
        $this->assertDatabaseHas('counties', ['id' => $county->id]);
    }
}
