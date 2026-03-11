<?php

namespace Tests\Feature;

use App\Enums\StatoPreventivo;
use App\Enums\TipoRigaPreventivo;
use App\Enums\UnitaMisura;
use App\Models\Costruzione;
use App\Models\LottoProduzione;
use App\Models\Preventivo;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventivoPdfControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_pdf_shows_lavorazioni_extra_total(): void
    {
        $prodotto = Prodotto::factory()->create();

        $preventivo = Preventivo::factory()->create([
            'created_by' => $this->user->id,
            'stato' => StatoPreventivo::BOZZA,
            'totale_materiali' => 100,
            'totale_lavorazioni' => 35,
            'totale' => 135,
        ]);

        $preventivo->righe()->create([
            'tipo_riga' => TipoRigaPreventivo::SFUSO->value,
            'include_in_bom' => true,
            'prodotto_id' => $prodotto->id,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => 'Riga test PDF',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita' => 1,
            'superficie_mq' => 0.1,
            'volume_mc' => 0.002,
            'materiale_netto' => 0.002,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 0.0022,
            'prezzo_unitario' => 100,
            'totale_riga' => 100,
            'ordine' => 0,
        ]);

        $html = $this->renderedPdfHtml($preventivo);

        $this->assertStringContainsString('Lavorazioni extra:', $html);
        $this->assertStringContainsString('€ 35,00', $html);
    }

    public function test_pdf_hides_lavorazioni_extra_total_when_zero(): void
    {
        $prodotto = Prodotto::factory()->create();

        $preventivo = Preventivo::factory()->create([
            'created_by' => $this->user->id,
            'stato' => StatoPreventivo::BOZZA,
            'totale_materiali' => 100,
            'totale_lavorazioni' => 0,
            'totale' => 100,
        ]);

        $preventivo->righe()->create([
            'tipo_riga' => TipoRigaPreventivo::SFUSO->value,
            'include_in_bom' => true,
            'prodotto_id' => $prodotto->id,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => 'Riga senza extra',
            'lunghezza_mm' => 1000,
            'larghezza_mm' => 100,
            'spessore_mm' => 20,
            'quantita' => 1,
            'superficie_mq' => 0.1,
            'volume_mc' => 0.002,
            'materiale_netto' => 0.002,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 0.0022,
            'prezzo_unitario' => 100,
            'totale_riga' => 100,
            'ordine' => 0,
        ]);

        $html = $this->renderedPdfHtml($preventivo);

        $this->assertStringNotContainsString('Lavorazioni extra:', $html);
    }

    public function test_pdf_shows_lotto_dimensions_for_linked_rows(): void
    {
        $preventivo = Preventivo::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'created_by' => $this->user->id,
            'larghezza_cm' => 90,
            'profondita_cm' => 80,
            'altezza_cm' => 70,
        ]);

        $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => TipoRigaPreventivo::LOTTO->value,
            'include_in_bom' => true,
            'prodotto_id' => null,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => 'Riga lotto PDF',
            'lunghezza_mm' => 0,
            'larghezza_mm' => 0,
            'spessore_mm' => 0,
            'quantita' => 1,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 0,
            'prezzo_unitario' => 0,
            'totale_riga' => 0,
            'ordine' => 0,
        ]);

        $html = $this->renderedPdfHtml($preventivo);

        $this->assertStringContainsString('900 x', $html);
        $this->assertStringContainsString('800 x', $html);
        $this->assertStringContainsString('700', $html);
    }

    public function test_pdf_shows_lotto_weight_when_enabled_on_construction(): void
    {
        $costruzione = Costruzione::factory()->create([
            'config' => [
                'show_weight_in_quote' => true,
            ],
        ]);

        $preventivo = Preventivo::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $lotto = LottoProduzione::factory()->bozza()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
            'costruzione_id' => $costruzione->id,
            'created_by' => $this->user->id,
            'peso_totale_kg' => 254.8,
        ]);

        $preventivo->righe()->create([
            'lotto_produzione_id' => $lotto->id,
            'tipo_riga' => TipoRigaPreventivo::LOTTO->value,
            'include_in_bom' => true,
            'prodotto_id' => null,
            'unita_misura' => UnitaMisura::MC->value,
            'descrizione' => 'Riga lotto peso',
            'lunghezza_mm' => 0,
            'larghezza_mm' => 0,
            'spessore_mm' => 0,
            'quantita' => 1,
            'superficie_mq' => 0,
            'volume_mc' => 0,
            'materiale_netto' => 0,
            'coefficiente_scarto' => 0.10,
            'materiale_lordo' => 0,
            'prezzo_unitario' => 0,
            'totale_riga' => 0,
            'ordine' => 0,
        ]);

        $html = $this->renderedPdfHtml($preventivo);

        $this->assertStringContainsString('Peso lotto: 254,80 kg', $html);
    }

    private function renderedPdfHtml(Preventivo $preventivo): string
    {
        $preventivo->load(['cliente', 'righe.prodotto', 'righe.lottoProduzione.costruzione', 'createdBy']);

        return view('preventivi.pdf', [
            'preventivo' => $preventivo,
        ])->render();
    }
}
