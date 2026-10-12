<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\County;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitiesApiTest extends TestCase
{
    use RefreshDatabase;

    private County $pest;

    private County $fejer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pest = County::create(['name' => 'Pest']);
        $this->fejer = County::create(['name' => 'Fejér']);

        City::create(['name' => 'Szentendre', 'zip_code' => 2000, 'population' => 25000, 'id_county' => $this->pest->id]);
        City::create(['name' => 'Szigetszentmiklós', 'zip_code' => 2310, 'population' => 37000, 'id_county' => $this->pest->id]);
        City::create(['name' => 'Székesfehérvár', 'zip_code' => 8000, 'population' => 95000, 'id_county' => $this->fejer->id]);
    }

    public function test_index_is_public_and_includes_county(): void
    {
        $this->getJson('/api/cities')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson('/api/cities?search=Székes')->assertJsonPath('data.0.county.name', 'Fejér');
    }

    public function test_search_filters_by_name(): void
    {
        $this->getJson('/api/cities?search=Szent')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_county_filter(): void
    {
        $this->getJson('/api/cities?county='.$this->fejer->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Székesfehérvár');
    }

    public function test_search_and_county_filter_combine(): void
    {
        $this->getJson('/api/cities?search=Sz&county='.$this->pest->id)->assertJsonCount(2, 'data');
        $this->getJson('/api/cities?search=Székes&county='.$this->pest->id)->assertJsonCount(0, 'data');
    }

    public function test_per_page_is_applied_and_capped(): void
    {
        $this->getJson('/api/cities?per_page=2')->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 3);
        $this->getJson('/api/cities?per_page=1000')->assertJsonPath('meta.per_page', 100);
    }

    public function test_show_and_not_found(): void
    {
        $city = City::first();

        $this->getJson('/api/cities/'.$city->id)->assertOk()->assertJsonPath('data.name', $city->name);
        $this->getJson('/api/cities/999999')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_writes_require_a_token(): void
    {
        $payload = ['name' => 'Újváros', 'zip_code' => 1234, 'population' => 10, 'id_county' => $this->pest->id];

        $this->postJson('/api/cities', $payload)->assertUnauthorized();
        $this->putJson('/api/cities/'.City::first()->id, $payload)->assertUnauthorized();
        $this->deleteJson('/api/cities/'.City::first()->id)->assertUnauthorized();
        $this->assertDatabaseCount('cities', 3);
    }

    public function test_store_creates_city(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/cities', ['name' => 'Újváros', 'zip_code' => 1234, 'population' => 10, 'id_county' => $this->pest->id])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Újváros')
            ->assertJsonPath('data.county.name', 'Pest');

        $this->assertDatabaseHas('cities', ['name' => 'Újváros', 'zip_code' => 1234]);
    }

    public function test_store_validates_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/cities', ['name' => 'Új', 'zip_code' => 12, 'population' => -1, 'id_county' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'zip_code', 'population', 'id_county']);
    }

    public function test_patch_updates_only_sent_fields_but_put_requires_all(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $city = City::where('name', 'Szentendre')->first();

        $this->patchJson('/api/cities/'.$city->id, ['population' => 26000])
            ->assertOk()
            ->assertJsonPath('data.population', 26000)
            ->assertJsonPath('data.name', 'Szentendre');

        $this->putJson('/api/cities/'.$city->id, ['population' => 1])->assertUnprocessable();
    }

    public function test_destroy_deletes_city(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $city = City::first();

        $this->deleteJson('/api/cities/'.$city->id)->assertOk();
        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }
}
