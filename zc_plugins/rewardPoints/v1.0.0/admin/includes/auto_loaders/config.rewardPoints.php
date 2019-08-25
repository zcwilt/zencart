<?php
// -----
// Load the Zen Cart v1.5.4 compatibility module.
// Copyright (c) 2014 Vinos de Frutas Tropicales
//
$autoLoadConfig[1][] = array('autoType'=>'class',
                             'loadFile'=>'RewardsOrderDisplay.php',
                             'classPath'=>'observers/');
$autoLoadConfig[65][]  = array ('autoType' => 'init_script',
                                'loadFile' => 'init_test.php');
$autoLoadConfig[65][] = array('autoType'=>'classInstantiate',
                              'className'=>'RewardsOrderDisplay',
                              'objectName'=>'RewardsOrderDisplay');
