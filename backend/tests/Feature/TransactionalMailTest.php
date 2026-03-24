<?php

namespace Tests\Feature;

use App\Mail\AccountSecurityNoticeMail;
use App\Mail\WelcomeRegisteredUserMail;
use App\Models\User;
use App\Services\Mail\TransactionalMailService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TransactionalMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => config('app.url')];
    }

    public function test_password_reset_mail_message_uses_blade_views_and_spa_url(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'guest' => false,
        ]);

        $service = app(TransactionalMailService::class);
        $message = $service->buildPasswordResetMailMessage($user, 'plain-token');

        $this->assertInstanceOf(MailMessage::class, $message);
        $this->assertSame('Скидання пароля', $message->subject);
        $this->assertIsArray($message->view);
        $this->assertSame('mail.password-reset', $message->view['html']);
        $this->assertSame('mail.password-reset-text', $message->view['text']);
        $this->assertStringContainsString('/reset-password?token=', (string) $message->viewData['resetUrl']);
        $this->assertStringContainsString('email=', (string) $message->viewData['resetUrl']);
        $this->assertStringContainsString(urlencode('plain-token'), (string) $message->viewData['resetUrl']);
    }

    public function test_register_queues_welcome_mailable(): void
    {
        Mail::fake();

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/register', [
                'user_name' => 'MailWelcomeUser',
                'email' => 'welcome@example.com',
                'password' => 'password-secure-1',
                'password_confirmation' => 'password-secure-1',
            ])
            ->assertCreated();

        Mail::assertQueued(WelcomeRegisteredUserMail::class, function (WelcomeRegisteredUserMail $mailable): bool {
            return $mailable->user->user_name === 'MailWelcomeUser';
        });
    }

    public function test_transactional_service_queues_account_security_notice(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'sec@example.com',
            'guest' => false,
        ]);

        app(TransactionalMailService::class)->sendAccountSecurityNotice(
            $user,
            'Перевірка безпеки',
            'Це тестове повідомлення для облікового запису.',
        );

        Mail::assertQueued(AccountSecurityNoticeMail::class, function (AccountSecurityNoticeMail $mailable) use ($user): bool {
            return $mailable->user->is($user)
                && $mailable->headline === 'Перевірка безпеки';
        });
    }
}
