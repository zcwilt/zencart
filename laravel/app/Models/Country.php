<?php
<<<<<<< HEAD
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: $
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Country extends Eloquent
{
    protected $table = TABLE_COUNTRIES;
    protected $primaryKey = 'countries_id';
    public $timestamps = false;
    protected $guarded = [];

    public function zones()
    {
        return $this->hasMany(Zone::class, 'zone_country_id', 'countries_id');
    }
=======

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = TABLE_COUNTRIES;
    protected $primaryKey = 'countries_id';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'status' => 'boolean',
    ];

<<<<<<< HEAD
>>>>>>> ba423be0e (more)
=======
    public function addressFormat()
    {
        return $this->belongsTo(AddressFormat::class, 'address_format_id', 'address_format_id');
    }
>>>>>>> e49386ac5 (further dashboard work)
}
