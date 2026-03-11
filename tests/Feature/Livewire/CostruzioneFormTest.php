<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Forms\CostruzioneForm;
use App\Models\Costruzione;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CostruzioneFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_show_weight_in_quote_flag(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CostruzioneForm::class)
            ->set('categoria', 'cassa')
            ->set('nome', 'Cassa con peso')
            ->set('show_weight_in_quote', true)
            ->call('save')
            ->assertRedirect('/costruzioni');

        $costruzione = Costruzione::query()->where('nome', 'Cassa con peso')->firstOrFail();
        $this->assertTrue($costruzione->showWeightInQuote());
    }

    public function test_edit_mount_loads_show_weight_in_quote_flag(): void
    {
        $user = User::factory()->create();

        $costruzione = Costruzione::factory()->create([
            'config' => [
                'show_weight_in_quote' => true,
            ],
        ]);

        Livewire::actingAs($user)
            ->test(CostruzioneForm::class, ['costruzione' => $costruzione])
            ->assertSet('show_weight_in_quote', true)
            ->assertSee('Mostra peso nel preventivo');
    }

    public function test_can_save_cassa_optimizer_key_for_excel_mode(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CostruzioneForm::class)
            ->set('categoria', 'cassa')
            ->set('nome', 'Cassa SP25 Test')
            ->set('cassa_optimizer_key', 'excel_sp25')
            ->call('save')
            ->assertRedirect('/costruzioni');

        $costruzione = Costruzione::query()->where('nome', 'Cassa SP25 Test')->firstOrFail();
        $this->assertSame('excel_sp25', data_get($costruzione->config, 'optimizer_key'));
    }
}
