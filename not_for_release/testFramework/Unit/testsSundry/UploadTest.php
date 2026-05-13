<?php

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;

class UploadTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        defined('WARNING_NO_FILE_UPLOADED') || define('WARNING_NO_FILE_UPLOADED', 'Warning: no file uploaded.');
        defined('ERROR_FILE_TOO_BIG') || define('ERROR_FILE_TOO_BIG', 'Warning: file too large.');
        defined('ERROR_FILE_NOT_SAVED') || define('ERROR_FILE_NOT_SAVED', 'Error: file not saved.');
        defined('MAX_FILE_UPLOAD_SIZE') || define('MAX_FILE_UPLOAD_SIZE', 4096000);

        require_once DIR_FS_CATALOG . 'includes/functions/functions_strings.php';
        require_once DIR_FS_CATALOG . 'includes/classes/class.base.php';
        require_once DIR_FS_CATALOG . 'includes/classes/message_stack.php';
        require_once DIR_FS_CATALOG . 'includes/classes/upload.php';

        $_FILES = [];
        $_SESSION = [];

        global $messageStack;
        $messageStack = new \messageStack();
    }

    public function testParseUsesFileErrorForIniSizeFailures(): void
    {
        $_FILES['products_image'] = [
            'name' => 'too-big.jpg',
            'type' => 'image/jpeg',
            'size' => 3145728,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
        ];

        $upload = new \upload('products_image');

        $this->assertFalse($upload->parse());

        global $messageStack;
        $this->assertSame(
            [
                [
                    'class' => 'header',
                    'text' => ERROR_FILE_TOO_BIG,
                    'type' => 'error',
                ],
            ],
            $_SESSION['messageToStack']
        );
        $this->assertSame('header', $messageStack->messages[0]['class']);
        $this->assertStringContainsString(ERROR_FILE_TOO_BIG, $messageStack->messages[0]['text']);
    }

    public function testParseUsesNoFileMessageForUploadErrNoFile(): void
    {
        $_FILES['products_image'] = [
            'name' => '',
            'type' => '',
            'size' => 0,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $upload = new \upload('products_image');

        $this->assertFalse($upload->parse());

        $this->assertSame(
            [
                [
                    'class' => 'header',
                    'text' => WARNING_NO_FILE_UPLOADED,
                    'type' => 'warning',
                ],
            ],
            $_SESSION['messageToStack']
        );
    }
}
