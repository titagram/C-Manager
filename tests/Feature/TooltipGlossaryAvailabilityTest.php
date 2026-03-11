<?php

namespace Tests\Feature;

use Tests\TestCase;

class TooltipGlossaryAvailabilityTest extends TestCase
{
    public function test_glossary_file_exists_with_required_entries(): void
    {
        $path = base_path('docs/ux/glossario_tooltip_magazzino_fitok.md');

        $this->assertFileExists($path);

        $content = (string) file_get_contents($path);

        $this->assertStringContainsString('Con giacenza (> 0)', $content);
        $this->assertStringContainsString('Senza giacenza (<= 0)', $content);
        $this->assertStringContainsString('Con scarti disponibili', $content);
        $this->assertStringContainsString('Tipologia scarto', $content);
        $this->assertStringContainsString('Preparazione avvio (lotto)', $content);
        $this->assertStringContainsString('Destinazione FITOK', $content);
    }
}
