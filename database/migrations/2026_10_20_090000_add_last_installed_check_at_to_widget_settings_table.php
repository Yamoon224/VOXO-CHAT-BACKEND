<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widget_settings', function (Blueprint $table): void {
            // Vérification d'installation (section 2.3) : touché à chaque
            // appel public de `GET /public/widget/{workspace}/settings` —
            // le script ne peut appeler cette route que s'il est bien chargé
            // sur le site du client.
            $table->timestamp('last_installed_check_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('widget_settings', function (Blueprint $table): void {
            $table->dropColumn('last_installed_check_at');
        });
    }
};
