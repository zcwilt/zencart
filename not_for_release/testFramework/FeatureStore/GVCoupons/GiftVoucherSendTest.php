<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

declare(strict_types=1);

namespace Tests\FeatureStore\GVCoupons;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Database\TestDb;
use Tests\Support\InProcess\FeatureResponse;
use Tests\Support\Traits\CustomerAccountConcerns;
use Tests\Support\zcInProcessFeatureTestCaseStore;

/**
 * gv_send: the 'send' step validates and shows a confirmation form; 'process' creates the voucher and emails it.
 * 'process' can be posted directly, so it must repeat every 'send' check itself.
 */
#[Group('parallel-candidate')]
#[RunTestsInSeparateProcesses]
class GiftVoucherSendTest extends zcInProcessFeatureTestCaseStore
{
    use CustomerAccountConcerns;

    private const STARTING_BALANCE = 100.0;

    private int $customerId;

    public function setUp(): void
    {
        parent::setUp();
        $this->setConfiguration('DEFAULT_CURRENCY', 'USD');
        $profile = $this->createCustomerAccountOrLogin('florida-basic1');
        $this->customerId = (int) $this->getCustomerIdFromEmail($profile['email_address']);
        $this->addGiftVoucherBalance($profile['email_address'], self::STARTING_BALANCE);
    }

    #[Test]
    public function sendAndConfirmCreatesVoucherAndDebitsBalance(): void
    {
        $couponsBefore = $this->giftVoucherCouponCount();

        $confirmation = $this->submitGiftVoucherSendForm([
            'to_name' => 'Tom Bombadil',
            'email' => 'friend@example.com',
            'amount' => '25',
            'message' => 'Enjoy',
        ])->assertOk()
            ->assertSee('Send Gift Certificate Confirmation');

        $this->confirmGiftVoucherSend($confirmation)
            ->assertOk()
            ->assertSee('Gift Certificate Sent');

        $this->assertSame($couponsBefore + 1, $this->giftVoucherCouponCount());
        $this->assertSame('75.0000', $this->balance());

        $track = $this->latestEmailTrack();
        $this->assertNotNull($track);
        $this->assertSame('friend@example.com', (string) $track['emailed_to']);
        $this->assertSame('25.0000', number_format((float) $track['coupon_amount'], 4, '.', ''));
    }

    #[Test]
    public function sendStepRejectsWhitespaceOnlyRecipientName(): void
    {
        $response = $this->submitGiftVoucherSendForm([
            'to_name' => '   ',
            'email' => 'friend@example.com',
            'amount' => '10',
        ])->assertOk()
            ->assertSee('We did not get the Recipient');

        $this->assertStringNotContainsString('Send Gift Certificate Confirmation', $response->content);
    }

    #[Test]
    public function sendStepRejectsEmptyAmountWithoutFatalError(): void
    {
        $response = $this->submitGiftVoucherSendForm([
            'to_name' => 'Tom Bombadil',
            'email' => 'friend@example.com',
            'amount' => '',
        ])->assertOk()
            ->assertSee('amount does not appear to be correct');

        $this->assertStringNotContainsString('Send Gift Certificate Confirmation', $response->content);
    }

    #[Test]
    public function processRejectsBlankRecipientName(): void
    {
        $this->assertProcessRejected(
            ['to_name' => '   ', 'email' => 'friend@example.com', 'amount' => '10'],
            'We did not get the Recipient'
        );
    }

    #[Test]
    public function processRejectsInvalidEmail(): void
    {
        $this->assertProcessRejected(
            ['to_name' => 'Tom Bombadil', 'email' => '"><script>alert(1)</script>', 'amount' => '10'],
            'Is the email address correct?'
        );
    }

    #[Test]
    #[DataProvider('invalidProcessAmounts')]
    public function processRejectsInvalidAmount(?string $amount): void
    {
        $data = ['to_name' => 'Tom Bombadil', 'email' => 'friend@example.com'];
        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        $this->assertProcessRejected($data, 'amount does not appear to be correct');
    }

    public static function invalidProcessAmounts(): array
    {
        return [
            'zero' => ['0'],
            'rounds to zero' => ['0.001'],
            'negative sign stripped to overspend' => ['-100.01'],
            'more than balance' => ['100.01'],
            'empty' => [''],
            'percent only' => ['%'],
            'comma only' => [','],
            'missing' => [null],
        ];
    }

