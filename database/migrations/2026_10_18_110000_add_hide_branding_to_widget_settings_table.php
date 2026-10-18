<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widget_settings', function (Blueprint $table): void {
            // Marque blanche (lot 6, section 2.3, P2) : retire la mention
            // VOXO du widget. N'a d'effet visuel qu'une fois le bundle
            // `widget.js` embarquable construit — ce réglage, lui, est prêt.
            $table->boolean('hide_branding')->default(false)->after('offline_message');
        });
    }

    public function down(): void
    {
        Schema::table('widget_settings', function (Blueprint $table): void {
            $table->dropColumn('hide_branding');
        });
    }
};
