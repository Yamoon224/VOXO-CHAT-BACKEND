<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            // Toujours pas de rendu PDF maison : ce champ relaie le PDF déjà
            // généré par le prestataire (`invoice_pdf` chez Stripe), à côté de
            // la page hébergée (`hosted_invoice_url`).
            $table->string('invoice_pdf_url')->nullable()->after('hosted_invoice_url');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('invoice_pdf_url');
        });
    }
};