    #[Test]
    public function processStoresTrimmedEmailAddress(): void
    {
        $this->postProcessDirectly([
            'to_name' => 'Tom Bombadil',
            'email' => "  friend@example.com\r\n",
            'amount' => '10',
        ])->assertOk()
            ->assertSee('Gift Certificate Sent');

        $this->assertSame('friend@example.com', (string) $this->latestEmailTrack()['emailed_to']);
    }

    #[Test]
    public function replayedProcessCannotSpendMoreThanBalance(): void
    {
        $couponsBefore = $this->giftVoucherCouponCount();
        $data = ['to_name' => 'Tom Bombadil', 'email' => 'friend@example.com', 'amount' => '60'];

        $this->postProcessDirectly($data)
            ->assertOk()
            ->assertSee('Gift Certificate Sent');

        $this->postProcessDirectly($data)
            ->assertOk()
            ->assertSee('amount does not appear to be correct');

        $this->assertSame($couponsBefore + 1, $this->giftVoucherCouponCount());
        $this->assertSame('40.0000', $this->balance());
    }

    #[Test]
    #[DataProvider('arrayInputs')]
    public function sendStepTreatsArrayInputAsEmpty(string $field, ?string $expectedError): void
    {
        $data = $this->validSendData();
        $data[$field] = ['x'];

        $response = $this->submitGiftVoucherSendForm($data)->assertOk();

        if ($expectedError === null) {
            $response->assertSee('Send Gift Certificate Confirmation');
            return;
        }
        $response->assertSee($expectedError);
        $this->assertStringNotContainsString('Send Gift Certificate Confirmation', $response->content);
    }

    #[Test]
    #[DataProvider('arrayInputs')]
    public function processTreatsArrayInputAsEmpty(string $field, ?string $expectedError): void
    {
        $data = $this->validSendData();
        $data[$field] = ['x'];

        if ($expectedError === null) {
            $this->postProcessDirectly($data)
                ->assertOk()
                ->assertSee('Gift Certificate Sent');
            $this->assertSame('90.0000', $this->balance());
            return;
        }
        $this->assertProcessRejected($data, $expectedError);
    }

    public static function arrayInputs(): array
    {
        return [
            'to_name' => ['to_name', 'We did not get the Recipient'],
            'email' => ['email', 'Is the email address correct?'],
            'amount' => ['amount', 'amount does not appear to be correct'],
            'message is optional' => ['message', null],
        ];
    }

