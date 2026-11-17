<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Country;
use App\Models\Product;

/**
 * French (Canada) names for the catalogue, matched on the English name.
 * They only fill empty name_fr fields: anything staff typed in the admin
 * is never replaced. Run on install, after each Square sync and by the
 * migration that added the columns, so products and categories that
 * Square adds later get their French name too when we know one.
 */
class FrenchNames
{
    public const PRODUCTS = [
        'Black Eyed Beans' => 'Haricots à œil noir',
        'West African Pepper Sauce' => 'Sauce pimentée d’Afrique de l’Ouest',
        'Ripe Plantain' => 'Banane plantain mûre',
        'Indomie' => 'Indomie',
        'Zobo Hibiscus Drink' => 'Boisson à l’hibiscus Zobo',
        'Dried Hibiscus Petals' => 'Pétales d’hibiscus séchés',
        'Fresh Okra' => 'Gombo frais',
        'White Garri' => 'Garri blanc',
        'Groundnut Paste' => 'Pâte d’arachide',
        'Titus Sardines' => 'Sardines Titus',
        'Peak Evaporated Milk' => 'Lait évaporé Peak',
        'Pounded Yam' => 'Igname pilée',
        'Tomatoes' => 'Tomates',
        'Nasco Cornflakes' => 'Flocons de maïs Nasco',
        'Onga Seasoning' => 'Assaisonnement Onga',
        'Dangota Sugar' => 'Sucre Dangota',
        'Golden Penny Macaroni' => 'Macaronis Golden Penny',
        'Premium Parboiled Rice' => 'Riz étuvé de qualité supérieure',
        'Gino Tomato Paste' => 'Pâte de tomate Gino',
        'Scotch Bonnet Peppers' => 'Piments Scotch Bonnet',
        'Mamador Pure Oil' => 'Huile pure Mamador',
        'Maggi Seasoning Cubes' => 'Cubes d’assaisonnement Maggi',
        'Fresh Beef' => 'Bœuf frais',
        'Red Palm Oil' => 'Huile de palme rouge',
        'Vegetable' => 'Légume',
        'Dried Stockfish' => 'Stockfish séché',
        'Dried Crayfish' => 'Écrevisses séchées',
        'Sardines in Tomato Sauce' => 'Sardines à la sauce tomate',
        'Pure Red Palm Oil' => 'Huile de palme rouge pure',
        'Fresh Green Plantain' => 'Banane plantain verte fraîche',
        'Ground Egusi Seeds' => 'Graines d’egusi moulues',
        'Fresh Spinach' => 'Épinards frais',
        'Seasoning Stock Cubes' => 'Cubes de bouillon assaisonné',
        'Rich Tomato Paste' => 'Pâte de tomate riche',
        'Pounded Yam Flour' => 'Farine d’igname pilée',
        'Indomie Chicken Noodles' => 'Nouilles au poulet Indomie',
        'Capri-Sun Tropical' => 'Capri-Sun Tropical',
        'Golden Penny Semovita' => 'Semovita Golden Penny',
        'Nestlé Milo' => 'Nestlé Milo',
        'Supligen Vanilla Drink' => 'Boisson à la vanille Supligen',
        'Chief Curry Powder' => 'Poudre de cari Chief',
        'Mama Gold Garri' => 'Garri Mama Gold',
        'Bournvita Chocolate Drink' => 'Boisson chocolatée Bournvita',
        'Knorr Chicken Seasoning' => 'Assaisonnement au poulet Knorr',
        'Ofada Rice' => 'Riz Ofada',
        'Basmati Rice' => 'Riz basmati',
        'Ginger' => 'Gingembre',
        'Clove' => 'Clou de girofle',
        'Pineapple' => 'Ananas',
    ];

    public const CATEGORIES = [
        'Food & pastry' => 'Épicerie et pâtisserie',
        'Flours & baking' => 'Farines et pâtisserie',
        'Grains & cereals' => 'Grains et céréales',
        'Spices & seasonings' => 'Épices et assaisonnements',
        'Cooking oils' => 'Huiles de cuisson',
        'Vegetables' => 'Légumes',
        'Frozen foods' => 'Produits surgelés',
        'Meat & poultry' => 'Viande et volaille',
        'Seafood' => 'Poissons et fruits de mer',
        'Frozen vegetables' => 'Légumes surgelés',
        'Frozen prepared foods' => 'Plats préparés surgelés',
        'Other frozen foods' => 'Autres produits surgelés',
        'Snacks & treats' => 'Collations et gâteries',
        'Snacks' => 'Collations',
        'Sweets & confectionary' => 'Bonbons et confiseries',
        'Drinks' => 'Boissons',
        'Soft drinks' => 'Boissons gazeuses',
        'Beverages' => 'Breuvages',
        'Beauty & personal care' => 'Beauté et soins personnels',
        'Skincare' => 'Soins de la peau',
        'Haircare' => 'Soins capillaires',
        'Bath & body' => 'Bain et corps',
        'Personal care' => 'Soins personnels',
        'Beauty accessories' => 'Accessoires de beauté',
        'Home & household' => 'Maison et entretien',
        'Cleaning' => 'Nettoyage',
        'Laundry' => 'Lessive',
        'Kitchen & household' => 'Cuisine et maison',
        'Other home essentials' => 'Autres essentiels pour la maison',
        'Rice & beans' => 'Riz et haricots',
        'Rice & Grains' => 'Riz et grains',
        'Sauces' => 'Sauces',
        'Seasonings' => 'Assaisonnements',
        'Fresh produce' => 'Fruits et légumes frais',
        'Meat & fish' => 'Viande et poisson',
        'Beauty & home' => 'Beauté et maison',
    ];

    public const COUNTRIES = [
        'Nigeria' => 'Nigéria',
        'Ghana' => 'Ghana',
        'Jamaica' => 'Jamaïque',
        'Cameroon' => 'Cameroun',
        'Trinidad & Tobago' => 'Trinité-et-Tobago',
        'Ethiopia' => 'Éthiopie',
        'Kenya' => 'Kenya',
        'Sudan' => 'Soudan',
    ];

    /**
     * Fill empty name_fr fields. Returns how many rows got a French name.
     */
    public static function fill(): int
    {
        $filled = 0;

        foreach ([Product::class => self::PRODUCTS, Category::class => self::CATEGORIES, Country::class => self::COUNTRIES] as $model => $names) {
            $lookup = collect($names)->mapWithKeys(fn ($fr, $en) => [mb_strtolower($en) => $fr]);

            $model::query()->where(fn ($q) => $q->whereNull('name_fr')->orWhere('name_fr', ''))->get(['id', 'name'])
                ->each(function ($row) use ($model, $lookup, &$filled) {
                    if ($fr = $lookup->get(mb_strtolower(trim($row->name)))) {
                        $filled += $model::whereKey($row->id)->update(['name_fr' => $fr]);
                    }
                });
        }

        return $filled;
    }
}
