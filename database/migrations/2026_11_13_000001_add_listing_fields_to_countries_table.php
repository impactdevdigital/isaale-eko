<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The client's country list (Figma landing page, 1440w): Nigeria, Ghana,
 * Jamaica, Cameroon and Sudan. Countries that dropped off are hidden, not
 * deleted, so products and recipes that point at them keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->boolean('is_listed')->default(true)->after('sort_order');
            // "bottom" pins the tile artwork to the tile's bottom edge (Sudan); null keeps the usual placement.
            $table->string('image_position')->nullable()->after('image');
        });

        // Fresh installs get their countries from StorefrontSeeder, which `shop:install`
        // only runs while the table is empty, so leave an empty table alone.
        if (DB::table('countries')->doesntExist()) {
            return;
        }

        DB::table('countries')
            ->whereIn('slug', ['trinidad-and-tobago', 'ethiopia', 'kenya'])
            ->update(['is_listed' => false]);

        foreach (['nigeria', 'ghana', 'jamaica', 'cameroon'] as $order => $slug) {
            DB::table('countries')->where('slug', $slug)->update(['sort_order' => $order]);
        }

        DB::table('countries')->updateOrInsert(['slug' => 'sudan'], [
            'name' => 'Sudan',
            'code' => 'SD',
            'demonym' => 'Sudanese',
            'tagline' => 'Home has never been closer.',
            'theme' => 'green',
            'image' => 'images/store/countries/sudan.webp',
            'image_position' => 'bottom',
            'banner_description' => 'Find familiar Sudanese staples, pantry favourites and flavours from home — all in one place.',
            'sort_order' => 4,
            'is_listed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('countries')->where('slug', 'sudan')->delete();

        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['is_listed', 'image_position']);
        });
    }
};
