<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('funcionario')->after('password');
            $table->string('cargo', 100)->nullable()->after('role');
            $table->boolean('must_change_password')->default(true)->after('cargo');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'cargo', 'must_change_password']);
            $table->dropSoftDeletes();
        });
    }
};
