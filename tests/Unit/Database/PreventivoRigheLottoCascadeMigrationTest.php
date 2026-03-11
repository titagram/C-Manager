<?php

namespace Tests\Unit\Database;

use App\Models\LottoProduzione;
use App\Models\Preventivo;
use App\Models\PreventivoRiga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PreventivoRigheLottoCascadeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_preventivo_riga_is_deleted_when_lotto_is_force_deleted_at_db_level(): void
    {
        $preventivo = Preventivo::factory()->create();
        $lotto = LottoProduzione::factory()->create([
            'preventivo_id' => $preventivo->id,
            'cliente_id' => $preventivo->cliente_id,
        ]);

        $riga = PreventivoRiga::factory()->create([
            'preventivo_id' => $preventivo->id,
            'lotto_produzione_id' => $lotto->id,
        ]);

        $this->assertDatabaseHas('preventivo_righe', ['id' => $riga->id]);

        // Direct DB delete validates the FK behavior (independent from model hooks).
        DB::table('lotti_produzione')->where('id', $lotto->id)->delete();

        $this->assertDatabaseMissing('lotti_produzione', ['id' => $lotto->id]);
        $this->assertDatabaseMissing('preventivo_righe', ['id' => $riga->id]);
    }
}
