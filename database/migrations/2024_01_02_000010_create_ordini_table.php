<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordini', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('preventivo_id')->nullable()->constrained('preventivi')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clienti');
            $table->date('data_ordine');
            $table->date('data_consegna_prevista')->nullable();
            $table->date('data_consegna_effettiva')->nullable();
            // Valid stato values: confermato, in_produzione, pronto, consegnato, fatturato, annullato
            $table->string('stato')->default('confermato');
            $table->text('descrizione')->nullable();
            $table->text('note')->nullable();
            $table->decimal('totale', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero');
            $table->index('stato');
            $table->index('data_ordine');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordini');
    }
};
