<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Bom;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_is_redirected_to_lotti_after_login(): void
    {
        $operator = User::factory()->create([
            'role' => UserRole::OPERATORE,
            'email' => 'operaio@example.test',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'email' => $operator->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('lotti.index'));
    }

    public function test_operator_can_access_only_operational_pages(): void
    {
        $operator = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lottoProduzione = LottoProduzione::factory()->create();
        $lottoMateriale = LottoMateriale::factory()->create();
        $bom = Bom::factory()->create();

        $this->actingAs($operator)->get(route('magazzino.index'))->assertOk();
        $this->actingAs($operator)->get(route('magazzino.aggregato'))->assertOk();
        $this->actingAs($operator)->get(route('magazzino.movimenti', $lottoMateriale))->assertOk();
        $this->actingAs($operator)->get(route('lotti.index'))->assertOk();
        $this->actingAs($operator)->get(route('lotti.show', $lottoProduzione))->assertOk();
        $this->actingAs($operator)->get(route('bom.index'))->assertOk();
        $this->actingAs($operator)->get(route('bom.show', $bom))->assertOk();
    }

    public function test_operator_cannot_access_admin_pages(): void
    {
        $operator = User::factory()->create(['role' => UserRole::OPERATORE]);
        $lottoProduzione = LottoProduzione::factory()->create();
        $bom = Bom::factory()->create();

        $forbiddenRoutes = [
            route('dashboard'),
            route('magazzino.carico'),
            route('magazzino.scarico'),
            route('fitok.index'),
            route('lotti.create'),
            route('lotti.edit', $lottoProduzione),
            route('preventivi.index'),
            route('clienti.index'),
            route('fornitori.index'),
            route('prodotti.index'),
            route('costruzioni.index'),
            route('ordini.index'),
            route('bom.create'),
            route('bom.edit', $bom),
            route('settings.production'),
            route('istruzioni'),
        ];

        foreach ($forbiddenRoutes as $url) {
            $this->actingAs($operator)->get($url)->assertForbidden();
        }
    }
}
