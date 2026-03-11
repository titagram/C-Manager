<?php

namespace Tests\Feature;

use App\Enums\TipoMovimento;
use App\Models\LottoMateriale;
use App\Models\LottoProduzione;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ZipArchive;
use Tests\TestCase;

class FitokExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
    }

    public function test_export_contains_traceability_columns(): void
    {
        $prodotto = Prodotto::factory()->create([
            'soggetto_fitok' => true,
            'codice' => 'PF-EXP-001',
            'nome' => 'Abete Export',
        ]);

        $lottoMateriale = LottoMateriale::factory()->create([
            'prodotto_id' => $prodotto->id,
            'codice_lotto' => 'LM-EXP-001',
        ]);

        $lottoProduzione = LottoProduzione::factory()->create([
            'codice_lotto' => 'LP-EXP-001',
            'fitok_percentuale' => 100,
            'created_by' => $this->user->id,
        ]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lottoMateriale->id,
            'lotto_produzione_id' => $lottoProduzione->id,
            'tipo' => TipoMovimento::SCARICO,
            'quantita' => 2,
            'created_by' => $this->user->id,
            'data_movimento' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('fitok.export.excel', [
                'data_inizio' => now()->subDay()->toDateString(),
                'data_fine' => now()->addDay()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $content = $response->streamedContent();
        $tmpPath = tempnam(sys_get_temp_dir(), 'fitok-export-');
        file_put_contents($tmpPath, $content);

        try {
            $zip = new ZipArchive();
            $opened = $zip->open($tmpPath);

            $this->assertTrue($opened === true, 'Impossibile aprire il file XLSX esportato.');

            $this->assertTrue($this->zipContainsText($zip, 'Lotto Produzione Destinazione'));
            $this->assertTrue($this->zipContainsText($zip, 'Stato Certificazione Uscita'));
            $this->assertTrue($this->zipContainsText($zip, 'LP-EXP-001'));

            $zip->close();
        } finally {
            @unlink($tmpPath);
        }
    }

    private function zipContainsText(ZipArchive $zip, string $needle): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name) || !str_ends_with($name, '.xml')) {
                continue;
            }

            $content = $zip->getFromIndex($i);
            if (!is_string($content)) {
                continue;
            }

            if (str_contains($content, $needle)) {
                return true;
            }
        }

        return false;
    }
}
