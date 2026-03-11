<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Ordine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomShowViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_bom_from_order_hides_version_field(): void
    {
        $user = User::factory()->create();
        $ordine = Ordine::factory()->create([
            'created_by' => $user->id,
        ]);

        $bom = Bom::factory()->generatedFromOrder()->create([
            'ordine_id' => $ordine->id,
            'created_by' => $user->id,
            'versione' => '1.0',
        ]);

        $this->actingAs($user)
            ->get(route('bom.show', $bom))
            ->assertOk()
            ->assertDontSee('Versione');
    }

    public function test_template_bom_shows_version_field(): void
    {
        $user = User::factory()->create();
        $bom = Bom::factory()->create([
            'source' => 'template',
            'created_by' => $user->id,
            'versione' => '2.3',
        ]);

        $this->actingAs($user)
            ->get(route('bom.show', $bom))
            ->assertOk()
            ->assertSee('Versione')
            ->assertSee('2.3');
    }
}

