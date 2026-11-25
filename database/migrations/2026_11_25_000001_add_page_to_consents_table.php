<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The storefront page a consent was given on (e.g. /shop for the footer
 * form), shown on the admin Newsletter sign-ups page and sent to Brevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->string('page', 255)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('consents', function (Blueprint $table) {
            $table->dropColumn('page');
        });
    }
};
