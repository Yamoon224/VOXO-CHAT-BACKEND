<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            // Rattachement au profil CRM (lot 4) : nullable, une conversation
            // sans adresse visiteur connue n'a pas de contact. Rempli via
            // `ContactLinkContract`, jamais directement par `Conversations`.
            $table->foreignUuid('contact_id')->nullable()->after('visitor_email')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('contact_id');
        });
    }
};
