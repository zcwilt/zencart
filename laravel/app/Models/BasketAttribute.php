<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: $
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BasketAttribute extends Eloquent
{
    protected $table = TABLE_BASKET_ATTRIBUTES;
    public $timestamps = false;
    protected $guarded = [];

    public function basketProduct(): BelongsTo
    {
        return $this->belongsTo(BasketProduct::class);
    }
}
