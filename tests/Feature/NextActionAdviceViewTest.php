<?php

namespace Tests\Feature;

use App\Models\LottoProduzione;
use App\Models\Ordine;
use App\Models\Preventivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NextActionAdviceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preventivo_show_displays_next_action_for_conversion_to_order(): void
    {
        $user = User::factory()->admin()->create();
        $preventivo = Preventivo::factory()->accettato()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('preventivi.show', $preventivo->id))
            ->assertOk()
            ->assertSee('Prossima azione consigliata')
            ->assertSee("Crea l'ordine da questo preventivo");
    }

    public function test_ordine_show_displays_next_action_for_first_lotto_planning(): void
    {
        $user = User::factory()->admin()->create();
        $ordine = Ordine::factory()->confermato()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('ordini.show', $ordine))
            ->assertOk()
            ->assertSee('Prossima azione consigliata')
            ->assertSee('Pianifica il primo lotto di produzione');
    }

    public function test_lotto_show_displays_next_action_for_completion_when_in_lavorazione(): void
    {
        $user = User::factory()->admin()->create();
        $lotto = LottoProduzione::factory()->inLavorazione()->create([
            'created_by' => $user->id,
            'preventivo_id' => Preventivo::factory()->create([
                'created_by' => $user->id,
            ])->id,
        ]);

        $this->actingAs($user)
            ->get(route('lotti.show', $lotto->id))
            ->assertOk()
            ->assertSee('Prossima azione consigliata')
            ->assertSee('Completa il lotto')
            ->assertSee('consumi reali');
    }
}
