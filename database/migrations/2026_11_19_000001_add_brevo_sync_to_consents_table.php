<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email consents are copied into Brevo for marketing email. Keep the name
 * given at sign-up (for Brevo's FIRSTNAME / LASTNAME) and when the consent
 * last reached Brevo, so failed or older sign-ups are retried by brevo:sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->string('name')->nullable()->after('email');
            $table->timestamp('brevo_synced_at')->nullable()->after('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->dropColumn(['name', 'brevo_synced_at']);
        });
    }
};
