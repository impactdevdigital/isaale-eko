<?php

namespace App\Models;

/**
 * Alias for ProductVariation to align with Part G & H specifications.
 */
class Variation extends ProductVariation
{
    protected $table = 'product_variations';
}
