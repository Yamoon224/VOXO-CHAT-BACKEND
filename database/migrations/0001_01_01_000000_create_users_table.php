<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            // Identifiant public : il circule dans les URL et les journaux. Un
            // entier séquentiel y annoncerait le nombre de comptes ouverts.
            $table->uuid('id')->primary();
            $table->string('name');
            // Stockée en minuscules par l'application : l'unicité vaut alors
            // quelle que soit la casse saisie.
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('locale', 5)->default('fr');

            // Un compte désactivé conserve son historique, il perd l'accès.
            $table->boolean('is_active')->default(true);

            $table->text('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
