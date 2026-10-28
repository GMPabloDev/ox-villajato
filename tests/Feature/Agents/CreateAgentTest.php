<?php

namespace Tests\Feature\Agents;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_agent_creation_screen(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('panel.agents.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('panel/agents/create'));
    }

    public function test_admin_creates_agent_account_and_sends_password_link(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('panel.agents.store'), [
            'name' => 'Ana Torres',
            'email' => 'ana@example.com',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('panel.agents.create'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Cuenta creada. Enviamos un enlace al correo del agente.',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Ana Torres',
            'email' => 'ana@example.com',
            'role' => 'agent',
        ]);

        $agent = User::where('email', 'ana@example.com')->sole();

        $this->assertNotNull($agent->email_verified_at);
        Notification::assertSentTo($agent, ResetPassword::class);
    }

    public function test_admin_sees_an_error_and_no_account_is_created_when_the_invitation_fails(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_THROTTLED);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('panel.agents.store'), [
            'name' => 'Ana Torres',
            'email' => 'ana@example.com',
        ]);

        $response
            ->assertRedirect(route('panel.agents.create'))
            ->assertInertiaFlash('toast', [
                'type' => 'error',
                'message' => 'No pudimos crear la cuenta. Inténtalo de nuevo.',
            ]);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_duplicate_email_is_rejected_regardless_of_case(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('panel.agents.store'), [
            'name' => 'Ana Torres',
            'email' => 'ana@example.com',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('panel.agents.create'))
            ->post(route('panel.agents.store'), [
                'name' => 'Otra Ana',
                'email' => 'ANA@EXAMPLE.COM',
            ]);

        $response
            ->assertRedirect(route('panel.agents.create'))
            ->assertSessionHasErrors([
                'email' => 'Ya existe una cuenta con este correo.',
            ]);

        $this->assertDatabaseCount('users', 2);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, string>}>
     */
    public static function invalidAgentData(): array
    {
        return [
            'missing name and email' => [
                ['name' => '', 'email' => ''],
                [
                    'name' => 'El nombre es obligatorio.',
                    'email' => 'El correo es obligatorio.',
                ],
            ],
            'invalid email' => [
                ['name' => 'Ana Torres', 'email' => 'no-es-un-correo'],
                [
                    'email' => 'El correo no es válido.',
                ],
            ],
            'name is too long' => [
                ['name' => str_repeat('a', 256), 'email' => 'ana@example.com'],
                [
                    'name' => 'El nombre no debe superar 255 caracteres.',
                ],
            ],
            'name is not text' => [
                ['name' => ['Ana'], 'email' => 'ana@example.com'],
                [
                    'name' => 'El nombre debe ser texto.',
                ],
            ],
            'email is too long' => [
                ['name' => 'Ana Torres', 'email' => str_repeat('a', 250).'@example.com'],
                [
                    'email' => 'El correo no debe superar 255 caracteres.',
                ],
            ],
            'email is not text' => [
                ['name' => 'Ana Torres', 'email' => ['ana@example.com']],
                [
                    'email' => 'El correo debe ser texto.',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $errors
     */
    #[DataProvider('invalidAgentData')]
    public function test_invalid_agent_data_shows_errors_and_creates_no_account(
        array $payload,
        array $errors,
    ): void {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('panel.agents.create'))
            ->post(route('panel.agents.store'), $payload);

        $response
            ->assertRedirect(route('panel.agents.create'))
            ->assertSessionHasErrors($errors);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }
}
