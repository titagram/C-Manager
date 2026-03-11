<?php

namespace Tests\Feature\Seeders;

use App\Models\ComponenteCostruzione;
use App\Models\Costruzione;
use Database\Seeders\CostruzioneSeeder;
use Database\Seeders\CostruzioniSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostruzioniSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_costruzioni_seeder_creates_expected_templates_and_components(): void
    {
        $this->seed(CostruzioniSeeder::class);

        $this->assertDatabaseHas('costruzioni', [
            'slug' => 'cassa-standard',
            'categoria' => 'cassa',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('costruzioni', [
            'slug' => 'cassa-standard-geometrica',
            'categoria' => 'cassa',
        ]);
        $this->assertDatabaseHas('costruzioni', [
            'slug' => 'cassa-sp25',
            'categoria' => 'cassa',
        ]);
        $this->assertDatabaseHas('costruzioni', [
            'slug' => 'cassa-sp25-fondo40',
            'categoria' => 'cassa',
        ]);

        $this->assertDatabaseHas('componenti_costruzione', [
            'nome' => 'Parete Corta (Interna)',
            'formula_lunghezza' => 'W - (2 * T)',
            'is_internal' => true,
        ]);

        $this->assertDatabaseHas('componenti_costruzione', [
            'nome' => 'Doghe Piano Superiore',
            'formula_quantita' => 'ceil(L / 130)',
        ]);

        $this->assertGreaterThan(0, Costruzione::count());
        $this->assertGreaterThan(0, ComponenteCostruzione::count());
    }

    public function test_costruzione_seeder_alias_is_backward_compatible(): void
    {
        $this->seed(CostruzioneSeeder::class);

        $this->assertDatabaseHas('costruzioni', [
            'slug' => 'cassa-standard',
            'categoria' => 'cassa',
        ]);
    }

    public function test_costruzioni_seeder_preserves_unmanaged_custom_constructions(): void
    {
        $custom = Costruzione::factory()->create([
            'slug' => 'cassa-custom-utente',
            'nome' => 'Cassa Custom Utente',
            'categoria' => 'cassa',
            'config' => [],
        ]);

        ComponenteCostruzione::factory()->create([
            'costruzione_id' => $custom->id,
            'nome' => 'Legno Custom',
        ]);

        $this->seed(CostruzioniSeeder::class);

        $this->assertDatabaseHas('costruzioni', [
            'id' => $custom->id,
            'slug' => 'cassa-custom-utente',
        ]);
        $this->assertDatabaseHas('componenti_costruzione', [
            'costruzione_id' => $custom->id,
            'nome' => 'Legno Custom',
        ]);
    }
}
