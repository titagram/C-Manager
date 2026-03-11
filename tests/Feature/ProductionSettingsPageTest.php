<?php

namespace Tests\Feature;

use App\Livewire\Forms\ProductionSettingsForm;
use App\Models\LottoMateriale;
use App\Models\MovimentoMagazzino;
use App\Models\Prodotto;
use App\Models\ProductionSetting;
use App\Models\User;
use App\Enums\TipoMovimento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ProductionSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_mount_production_settings_livewire_component(): void
    {
        $operatore = User::factory()->create();

        Livewire::actingAs($operatore)
            ->test(ProductionSettingsForm::class)
            ->assertForbidden();
    }

    public function test_admin_can_save_production_settings_from_livewire_form(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->set('cutting_kerf_mm', '2.75')
            ->set('cassa_optimizer_mode', 'legacy')
            ->set('gabbia_excel_mode', 'compatibility')
            ->set('bancale_excel_mode', 'strict')
            ->set('legaccio_excel_mode', 'preview')
            ->set('scrap_reusable_min_length_mm', '820')
            ->set('change_reason', 'Allineamento parametri produzione')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('production_settings', [
            'key' => 'cutting_kerf_mm',
            'value' => '2.75',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'cassa_optimizer_mode',
            'value' => 'legacy',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'gabbia_excel_mode',
            'value' => 'compatibility',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'bancale_excel_mode',
            'value' => 'strict',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'legaccio_excel_mode',
            'value' => 'preview',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'scrap_reusable_min_length_mm',
            'value' => '820',
        ]);
        $this->assertDatabaseHas('production_setting_histories', [
            'key' => 'cutting_kerf_mm',
            'new_value' => '2.75',
            'changed_reason' => 'Allineamento parametri produzione',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_locked_keys_are_not_overwritten_from_livewire_form(): void
    {
        $admin = User::factory()->admin()->create();

        Config::set('production.settings_lock_enabled', true);
        Config::set('production.settings_lock_only_production', false);
        Config::set('production.settings_locked_keys', ['cutting_kerf_mm']);

        ProductionSetting::query()->create([
            'key' => 'cutting_kerf_mm',
            'value' => '0.5',
            'type' => 'float',
        ]);

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->set('cutting_kerf_mm', '3.5')
            ->set('gabbia_excel_mode', 'strict')
            ->set('change_reason', 'Test lock policy')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('production_settings', [
            'key' => 'cutting_kerf_mm',
            'value' => '0.5',
        ]);
        $this->assertDatabaseHas('production_settings', [
            'key' => 'gabbia_excel_mode',
            'value' => 'strict',
        ]);
        $this->assertDatabaseHas('production_setting_histories', [
            'key' => 'gabbia_excel_mode',
            'new_value' => 'strict',
            'changed_reason' => 'Test lock policy',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_it_shows_preview_mode_operational_warning_when_preview_modes_are_active(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->assertSee('Modalita preview ancora attive')
            ->assertSee('Gabbia Excel mode')
            ->assertSee('Bancale Excel mode')
            ->assertSee('Legaccio Excel mode');
    }

    public function test_it_shows_critical_alert_after_saving_critical_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->set('cutting_kerf_mm', '1.25')
            ->set('cassa_optimizer_mode', 'legacy')
            ->set('change_reason', 'Test critical change alert')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Attenzione: modificati parametri critici')
            ->assertSee('cutting_kerf_mm')
            ->assertSee('cassa_optimizer_mode');
    }

    public function test_shows_debug_reset_section_only_when_app_debug_true(): void
    {
        config()->set('app.debug', true);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->assertSee('Reset ambiente debug')
            ->assertSee('Resetta database e ripopola seed');
    }

    public function test_hides_debug_reset_section_when_app_debug_false(): void
    {
        config()->set('app.debug', false);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->assertDontSee('Reset ambiente debug')
            ->assertDontSee('Resetta database e ripopola seed');
    }

    public function test_debug_reset_action_requires_explicit_confirmation(): void
    {
        config()->set('app.debug', true);
        $admin = User::factory()->admin()->create();

        Artisan::shouldReceive('call')->never();

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->set('debugResetConfirmation', 'WRONG TOKEN')
            ->call('debugResetDatabase')
            ->assertHasErrors(['debugResetConfirmation'])
            ->assertSee('Conferma non valida');
    }

    public function test_debug_reset_action_executes_command_when_confirmed(): void
    {
        config()->set('app.debug', true);
        $admin = User::factory()->admin()->create();

        Artisan::shouldReceive('call')
            ->once()
            ->with('app:debug-reset-db', Mockery::on(
                fn (array $args): bool => ($args['--confirmed'] ?? false) === true
                    && (int) ($args['--requested-by'] ?? 0) === $admin->id
            ))
            ->andReturn(0);

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->set('debugResetConfirmation', 'RESET DB')
            ->call('debugResetDatabase')
            ->assertHasNoErrors()
            ->assertSet('debugResetConfirmation', '')
            ->assertSee('Reset database completato con successo.');
    }

    public function test_displays_inventory_anomaly_kpis(): void
    {
        $admin = User::factory()->admin()->create();
        $prodotto = Prodotto::factory()->create();
        $lotto = LottoMateriale::factory()->create(['prodotto_id' => $prodotto->id]);

        MovimentoMagazzino::query()->create([
            'lotto_materiale_id' => $lotto->id,
            'tipo' => TipoMovimento::RETTIFICA_NEGATIVA,
            'quantita' => 1.5,
            'causale' => 'Rettifica inventario',
            'causale_codice' => MovimentoMagazzino::REASON_CODE_SUSPECTED_SHORTAGE,
            'created_by' => $admin->id,
            'data_movimento' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ProductionSettingsForm::class)
            ->assertSee('Anomalie inventario (ultimi 30 giorni)')
            ->assertSee('Rettifiche negative')
            ->assertSee('Qty sospetto ammanco')
            ->assertSee('Lotti mismatch scarti')
            ->assertSee('Consumi senza movimento');
    }
}
