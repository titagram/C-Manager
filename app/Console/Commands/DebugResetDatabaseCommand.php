<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DebugDatabaseResetService;
use Illuminate\Console\Command;

class DebugResetDatabaseCommand extends Command
{
    protected $signature = 'app:debug-reset-db
        {--confirmed : Conferma esplicita dell\'operazione distruttiva}
        {--requested-by= : ID utente che richiede il reset}';

    protected $description = 'Resetta il database e rilancia i seeder (solo in APP_DEBUG=true e con utente admin).';

    public function __construct(
        private readonly DebugDatabaseResetService $resetService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!(bool) config('app.debug')) {
            $this->error('Comando bloccato: APP_DEBUG deve essere TRUE.');
            return self::FAILURE;
        }

        if (!(bool) $this->option('confirmed')) {
            $this->error('Conferma mancante: rieseguire con --confirmed.');
            return self::FAILURE;
        }

        $requestedBy = (int) $this->option('requested-by');
        if ($requestedBy <= 0) {
            $this->error('Parametro obbligatorio mancante: --requested-by=<id_utente_admin>.');
            return self::FAILURE;
        }

        $user = User::query()->find($requestedBy);
        if (!$user || !$user->isAdmin()) {
            $this->error('Operazione non autorizzata: l\'utente indicato non e admin.');
            return self::FAILURE;
        }

        $this->warn('Reset database in corso: migrate:fresh --seed');

        $exitCode = $this->resetService->resetWithSeed();
        if ($exitCode !== self::SUCCESS) {
            $this->error('Reset fallito durante migrate:fresh --seed.');
            return self::FAILURE;
        }

        $this->info('Reset completato con successo.');

        return self::SUCCESS;
    }
}
