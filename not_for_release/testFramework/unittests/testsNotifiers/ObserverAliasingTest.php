<?php
/**
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

require_once(__DIR__ . '/../support/zcTestCase.php');


class ObserverAliasingTest extends zcTestCase
{
    protected $preserveGlobalState = FALSE;
    protected $runTestInSeparateProcess = TRUE;

    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . DIR_WS_CLASSES. 'traits/NotifierManager.php';
        require_once DIR_FS_CATALOG . DIR_WS_CLASSES. 'traits/ObserverManager.php';
        require_once DIR_FS_CATALOG . DIR_WS_CLASSES. 'EventDto.php';
        require_once(TESTCWD . 'support/zcObserverAliasTestObject.php');
        require_once(TESTCWD . 'support/zcNotifierBaseAliasTestObject.php');
        require_once(TESTCWD . 'support/zcNotifierTraitAliasTestObject.php');
    }

    public function testObserverAliasing()
    {
        $zcObserverAliasTestObject = new zcObserverAliasTestObject;
        $zcNotifierBaseAliasTestObject = new zcNotifierBaseAliasTestObject;
        $result = $zcNotifierBaseAliasTestObject->fireNotifierValid();
        $this->assertEquals($result, 'NOTIFIY_ORDER_CART_SUBTOTAL_CALCULATE');
        $result = $zcNotifierBaseAliasTestObject->fireNotifierInvalid();
        $this->assertEquals($result, 'invalid');

        $zcNotifierTraitAliasTestObject = new zcNotifierTraitAliasTestObject;
        $result = $zcNotifierTraitAliasTestObject->fireNotifierValid();
        $this->assertEquals($result, 'NOTIFIY_ORDER_CART_SUBTOTAL_CALCULATE');
        $result = $zcNotifierTraitAliasTestObject->fireNotifierInvalid();
        $this->assertEquals($result, 'invalid');
    }
}