    #[Test]
    public function sendFormRedisplayEscapesMessage(): void
    {
        $response = $this->submitGiftVoucherSendForm([
            'to_name' => 'Tom Bombadil',
            'email' => 'friend@example.com',
            'amount' => '',
            'message' => '</textarea><script>alert(1)</script>',
        ])->assertOk()
            ->assertSee('amount does not appear to be correct')
            ->assertSee('&lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->content);
    }

    #[Test]
    public function confirmationPageAndEmailEscapeRecipientNameAndMessage(): void
    {
        $this->enableEmailArchive();

        $confirmation = $this->submitGiftVoucherSendForm([
            'to_name' => '<b>Tom</b>',
            'email' => 'friend@example.com',
            'amount' => '25',
            'message' => 'Enjoy<script>alert(1)</script>',
        ])->assertOk()
            ->assertSee('Send Gift Certificate Confirmation')
            ->assertSee('Enjoy&lt;script&gt;alert(1)&lt;/script&gt;');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $confirmation->content);
        $this->assertStringNotContainsString('<b>Tom</b>', $confirmation->content);

        // the confirmation form posts the original message back, not its escaped form
        $this->assertSame('Enjoy<script>alert(1)</script>', $confirmation->formDefaults('gv_send_process')['message'] ?? null);

        $this->confirmGiftVoucherSend($confirmation)
            ->assertOk()
            ->assertSee('Gift Certificate Sent');

        $email = $this->latestArchivedEmail('gv_send');
        $this->assertNotNull($email, 'The voucher email should have been archived.');

        $html = (string) $email['email_html'];
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Tom</b>', $html);
        $this->assertStringContainsString('Enjoy&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    #[Test]
    #[DataProvider('recipientNamesWithLineBreaks')]
    public function sendStepRejectsRecipientNameWithLineBreak(string $toName): void
    {
        $data = $this->validSendData();
        $data['to_name'] = $toName;

        $response = $this->submitGiftVoucherSendForm($data)
            ->assertOk()
            ->assertSee('We did not get the Recipient');

        $this->assertStringNotContainsString('Send Gift Certificate Confirmation', $response->content);
    }

    /**
     * zen_mail() silently refuses a recipient name containing CR/LF, so accepting one would debit the
     * balance and create a voucher that is never emailed.
     */
    #[Test]
    #[DataProvider('recipientNamesWithLineBreaks')]
    public function processRejectsRecipientNameWithLineBreak(string $toName): void
    {
        $data = $this->validSendData();
        $data['to_name'] = $toName;

        $this->assertProcessRejected($data, 'We did not get the Recipient');
    }

    public static function recipientNamesWithLineBreaks(): array
    {
        return [
            'header injection' => ["Tom\r\nBcc: victim@example.com"],
            'trailing newline' => ["Tom Bombadil\n"],
            'carriage return only' => ["Tom\rBombadil"],
        ];
    }

    private function validSendData(): array
    {
        return ['to_name' => 'Tom Bombadil', 'email' => 'friend@example.com', 'amount' => '10', 'message' => 'Enjoy'];
    }

    /**
     * Mail is handed to the test framework's fake sendmail (see Support/application_testing.php) and archived.
     */
    private function enableEmailArchive(): void
    {
        $this->setConfiguration('SEND_EMAILS', 'true');
        $this->setConfiguration('EMAIL_TRANSPORT', 'sendmail');
        $this->setConfiguration('EMAIL_ARCHIVE', 'true');
        $this->setConfiguration('EMAIL_USE_HTML', 'true');
    }

    private function latestArchivedEmail(string $module): ?array
    {
        return TestDb::selectOne(
            'SELECT email_html, email_text FROM email_archive WHERE module = :module ORDER BY archive_id DESC LIMIT 1',
            [':module' => $module]
        );
    }

    private function assertProcessRejected(array $data, string $expectedError): void
    {
        $couponsBefore = $this->giftVoucherCouponCount();
        $tracksBefore = $this->emailTrackCount();

        $response = $this->postProcessDirectly($data)
            ->assertOk()
            ->assertSee($expectedError);

        $this->assertStringNotContainsString('Gift Certificate Sent', $response->content);
        $this->assertSame($couponsBefore, $this->giftVoucherCouponCount(), 'No voucher should be created.');
        $this->assertSame($tracksBefore, $this->emailTrackCount(), 'No voucher email should be tracked.');
        $this->assertSame('100.0000', $this->balance(), 'Balance should be unchanged.');
    }

    /**
     * Posts to action=process without going through the send/confirm pages, carrying only a valid CSRF token.
     */
    private function postProcessDirectly(array $data): FeatureResponse
    {
        $token = $this->visitGiftVoucherSend()
            ->assertOk()
            ->formDefaults('gv_send_send')['securityToken'] ?? '';

        $response = $this->postSsl(
            '/index.php?main_page=gv_send&action=process',
            array_merge(['securityToken' => $token], $data)
        );

        return $response->isRedirect() ? $this->followRedirect($response) : $response;
    }

    private function giftVoucherCouponCount(): int
    {
        return (int) TestDb::selectValue("SELECT COUNT(*) FROM coupons WHERE coupon_type = 'G'");
    }

    private function emailTrackCount(): int
    {
        return (int) TestDb::selectValue(
            'SELECT COUNT(*) FROM coupon_email_track WHERE customer_id_sent = :customer_id',
            [':customer_id' => $this->customerId]
        );
    }

    private function latestEmailTrack(): ?array
    {
        return TestDb::selectOne(
            'SELECT t.emailed_to, c.coupon_amount
               FROM coupon_email_track t
               JOIN coupons c ON c.coupon_id = t.coupon_id
              WHERE t.customer_id_sent = :customer_id
              ORDER BY t.unique_id DESC
              LIMIT 1',
            [':customer_id' => $this->customerId]
        );
    }

    private function balance(): string
    {
        $amount = TestDb::selectValue(
            'SELECT amount FROM coupon_gv_customer WHERE customer_id = :customer_id',
            [':customer_id' => $this->customerId]
        );

        return number_format((float) $amount, 4, '.', '');
    }
}
