<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version GIT: $Id: $
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPage extends Model
{
    use HasFactory;

    protected $table = TABLE_ADMIN_PAGES;
    protected $primaryKey = 'page_key';
    public $timestamps = false;
    protected $keyType = 'string';
}
