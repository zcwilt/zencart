<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsConfigField;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\Support\zcUnitTestCase;
use Zencart\ConfigField\Renderers\PasswordInputRenderer;
use Zencart\ConfigField\Renderers\ReadOnlyRenderer;
use Zencart\ConfigField\Renderers\SelectDropDownRenderer;
use Zencart\ConfigField\Renderers\SelectMultiOptionPairsRenderer;
use Zencart\ConfigField\Renderers\SelectMultiOptionRenderer;
use Zencart\ConfigField\Renderers\SelectOptionRenderer;
use Zencart\ConfigField\Renderers\TextareaRenderer;
use Zencart\ConfigField\Renderers\TextareaSmallRenderer;

/**
 * Covers the renderer adapters whose wrapped zen_cfg_*() function needs no
 * database access, so they can run as pure Unit tests. Each adapter is pure
 * delegation, so these assert byte-equality against calling the legacy
 * function directly with the same arguments.
 *
 * general.php/html_output.php declare global functions, so this suite runs
 * isolated from other test files that might load the same files.
 */
#[RunTestsInSeparateProcesses]
class RenderersTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (!defined('CHARSET')) {
            define('CHARSET', 'utf-8');
        }
        require_once DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'general.php';
        require_once DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_strings.php';
        require_once DIR_FS_ADMIN . 'includes/functions/html_output.php';
    }

    public function testTextareaRendererMatchesLegacyFunction(): void
    {
        $renderer = new TextareaRenderer();
        $this->assertSame(
            zen_cfg_textarea('some value', 'cfg_1'),
            $renderer->render('some value', 'cfg_1')
        );
    }

    public function testTextareaSmallRendererMatchesLegacyFunction(): void
    {
        $renderer = new TextareaSmallRenderer();
        $this->assertSame(
            zen_cfg_textarea_small('some value', 'cfg_1'),
            $renderer->render('some value', 'cfg_1')
        );
    }

    public function testPasswordInputRendererMatchesLegacyFunctionAndIsSensitive(): void
    {
        $renderer = new PasswordInputRenderer();
        $this->assertSame(
            zen_cfg_password_input('secret', 'cfg_1'),
            $renderer->render('secret', 'cfg_1')
        );
        $this->assertTrue($renderer->isSensitive());
    }

    public function testSelectOptionRendererMatchesLegacyFunction(): void
    {
        $renderer = new SelectOptionRenderer();
        $options = ['true', 'false'];
        $this->assertSame(
            zen_cfg_select_option($options, 'true', 'cfg_1'),
            $renderer->render('true', 'cfg_1', ['options' => $options])
        );
    }

    public function testSelectOptionRendererDefaultsToEmptyOptionsWhenParamsMissing(): void
    {
        $renderer = new SelectOptionRenderer();
        $this->assertSame(
            zen_cfg_select_option([], 'true', 'cfg_1'),
            $renderer->render('true', 'cfg_1')
        );
    }

    public function testSelectDropDownRendererMatchesLegacyFunction(): void
    {
        $renderer = new SelectDropDownRenderer();
        $options = [
            ['id' => '1', 'text' => 'One'],
            ['id' => '2', 'text' => 'Two'],
            ['id' => '3', 'text' => 'Three'],
        ];
        $this->assertSame(
            zen_cfg_select_drop_down($options, '2', 'cfg_1'),
            $renderer->render('2', 'cfg_1', ['options' => $options])
        );
    }

    public function testSelectMultiOptionRendererMatchesLegacyFunction(): void
    {
        $renderer = new SelectMultiOptionRenderer();
        $choices = ['a', 'b', 'c'];
        $this->assertSame(
            zen_cfg_select_multioption($choices, 'a, c', 'cfg_1'),
            $renderer->render('a, c', 'cfg_1', ['choices' => $choices])
        );
    }

    public function testSelectMultiOptionPairsRendererMatchesLegacyFunction(): void
    {
        $renderer = new SelectMultiOptionPairsRenderer();
        $choices = ['a=Apple', 'b=Banana'];
        $this->assertSame(
            zen_cfg_select_multioption_pairs($choices, 'a', 'cfg_1'),
            $renderer->render('a', 'cfg_1', ['choices' => $choices])
        );
    }

    public function testReadOnlyRendererMatchesLegacyFunction(): void
    {
        $renderer = new ReadOnlyRenderer();
        $this->assertSame(
            zen_cfg_read_only('1.2.3', 'cfg_1'),
            $renderer->render('1.2.3', 'cfg_1')
        );
    }
}
