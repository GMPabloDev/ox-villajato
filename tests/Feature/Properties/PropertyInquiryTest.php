<?php

namespace Tests\Feature\Properties;

use App\Mail\PropertyInquiry;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PropertyInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_send_an_inquiry_from_the_property_page_and_sees_a_confirmation(): void
    {
        config(['properties.inquiry_recipient' => 'consultas@inmobiliaria.test']);

        Mail::fake();

        $property = Property::factory()->create([
            'title' => 'Casa frente al parque',
        ]);

        $response = $this->post(route('properties.inquiries.store', $property), $this->validPayload());

        $response
            ->assertRedirect(route('properties.show', $property))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Tu consulta fue enviada. Nos pondremos en contacto contigo pronto.',
            ]);

        Mail::assertSent(PropertyInquiry::class, function (PropertyInquiry $mail): bool {
            return $mail->hasTo('consultas@inmobiliaria.test')
                && $mail->hasReplyTo('ana@example.com', 'Ana Pérez');
        });
    }

    public function test_company_email_contains_the_visitor_details_and_the_property_title_and_link(): void
    {
        Mail::fake();

        $property = Property::factory()->create([
            'title' => 'Casa frente al parque',
        ]);

        $this->post(route('properties.inquiries.store', $property), $this->validPayload())
            ->assertRedirect(route('properties.show', $property));

        $emailHtml = Mail::sent(PropertyInquiry::class)->sole()->render();

        $this->assertStringContainsString('Ana Pérez', $emailHtml);
        $this->assertStringContainsString('ana@example.com', $emailHtml);
        $this->assertStringContainsString('+51 987 654 321', $emailHtml);
        $this->assertStringContainsString('Quisiera coordinar una visita.', $emailHtml);
        $this->assertStringContainsString('Casa frente al parque', $emailHtml);
        $this->assertStringContainsString(route('properties.show', $property), $emailHtml);
    }

    public function test_company_email_omits_the_phone_when_it_is_not_provided(): void
    {
        Mail::fake();

        $property = Property::factory()->create([
            'title' => 'Casa frente al parque',
        ]);

        $this->post(route('properties.inquiries.store', $property), $this->validPayload(['phone' => '']))
            ->assertRedirect(route('properties.show', $property));

        $emailHtml = Mail::sent(PropertyInquiry::class)->sole()->render();

        $this->assertStringContainsString('Ana Pérez', $emailHtml);
        $this->assertStringNotContainsString('Teléfono', $emailHtml);
    }

    public function test_company_email_escapes_dangerous_visitor_content(): void
    {
        Mail::fake();

        $property = Property::factory()->create([
            'title' => 'Casa frente al parque',
        ]);

        $dangerousContent = "<script>alert('xss')</script>";

        $this->post(route('properties.inquiries.store', $property), $this->validPayload([
            'name' => 'Ana '.$dangerousContent,
            'message' => 'Mensaje '.$dangerousContent,
        ]))->assertRedirect(route('properties.show', $property));

        $emailHtml = Mail::sent(PropertyInquiry::class)->sole()->render();

        $this->assertStringContainsString('&lt;script&gt;', $emailHtml);
        $this->assertStringNotContainsString($dangerousContent, $emailHtml);
    }

    public function test_inquiry_is_rejected_when_name_email_or_message_is_missing_without_sending_an_email(): void
    {
        Mail::fake();

        $property = Property::factory()->create();

        $response = $this->from(route('properties.show', $property))
            ->post(route('properties.inquiries.store', $property));

        $response
            ->assertRedirect(route('properties.show', $property))
            ->assertSessionHasErrors([
                'name' => 'El campo nombre es obligatorio.',
                'email' => 'El campo email es obligatorio.',
                'message' => 'El campo mensaje es obligatorio.',
            ]);

        Mail::assertNothingSent();
    }

    public function test_inquiry_is_rejected_when_email_has_an_invalid_format_without_sending_an_email(): void
    {
        Mail::fake();

        $property = Property::factory()->create();

        $response = $this->from(route('properties.show', $property))
            ->post(route('properties.inquiries.store', $property), $this->validPayload([
                'email' => 'ana-arroba-example',
            ]));

        $response
            ->assertRedirect(route('properties.show', $property))
            ->assertSessionHasErrors([
                'email' => 'El campo email debe ser una dirección de correo válida.',
            ]);

        Mail::assertNothingSent();
    }

    public function test_inquiry_is_rejected_when_phone_has_an_invalid_format_without_sending_an_email(): void
    {
        Mail::fake();

        $property = Property::factory()->create();

        $response = $this->from(route('properties.show', $property))
            ->post(route('properties.inquiries.store', $property), $this->validPayload([
                'phone' => 'no-es-telefono',
            ]));

        $response
            ->assertRedirect(route('properties.show', $property))
            ->assertSessionHasErrors([
                'phone' => 'El campo teléfono no tiene un formato válido.',
            ]);

        Mail::assertNothingSent();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function overlongValues(): array
    {
        return [
            'name' => ['name', str_repeat('a', 256), 'El campo nombre no debe superar 255 caracteres.'],
            'email' => ['email', str_repeat('a', 244).'@example.com', 'El campo email no debe superar 255 caracteres.'],
            'phone' => ['phone', str_repeat('9', 21), 'El campo teléfono no debe superar 20 caracteres.'],
        ];
    }

    #[DataProvider('overlongValues')]
    public function test_inquiry_is_rejected_when_fields_exceed_their_maximum_length_without_sending_an_email(
        string $field,
        string $value,
        string $message,
    ): void {
        Mail::fake();

        $property = Property::factory()->create();

        $response = $this->from(route('properties.show', $property))
            ->post(
                route('properties.inquiries.store', $property),
                $this->validPayload([$field => $value]),
            );

        $response
            ->assertRedirect(route('properties.show', $property))
            ->assertSessionHasErrors([$field => $message]);

        Mail::assertNothingSent();
    }

    public function test_inquiry_is_rate_limited_per_ip_and_shows_a_wait_notice(): void
    {
        config(['properties.inquiry_rate_limit' => 2]);

        Mail::fake();

        $property = Property::factory()->create();

        $this->sendInquiryFrom('198.51.100.10', $property)
            ->assertRedirect(route('properties.show', $property));

        $this->sendInquiryFrom('198.51.100.10', $property)
            ->assertRedirect(route('properties.show', $property));

        $this->sendInquiryFrom('198.51.100.10', $property)
            ->assertRedirect(route('properties.show', $property))
            ->assertInertiaFlash('toast', [
                'type' => 'warning',
                'message' => 'Has enviado varias consultas seguidas. Espera un momento antes de intentarlo de nuevo.',
            ]);

        $this->sendInquiryFrom('198.51.100.11', $property)
            ->assertRedirect(route('properties.show', $property))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Tu consulta fue enviada. Nos pondremos en contacto contigo pronto.',
            ]);

        Mail::assertSent(PropertyInquiry::class, 3);
    }

    private function sendInquiryFrom(string $ip, Property $property): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('properties.inquiries.store', $property), $this->validPayload());
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ], $overrides);
    }
}
