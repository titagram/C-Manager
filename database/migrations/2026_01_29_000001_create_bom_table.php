<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 30)->unique();
            $table->string('nome');
            $table->foreignId('prodotto_id')->nullable()->constrained('prodotti')->nullOnDelete();
            $table->string('categoria_output')->nullable();
            $table->string('versione', 10)->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // codice already has unique index from ->unique()
            $table->index('is_active');
            $table->index('prodotto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom');
    }
};
