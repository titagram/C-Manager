<?php

namespace Tests\Feature\Livewire;

use App\Enums\TipoMovimento;
use App\Livewire\MagazzinoAggregato;
use App\Models\Bom;
use App\Models\Cliente;
use App\Models\ConsumoMateriale;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Ordine;
use App\Models\Prodotto;
use App\Models\Scarto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MagazzinoAggregatoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_magazzino_aggregato_page_contains_livewire_component(): void
    {
        $response = $this->actingAs($this->user)->get('/magazzino/aggregato');

        $response->assertStatus(200);
        $response->assertSeeLivewire(MagazzinoAggregato::class);
    }

    public function test_filtro_giacenza_positiva_hides_products_with_zero_stock(): void
    {
        $prodottoConGiacenza = Prodotto::factory()->create([
            'nome' => 'Abete Attivo',
        ]);
        $prodottoSenzaGiacenza = Prodotto::factory()->create([
            'nome' => 'Abete Esaurito',
        ]);

        $lottoConGiacenza = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoConGiacenza->id,
        ]);
        $lottoSenzaGiacenza = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoSenzaGiacenza->id,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoConGiacenza->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 10,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoSenzaGiacenza->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 5,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoSenzaGiacenza->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 5,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->set('filtroGiacenza', 'positiva')
            ->assertSee('Abete Attivo')
            ->assertDontSee('Abete Esaurito');
    }

    public function test_defaults_to_positive_stock_filter_on_first_load(): void
    {
        $prodottoConGiacenza = Prodotto::factory()->create([
            'nome' => 'Materiale Disponibile',
        ]);
        $prodottoSenzaGiacenza = Prodotto::factory()->create([
            'nome' => 'Materiale Non Disponibile',
        ]);

        $lottoConGiacenza = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoConGiacenza->id,
        ]);
        LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoSenzaGiacenza->id,
            'quantita_iniziale' => 0,
        ]);

        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoConGiacenza->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 4,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSet('filtroGiacenza', 'positiva')
            ->assertSee('Materiale Disponibile')
            ->assertDontSee('Materiale Non Disponibile');
    }

    public function test_displays_separate_fitok_kpi_for_stock_and_production(): void
    {
        $prodottoFitok = Prodotto::factory()->create([
            'nome' => 'Abete FITOK',
            'soggetto_fitok' => true,
        ]);

        $lottoCertificato = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoFitok->id,
            'fitok_certificato' => 'CERT-001',
        ]);
        $lottoNonCertificato = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoFitok->id,
            'fitok_certificato' => null,
        ]);

        // Stock KPI: 6 certificato + 4 non certificato => 60%.
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoCertificato->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 6,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);
        MovimentoMagazzino::create([
            'lotto_materiale_id' => $lottoNonCertificato->id,
            'tipo' => TipoMovimento::CARICO,
            'quantita' => 4,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        // Production KPI: 3 certificato + 1 non certificato => 75%.
        $lottoProduzione = LottoProduzione::factory()->create([
            'cliente_id' => Cliente::factory()->create()->id,
        ]);
        ConsumoMateriale::create([
            'lotto_produzione_id' => $lottoProduzione->id,
            'lotto_materiale_id' => $lottoCertificato->id,
            'quantita' => 3,
        ]);
        ConsumoMateriale::create([
            'lotto_produzione_id' => $lottoProduzione->id,
            'lotto_materiale_id' => $lottoNonCertificato->id,
            'quantita' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSee('Abete FITOK')
            ->assertSee('Giacenza 60%')
            ->assertSee('Produzione 75%');
    }

    public function test_displays_scrap_totals_split_by_fitok_and_non_fitok(): void
    {
        $prodottoFitok = Prodotto::factory()->create([
            'soggetto_fitok' => true,
        ]);
        $prodottoNonFitok = Prodotto::factory()->create([
            'soggetto_fitok' => false,
        ]);

        $lottoMaterialeFitok = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoFitok->id,
        ]);
        $lottoMaterialeNonFitok = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoNonFitok->id,
        ]);

        Scarto::factory()->riutilizzabile()->create([
            'lotto_materiale_id' => $lottoMaterialeFitok->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 1000,
            'spessore_mm' => 500,
            'volume_mc' => 0.500,
            'riutilizzato' => false,
        ]);

        Scarto::factory()->riutilizzabile()->create([
            'lotto_materiale_id' => $lottoMaterialeNonFitok->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 1000,
            'spessore_mm' => 250,
            'volume_mc' => 0.250,
            'riutilizzato' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSee('Scarti Totali')
            ->assertSee('Scarti FITOK')
            ->assertSee('Scarti Non-FITOK')
            ->assertSee('0,750')
            ->assertSee('0,500')
            ->assertSee('0,250');
    }

    public function test_scarti_tab_uses_dimension_derived_volume_and_weight_when_stored_value_is_stale(): void
    {
        $prodotto = Prodotto::factory()->create([
            'nome' => 'Abete volume corretto',
            'peso_specifico_kg_mc' => 360,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LOT-SCARTO-STALE',
        ]);

        Scarto::factory()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 75,
            'spessore_mm' => 35,
            'volume_mc' => 0.026250,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->set('activeTab', 'scarti')
            ->assertSee('Abete volume corretto')
            ->assertSee('0,003')
            ->assertSee('0,945')
            ->assertDontSee('0,026')
            ->assertDontSee('9,450');
    }

    public function test_displays_clarified_filter_labels_with_tooltips(): void
    {
        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSee('Filtro giacenza')
            ->assertSee('Filtro scarti')
            ->assertSee('Tutti (scarti riutilizzabili)')
            ->assertSee('Con scarti disponibili')
            ->assertSee('Senza scarti disponibili')
            ->assertSee('Mostra solo i prodotti che hanno almeno una quantita disponibile in magazzino (giacenza totale maggiore di zero).')
            ->assertSee('Mostra i prodotti che hanno scarti riutilizzabili non ancora riutilizzati.');
    }

    public function test_displays_operational_tabs_for_aggregated_inventory_sections(): void
    {
        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSet('activeTab', 'giacenze')
            ->assertSee('Giacenze')
            ->assertSee('Opzionato/Consumato')
            ->assertSee('Scarti')
            ->call('setActiveTab', 'opzionato')
            ->assertSet('activeTab', 'opzionato')
            ->call('setActiveTab', 'scarti')
            ->assertSet('activeTab', 'scarti');
    }

    public function test_displays_mobile_responsive_giacenze_container(): void
    {
        Prodotto::factory()->create();

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSee('id="magazzino-giacenze-mobile-list"', false);
    }

    public function test_ignores_invalid_operational_tab_values(): void
    {
        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSet('activeTab', 'giacenze')
            ->call('setActiveTab', 'invalid-tab')
            ->assertSet('activeTab', 'giacenze');
    }

    public function test_displays_scrap_traceability_by_production_lot_and_type(): void
    {
        $prodotto = Prodotto::factory()->create([
            'nome' => 'Abete Traccia',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-TRACE-001',
        ]);

        $lottoOrigine = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-TRACE-001',
            'created_by' => $this->user->id,
        ]);

        $lottoRiutilizzo = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-REUSE-001',
            'created_by' => $this->user->id,
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigine->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 1000,
            'spessore_mm' => 300,
            'volume_mc' => 0.300,
            'riutilizzabile' => true,
            'riutilizzato' => false,
            'note' => 'Scarto disponibile',
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigine->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 1000,
            'spessore_mm' => 200,
            'volume_mc' => 0.200,
            'riutilizzabile' => false,
            'riutilizzato' => false,
            'note' => 'Scarto corto',
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigine->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 1000,
            'spessore_mm' => 150,
            'volume_mc' => 0.150,
            'riutilizzabile' => true,
            'riutilizzato' => true,
            'riutilizzato_in_lotto_id' => $lottoRiutilizzo->id,
            'note' => 'Scarto riutilizzato',
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSee('Tracciabilita scarti per lotto')
            ->assertSee('LP-TRACE-001')
            ->assertSee('LM-TRACE-001')
            ->assertSee('Riutilizzabile disponibile')
            ->assertSee('Non riutilizzabile')
            ->assertSee('Riutilizzato')
            ->assertSee('LP-REUSE-001')
            ->assertSee('0,650');
    }

    public function test_scrap_traceability_respects_active_filters(): void
    {
        $prodottoVisibile = Prodotto::factory()->create([
            'nome' => 'Abete Visibile Traccia',
        ]);
        $prodottoNascosto = Prodotto::factory()->create([
            'nome' => 'Pino Nascosto Traccia',
        ]);

        $lottoMaterialeVisibile = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoVisibile->id,
            'codice_lotto' => 'LM-ABETE-001',
        ]);
        $lottoMaterialeNascosto = LottoMateriale::factory()->create([
            'prodotto_id' => $prodottoNascosto->id,
            'codice_lotto' => 'LM-PINO-001',
        ]);

        $lottoOrigineVisibile = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-ABETE-001',
            'created_by' => $this->user->id,
        ]);
        $lottoOrigineNascosto = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-PINO-001',
            'created_by' => $this->user->id,
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigineVisibile->id,
            'lotto_materiale_id' => $lottoMaterialeVisibile->id,
            'volume_mc' => 0.111,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigineNascosto->id,
            'lotto_materiale_id' => $lottoMaterialeNascosto->id,
            'volume_mc' => 0.222,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->set('search', 'Abete Visibile Traccia')
            ->assertSee('LP-ABETE-001')
            ->assertDontSee('LP-PINO-001')
            ->assertDontSee('LM-PINO-001');
    }

    public function test_displays_clarified_stock_filter_labels(): void
    {
        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSee('Con giacenza (> 0)')
            ->assertSee('Senza giacenza (<= 0)');
    }

    public function test_displays_material_opzionato_and_consumato_traceability(): void
    {
        $prodotto = Prodotto::factory()->create([
            'nome' => 'Abete prenotato',
        ]);

        $lottoMaterialeOpzionato = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-OPZ-001',
        ]);
        $lottoMaterialeConsumato = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-CON-001',
        ]);

        $lottoProduzione = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-OPZ-001',
            'cliente_id' => Cliente::factory()->create()->id,
            'created_by' => $this->user->id,
        ]);

        $ordine = Ordine::factory()->create([
            'cliente_id' => $lottoProduzione->cliente_id,
            'created_by' => $this->user->id,
        ]);
        $lottoProduzione->update(['ordine_id' => $ordine->id]);

        $bom = Bom::factory()->create([
            'lotto_produzione_id' => $lottoProduzione->id,
            'source' => 'lotto',
            'generated_at' => now(),
            'created_by' => $this->user->id,
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lottoProduzione->id,
            'lotto_materiale_id' => $lottoMaterialeOpzionato->id,
            'quantita' => 2,
            'stato' => 'opzionato',
            'opzionato_at' => now(),
            'note' => 'Opzionato test',
        ]);

        ConsumoMateriale::create([
            'lotto_produzione_id' => $lottoProduzione->id,
            'lotto_materiale_id' => $lottoMaterialeConsumato->id,
            'quantita' => 1.5,
            'stato' => 'consumato',
            'consumato_at' => now(),
            'note' => 'Consumato test',
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSee('Materiale opzionato e consumato')
            ->assertSee('Opzionato')
            ->assertSee('Consumato')
            ->assertSee('LM-OPZ-001')
            ->assertSee('LM-CON-001')
            ->assertSee('LP-OPZ-001')
            ->assertSee($bom->codice)
            ->assertSee("Opzionato per ordine {$ordine->numero}")
            ->assertSee("Utilizzato in ordine {$ordine->numero}");
    }

    public function test_displays_scrap_dimensions_piece_count_and_weight_summary(): void
    {
        $prodotto = Prodotto::factory()->create([
            'nome' => 'Abete scarto peso',
            'peso_specifico_kg_mc' => 500,
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-SCRAP-001',
        ]);

        $lottoOrigine = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-SCRAP-001',
            'created_by' => $this->user->id,
        ]);

        Scarto::factory()->create([
            'lotto_produzione_id' => $lottoOrigine->id,
            'lotto_materiale_id' => $lottoMateriale->id,
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 120,
            'spessore_mm' => 20,
            'volume_mc' => 0.2,
            'riutilizzabile' => true,
            'riutilizzato' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(MagazzinoAggregato::class)
            ->assertSee('Pezzi scarto')
            ->assertSee('Peso stimato')
            ->assertSee('Scarti riutilizzabili compatibili (per dimensione)')
            ->assertSee('1.000')
            ->assertSee('120')
            ->assertSee('20')
            ->assertSee('1,200');
    }
}
