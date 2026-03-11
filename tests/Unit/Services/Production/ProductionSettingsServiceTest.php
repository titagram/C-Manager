<?php

namespace Tests\Unit\Services\Production;

use App\Models\ProductionSetting;
use App\Models\ProductionSettingHistory;
use App\Models\User;
use App\Services\Production\ProductionSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ProductionSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_config_fallback_when_db_setting_is_missing(): void
    {
        Config::set('production.cutting_kerf_mm', 1.25);

        $service = app(ProductionSettingsService::class);
        $service->forgetCache();

        $this->assertSame(1.25, $service->cuttingKerfMm());
    }

    public function test_db_value_has_priority_over_config_fallback(): void
    {
        Config::set('production.cutting_kerf_mm', 0.75);

        ProductionSetting::query()->create([
            'key' => 'cutting_kerf_mm',
            'value' => '2.5',
            'type' => 'float',
        ]);

        $service = app(ProductionSettingsService::class);
        $service->forgetCache();

        $this->assertSame(2.5, $service->cuttingKerfMm());
    }

    public function test_category_alias_is_normalized_to_physical_for_cassa_mode(): void
    {
        ProductionSetting::query()->create([
            'key' => 'cassa_optimizer_mode',
            'value' => 'category',
            'type' => 'string',
        ]);

        $service = app(ProductionSettingsService::class);
        $service->forgetCache();

        $this->assertSame('physical', $service->cassaOptimizerMode());
        $this->assertTrue($service->cassaCategoryOptimizerEnabled());
    }

    public function test_update_many_persists_values_and_refreshes_cache(): void
    {
        ProductionSetting::query()->create([
            'key' => 'gabbia_excel_mode',
            'value' => 'preview',
            'type' => 'string',
        ]);

        $service = app(ProductionSettingsService::class);
        $service->forgetCache();

        $this->assertSame('preview', $service->gabbiaExcelMode());

        $result = $service->updateMany([
            'cassa_optimizer_mode' => 'legacy',
            'gabbia_excel_mode' => 'strict',
            'scrap_reusable_min_length_mm' => 900,
        ]);

        $this->assertSame('legacy', $result['saved']['cassa_optimizer_mode']);
        $this->assertSame('strict', $result['saved']['gabbia_excel_mode']);
        $this->assertSame(900, $result['saved']['scrap_reusable_min_length_mm']);
        $this->assertSame('legacy', $service->cassaOptimizerMode());
        $this->assertFalse($service->cassaCategoryOptimizerEnabled());
        $this->assertSame('strict', $service->gabbiaExcelMode());
        $this->assertSame(900, $service->scrapReusableMinLengthMm());

        $this->assertDatabaseHas('production_settings', [
            'key' => 'cassa_optimizer_mode',
            'value' => 'legacy',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'gabbia_excel_mode',
            'value' => 'strict',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'scrap_reusable_min_length_mm',
            'value' => '900',
        ]);
    }

    public function test_it_skips_locked_keys_when_lock_policy_is_active(): void
    {
        Config::set('production.settings_lock_enabled', true);
        Config::set('production.settings_lock_only_production', false);
        Config::set('production.settings_locked_keys', ['gabbia_excel_mode']);

        ProductionSetting::query()->create([
            'key' => 'gabbia_excel_mode',
            'value' => 'preview',
            'type' => 'string',
        ]);

        $service = app(ProductionSettingsService::class);
        $service->forgetCache();

        $result = $service->updateMany([
            'gabbia_excel_mode' => 'strict',
            'bancale_excel_mode' => 'compatibility',
        ]);

        $this->assertSame(['gabbia_excel_mode'], $result['locked']);
        $this->assertArrayNotHasKey('gabbia_excel_mode', $result['saved']);
        $this->assertSame('preview', $service->gabbiaExcelMode());
        $this->assertSame('compatibility', $service->bancaleExcelMode());

        $this->assertDatabaseHas('production_settings', [
            'key' => 'gabbia_excel_mode',
            'value' => 'preview',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'bancale_excel_mode',
            'value' => 'compatibility',
        ]);
    }

    public function test_it_tracks_old_and_new_values_in_history_with_reason(): void
    {
        $user = User::factory()->admin()->create();

        ProductionSetting::query()->create([
            'key' => 'cutting_kerf_mm',
            'value' => '0.5',
            'type' => 'float',
        ]);

        $service = app(ProductionSettingsService::class);
        $service->forgetCache();

        $result = $service->updateMany([
            'cutting_kerf_mm' => '1.75',
        ], userId: $user->id, reason: 'Aggiornamento prova audit');

        $this->assertSame(1.75, $result['saved']['cutting_kerf_mm']);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'cutting_kerf_mm',
            'value' => '1.75',
            'updated_by' => $user->id,
        ]);

        $this->assertDatabaseHas('production_setting_histories', [
            'key' => 'cutting_kerf_mm',
            'old_value' => '0.5',
            'new_value' => '1.75',
            'changed_reason' => 'Aggiornamento prova audit',
            'changed_by' => $user->id,
            'source' => 'admin_panel',
        ]);

        $historyRows = $service->recentHistory(10);
        $this->assertNotEmpty($historyRows);
        $this->assertInstanceOf(ProductionSettingHistory::class, $historyRows->first());
    }
}
