<?php

namespace App\Console\Commands;

use App\Enums\StatoPreventivo;
use App\Models\Preventivo;
use Illuminate\Console\Command;

class ExpirePreventivi extends Command
{
    protected $signature = 'preventivi:expire';

    protected $description = 'Segna come SCADUTO i preventivi INVIATI con validita_fino passata';

    public function handle(): int
    {
        $count = Preventivo::query()
            ->where('stato', StatoPreventivo::INVIATO->value)
            ->whereNotNull('validita_fino')
            ->where('validita_fino', '<', now()->startOfDay())
            ->update(['stato' => StatoPreventivo::SCADUTO->value]);

        $this->info("Preventivi scaduti: {$count}");

        return self::SUCCESS;
    }
}