<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom', function (Blueprint $table) {
            $table->foreignId('lotto_produzione_id')
                ->nullable()
                ->after('prodotto_id')
                ->constrained('lotti_produzione')
                ->nullOnDelete();
            $table->foreignId('ordine_id')
                ->nullable()
                ->after('lotto_produzione_id')
                ->constrained('ordini')
                ->nullOnDelete();
            $table->timestamp('generated_at')->nullable()->after('is_active');
            $table->string('source', 30)->default('template')->after('generated_at');

            $table->index('source');
            $table->index('generated_at');
        });

        // Existing BOM are treated as static templates.
        DB::table('bom')->update([
            'source' => 'template',
            'generated_at' => null,
        ]);

        Schema::table('bom_righe', function (Blueprint $table) {
            $table->string('source_type', 30)->nullable()->after('prodotto_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->boolean('is_optional')->default(false)->after('is_fitok_required');

            $table->index(['source_type', 'source_id']);
            $table->index('is_optional');
        });
    }

    public function down(): void
    {
        Schema::table('bom_righe', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropIndex(['is_optional']);
            $table->dropColumn([
                'source_type',
                'source_id',
                'is_optional',
            ]);
        });

        Schema::table('bom', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropIndex(['generated_at']);
            $table->dropForeign(['lotto_produzione_id']);
            $table->dropForeign(['ordine_id']);
            $table->dropColumn([
                'lotto_produzione_id',
                'ordine_id',
                'generated_at',
                'source',
            ]);
        });
    }
};
