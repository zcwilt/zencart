<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: $
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Basket extends Eloquent
{
    protected $table = TABLE_BASKETS;

    public function basketProducts()
    {
        return $this->hasMany(BasketProduct::class);
    }

    public function delete(): ?bool
    {
        $this->basketProducts()->each(function ($msg) {
            $msg->delete();
        });
        $result = parent::delete();
        return $result;
    }
}
