<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_setting_histories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120);
            $table->string('type', 32)->default('string');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('source', 32)->default('admin_panel');
            $table->string('changed_reason', 500)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['key', 'created_at']);
            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_setting_histories');
    }
};
