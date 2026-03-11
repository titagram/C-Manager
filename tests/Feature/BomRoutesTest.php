<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_bom_index_route_exists(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('bom.index'));

        $response->assertStatus(200);
    }

    public function test_bom_create_route_exists(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('bom.create'));

        $response->assertStatus(200);
    }

    public function test_bom_show_route_exists(): void
    {
        $user = User::factory()->admin()->create();
        $bom = Bom::factory()->create();

        $response = $this->actingAs($user)->get(route('bom.show', $bom));

        $response->assertStatus(200);
    }

    public function test_bom_edit_route_exists(): void
    {
        $user = User::factory()->admin()->create();
        $bom = Bom::factory()->create();

        $response = $this->actingAs($user)->get(route('bom.edit', $bom));

        $response->assertStatus(200);
    }
}
