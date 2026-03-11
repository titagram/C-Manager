<?php

namespace Tests\Unit\Models;

use App\Models\ProductionSettingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSettingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_rows_are_append_only_and_cannot_be_updated(): void
    {
        $user = User::factory()->admin()->create();

        $row = ProductionSettingHistory::query()->create([
            'key' => 'cutting_kerf_mm',
            'type' => 'float',
            'old_value' => '0',
            'new_value' => '2.5',
            'source' => 'admin_panel',
            'changed_reason' => 'test',
            'changed_by' => $user->id,
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('append-only');

        $row->update([
            'new_value' => '3.0',
        ]);
    }

    public function test_history_rows_cannot_be_deleted_from_application_layer(): void
    {
        $user = User::factory()->admin()->create();

        $row = ProductionSettingHistory::query()->create([
            'key' => 'cutting_kerf_mm',
            'type' => 'float',
            'old_value' => '0',
            'new_value' => '2.5',
            'source' => 'admin_panel',
            'changed_reason' => 'test',
            'changed_by' => $user->id,
        ]);

        try {
            $row->delete();
            $this->fail('Expected LogicException was not thrown.');
        } catch (\LogicException $e) {
            $this->assertStringContainsString('non possono essere eliminate', $e->getMessage());
        }

        $this->assertDatabaseHas('production_setting_histories', [
            'id' => $row->id,
        ]);
    }
}
