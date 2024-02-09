<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: $
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class BasketProduct extends Eloquent
{
    protected $table = TABLE_BASKET_PRODUCTS;
    public $timestamps = false;

    public function basketAttributes()
    {
        return $this->hasMany(BasketAttribute::class);
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'products_id');
    }

    public function delete() :?bool
    {
        $this->basketAttributes()->each(function($msg) {
            $msg->delete();
        });

        $result = parent::delete();
        return $result;
    }


}
