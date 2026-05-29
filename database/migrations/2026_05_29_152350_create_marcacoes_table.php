<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo');
            $table->timestamp('data_hora');
            $table->date('data_civil');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('gps_indisponivel')->default(false);
            $table->text('detalhes')->nullable();
            $table->string('foto_path')->nullable();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('editado_em')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'data_hora']);
            $table->index('data_hora');
            $table->unique(['user_id', 'tipo', 'data_civil']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcacoes');
    }
};
