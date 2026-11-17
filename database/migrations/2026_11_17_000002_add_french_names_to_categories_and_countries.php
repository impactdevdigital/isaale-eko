<?php

use App\Support\FrenchNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * French menu: categories and countries get a French name next to the
 * English one, like products already have. Fills the known ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_fr')->nullable();
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->string('name_fr')->nullable();
        });

        FrenchNames::fill();
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('name_fr'));
        Schema::table('countries', fn (Blueprint $table) => $table->dropColumn('name_fr'));
    }
};
