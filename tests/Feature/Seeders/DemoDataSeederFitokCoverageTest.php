<?php

namespace Tests\Feature\Seeders;

use App\Enums\TipoMovimento;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Services\FitokReportService;
use Database\Seeders\CostruzioniSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\FornitoriSeeder;
use Database\Seeders\ProdottiSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederFitokCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seed_creates_fitok_carichi_for_each_seeded_pack(): void
    {
        $this->seedBaseData();

        $codiciAttesi = ['303/25', '389/25', '196/25', '508/25'];
        $lottiByCode = LottoMateriale::query()
            ->whereIn('codice_lotto', $codiciAttesi)
            ->get()
            ->keyBy('codice_lotto');

        $this->assertCount(count($codiciAttesi), $lottiByCode);

        foreach ($codiciAttesi as $codice) {
            $lotto = $lottiByCode->get($codice);
            $this->assertNotNull($lotto, "Lotto materiale {$codice} non trovato nei seed demo.");
            $this->assertEqualsWithDelta(360.0, (float) ($lotto->prodotto?->peso_specifico_kg_mc ?? 0), 0.0001);
            $this->assertDatabaseHas('movimenti_magazzino', [
                'lotto_materiale_id' => $lotto->id,
                'tipo' => TipoMovimento::CARICO->value,
            ]);
        }
    }

    public function test_demo_data_seed_exposes_fitok_movements_in_current_month_with_destination_traceability(): void
    {
        $this->seedBaseData();

        /** @var FitokReportService $fitokService */
        $fitokService = app(FitokReportService::class);
        $registro = $fitokService->getRegistro(now()->startOfMonth(), now()->endOfMonth());

        $this->assertGreaterThan(0, $registro->count(), 'Nessun movimento FITOK nel mese corrente.');
        $this->assertTrue($registro->contains(fn ($m) => $this->movementType($m->tipo) === TipoMovimento::CARICO->value));
        $this->assertTrue($registro->contains(
            fn ($m) => $this->movementType($m->tipo) === TipoMovimento::SCARICO->value
                && !empty($m->lotto_produzione_id)
        ));

        $mappaDestinazioni = $fitokService->getFitokDestinationMap(now()->startOfMonth(), now()->endOfMonth());
        $this->assertGreaterThan(0, $mappaDestinazioni->count(), 'Mappa destinazioni FITOK vuota nel mese corrente.');
        $this->assertTrue($mappaDestinazioni->contains(
            fn (array $item) => (string) ($item['lotto_produzione_codice'] ?? '') === 'LP-2026-0002'
        ));
    }

    public function test_demo_data_seed_is_idempotent_for_fitok_movements(): void
    {
        $this->seedBaseData();

        $carichiPrima = MovimentoMagazzino::query()->where('tipo', TipoMovimento::CARICO->value)->count();
        $scarichiPrima = MovimentoMagazzino::query()->where('tipo', TipoMovimento::SCARICO->value)->count();
        $totalePrima = MovimentoMagazzino::query()->count();

        $this->seed([DemoDataSeeder::class]);

        $this->assertSame($carichiPrima, MovimentoMagazzino::query()->where('tipo', TipoMovimento::CARICO->value)->count());
        $this->assertSame($scarichiPrima, MovimentoMagazzino::query()->where('tipo', TipoMovimento::SCARICO->value)->count());
        $this->assertSame($totalePrima, MovimentoMagazzino::query()->count());
    }

    private function seedBaseData(): void
    {
        $this->seed([
            UserSeeder::class,
            ProdottiSeeder::class,
            FornitoriSeeder::class,
            CostruzioniSeeder::class,
            DemoDataSeeder::class,
        ]);
    }

    private function movementType(mixed $tipo): string
    {
        if ($tipo instanceof TipoMovimento) {
            return $tipo->value;
        }

        return strtolower(trim((string) $tipo));
    }
}
