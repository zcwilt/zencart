<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: $
 */
namespace App\Models;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Attribute extends Eloquent
{
    protected $table = TABLE_PRODUCTS_ATTRIBUTES;
    protected $primaryKey = 'products_attributes_id';
    public $timestamps = false;

    public function products(): hasMany
    {
        return $this->hasMany(Product::class, 'products_id', 'products_id');
    }

    public function scopeGetAttributeDetails(Builder $query, $productId, $optionId, $optionValueId): void
    {
        $query->where('products_id', $productId)->where('options_values_id', $optionValueId)->where('options_id', $optionId);
    }
}
