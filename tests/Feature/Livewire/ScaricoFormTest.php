<?php

namespace Tests\Feature\Livewire;

use App\Enums\TipoMovimento;
use App\Livewire\Forms\ScaricoForm;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScaricoFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LottoMateriale $lotto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
        $this->lotto = LottoMateriale::factory()->create();

        // Carica 100 unita
        app(InventoryService::class)->carico($this->lotto, 100, null, $this->user);
    }

    public function test_scarico_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/magazzino/scarico');

        $response->assertStatus(200);
        $response->assertSeeLivewire(ScaricoForm::class);
    }

    public function test_can_create_scarico(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScaricoForm::class)
            ->set('lotto_id', $this->lotto->id)
            ->set('quantita', '30')
            ->set('causale', 'Test scarico')
            ->call('save')
            ->assertRedirect('/magazzino');

        $this->assertEquals(70, app(InventoryService::class)->calcolaGiacenza($this->lotto));
    }

    public function test_cannot_scarico_more_than_available(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScaricoForm::class)
            ->set('lotto_id', $this->lotto->id)
            ->set('quantita', '150')
            ->set('causale', 'Test scarico eccessivo')
            ->call('save')
            ->assertHasErrors(['quantita']);
    }

    public function test_lotto_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScaricoForm::class)
            ->set('lotto_id', '')
            ->set('quantita', '10')
            ->set('causale', 'Test')
            ->call('save')
            ->assertHasErrors(['lotto_id' => 'required']);
    }

    public function test_causale_is_required(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScaricoForm::class)
            ->set('lotto_id', $this->lotto->id)
            ->set('quantita', '10')
            ->set('causale', '')
            ->call('save')
            ->assertHasErrors(['causale' => 'required']);
    }

    public function test_set_full_quantity(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScaricoForm::class)
            ->set('lotto_id', $this->lotto->id)
            ->call('setFullQuantity')
            ->assertSet('quantita', '100');
    }

    public function test_preselects_lotto_from_query_string(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/magazzino/scarico?lotto=' . $this->lotto->id);

        $response->assertStatus(200);
    }

    public function test_quantita_label_includes_selected_lotto_unit(): void
    {
        $lottoMc = LottoMateriale::factory()->create([
            'prodotto_id' => Prodotto::factory()->create([
                'unita_misura' => 'mc',
            ])->id,
        ]);

        app(InventoryService::class)->carico($lottoMc, 10, null, $this->user);

        Livewire::actingAs($this->user)
            ->test(ScaricoForm::class)
            ->set('lotto_id', $lottoMc->id)
            ->assertSee('Quantita da scaricare (m³) *');
    }

    public function test_rettifica_negativa_requires_reason_code(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScaricoForm::class)
            ->set('lotto_id', $this->lotto->id)
            ->set('tipo_movimento', 'rettifica_negativa')
            ->set('quantita', '10')
            ->set('causale', 'Rettifica per controllo inventario')
            ->set('causale_codice', '')
            ->call('save')
            ->assertHasErrors(['causale_codice' => 'required']);
    }

    public function test_rettifica_negativa_with_reason_code_is_registered(): void
    {
        Livewire::actingAs($this->user)
            ->test(ScaricoForm::class)
            ->set('lotto_id', $this->lotto->id)
            ->set('tipo_movimento', 'rettifica_negativa')
            ->set('quantita', '12')
            ->set('causale', 'Rettifica da conta fisica')
            ->set('causale_codice', 'errore_conteggio')
            ->call('save')
            ->assertRedirect('/magazzino');

        $movimento = MovimentoMagazzino::query()
            ->where('lotto_materiale_id', $this->lotto->id)
            ->where('tipo', TipoMovimento::RETTIFICA_NEGATIVA)
            ->latest('id')
            ->first();

        $this->assertNotNull($movimento);
        $this->assertSame('errore_conteggio', $movimento->causale_codice);
    }
}
