<?php

namespace Tests\Feature\Livewire;

use App\Enums\TipoMovimento;
use App\Livewire\Reports\FitokReport;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FitokReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_filter_movements_by_lotto_materiale(): void
    {
        $prodottoA = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-A',
            'nome' => 'Abete A',
        ]);
        $prodottoB = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-B',
            'nome' => 'Abete B',
        ]);

        $lottoMaterialeA = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoA->id,
            'codice_lotto' => 'LM-FILTER-001',
        ]);
        $lottoMaterialeB = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoB->id,
            'codice_lotto' => 'LM-FILTER-002',
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMaterialeA->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1.5,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMaterialeB->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 2.5,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(FitokReport::class)
            ->set('filtroLottoMateriale', 'LM-FILTER-001')
            ->assertSee('LM-FILTER-001')
            ->assertDontSee('PF-B');
    }

    public function test_can_filter_movements_by_lotto_produzione(): void
    {
        $prodottoA = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-PROD-A',
            'nome' => 'Abete Produzione A',
        ]);
        $prodottoB = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-PROD-B',
            'nome' => 'Abete Produzione B',
        ]);

        $lottoMaterialeA = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoA->id,
            'codice_lotto' => 'LM-PROD-001',
        ]);
        $lottoMaterialeB = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoB->id,
            'codice_lotto' => 'LM-PROD-002',
        ]);

        $lottoProduzioneA = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-FILTER-001',
            'created_by' => $this->user->id,
        ]);
        $lottoProduzioneB = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-FILTER-002',
            'created_by' => $this->user->id,
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMaterialeA->id,
            'lotto_produzione_id' => $lottoProduzioneA->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMaterialeB->id,
            'lotto_produzione_id' => $lottoProduzioneB->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(FitokReport::class)
            ->set('filtroLottoProduzione', 'LP-FILTER-001')
            ->assertSee('PF-PROD-A')
            ->assertDontSee('PF-PROD-B');
    }

    public function test_displays_new_traceability_columns(): void
    {
        $prodotto = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-TRACE-COL',
            'nome' => 'Abete Trace Col',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-COL-001',
        ]);

        $lottoProduzione = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-COL-001',
            'fitok_percentuale' => 40,
            'created_by' => $this->user->id,
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lottoProduzione->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(FitokReport::class)
            ->assertSee('Lotto carico')
            ->assertSee('Lotto produzione destinatario')
            ->assertSee('Stato certificazione uscita')
            ->assertSee('LM-COL-001')
            ->assertSee('LP-COL-001')
            ->assertSee('Misto (non certificabile FITOK)');
    }

    public function test_displays_fitok_destination_map(): void
    {
        $prodotto = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-MAP-VIEW',
            'nome' => 'Abete Mappa Vista',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-MAP-VIEW-001',
        ]);

        $lottoProduzione = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-MAP-VIEW-001',
            'fitok_percentuale' => 100,
            'created_by' => $this->user->id,
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lottoProduzione->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1.5,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(FitokReport::class)
            ->assertSee('Mappa destinazioni quota FITOK')
            ->assertSee('LM-MAP-VIEW-001')
            ->assertSee('LP-MAP-VIEW-001')
            ->assertSee('PF-MAP-VIEW')
            ->assertSee('Certificabile FITOK');
    }

    public function test_displays_select_options_for_lotto_filters(): void
    {
        $prodotto = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-SUGG',
            'nome' => 'Abete Suggerimenti',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-SUGG-001',
        ]);

        $lottoProduzione = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-SUGG-001',
            'created_by' => $this->user->id,
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lottoProduzione->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1.5,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(FitokReport::class)
            ->assertSee('Tutti i lotti carico')
            ->assertSee('Tutti i lotti produzione')
            ->assertSee('LM-SUGG-001')
            ->assertSee('LP-SUGG-001');
    }

    public function test_displays_warning_when_period_has_non_certifiable_fitok_lots(): void
    {
        LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-MIX-ALERT-001',
            'stato' => 'completato',
            'fitok_percentuale' => 45,
            'fitok_calcolato_at' => now(),
            'data_fine' => now(),
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(FitokReport::class)
            ->assertSee('lotti non certificabili FITOK')
            ->assertSee('Stato certificazione uscita');
    }

    public function test_displays_mobile_responsive_containers_for_fitok_tables(): void
    {
        $prodotto = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-MOBILE',
            'nome' => 'Abete Mobile',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-MOBILE-001',
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 2,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(FitokReport::class)
            ->assertSee('id="fitok-riepilogo-mobile-list"', false)
            ->assertSee('id="fitok-registro-mobile-list"', false);
    }

    public function test_riepilogo_and_per_prodotto_handle_enum_movement_types_correctly(): void
    {
        $prodotto = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-SALDO',
            'nome' => 'Abete Saldo',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-SALDO-001',
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 3.5,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 1.2,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(FitokReport::class)
            ->assertSee('Saldo Periodo')
            ->assertSee('2,30')
            ->assertSee('+3,50')
            ->assertSee('-1,20')
            ->assertSee('Carico')
            ->assertSee('Scarico');
    }
}
