[<?php
/**
* @copyright Copyright 2003-2020 Zen Cart Development Team
* @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
*/

use App\Models\Admin;
use Tests\Support\Traits\DatabaseConcerns;
use Tests\Support\zcUnitTestCase;

class ConfigurationGroupTest extends zcUnitTestCase
{
    use DatabaseConcerns;

    public $databaseFixtures = ['configurationGroup' => ['configuration_group', 'configuration']];

    public function testZenGetConfigurationGroupValue()
    {
        require(DIR_FS_ADMIN . 'includes/functions/general.php');
        $result = zen_get_configuration_group_value(1);
        $this->assertEquals('test-group-title', $result);
        $result = zen_get_configuration_group_value(9);
        $this->assertEquals(9, $result);
    }

    public function testAddCookiePathSwitch()
    {
        global $db;
        require DIR_FS_ADMIN . 'includes/functions/extra_functions/add_cookie_path_switch.php';
        $r = \App\Models\Configuration::where('configuration_group_id', 15)->where('configuration_key', 'SESSION_USE_ROOT_COOKIE_PATH')->first();
        $this->assertEquals(15, $r->configuration_group_id);
        $r = \App\Models\Configuration::where('configuration_group_id', 15)->where('configuration_key', 'SESSION_ADD_PERIOD_PREFIX')->first();
        $this->assertEquals(15, $r->configuration_group_id);

    }
}
