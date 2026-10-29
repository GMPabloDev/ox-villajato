<?php

namespace Tests\Feature\Properties;

use App\Mail\PropertyInquiry;
use App\Models\Property;
use App\Models\PropertyInquiry as PropertyInquiryModel;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
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

        $this->assertDatabaseHas('property_inquiries', [
            'property_id' => $property->id,
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ]);

        Mail::assertSent(PropertyInquiry::class, function (PropertyInquiry $mail): bool {
            return $mail->hasTo('consultas@inmobiliaria.test')
                && $mail->hasReplyTo('ana@example.com', 'Ana Pérez');
        });
    }

    public function test_mail_failure_keeps_saved_inquiry_and_confirms_submission(): void
    {
        $property = Property::factory()->create();

        Exceptions::fake();

        Mail::expects('send')
            ->andThrow(new RuntimeException('Mail delivery failed.'));

        $response = $this->post(route('properties.inquiries.store', $property), $this->validPayload());

        $this->assertDatabaseHas('property_inquiries', [
            'property_id' => $property->id,
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ]);

        $response
            ->assertRedirect(route('properties.show', $property))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Tu consulta fue enviada. Nos pondremos en contacto contigo pronto.',
            ]);

        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_agent_sees_their_inquiry_property_and_visitor_details(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa frente al parque',
        ]);
        $expectedReceivedAt = new DateTimeImmutable('2026-10-28T14:30:00+00:00');
        $inquiry = PropertyInquiryModel::factory()->for($property)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
            'created_at' => '2026-10-28 14:30:00',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.inquiries.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('canSeeAllInquiries', false)
                ->where('inquiries.data.0.property.title', 'Casa frente al parque')
                ->where('inquiries.data.0.name', 'Ana Pérez')
                ->where('inquiries.data.0.email', 'ana@example.com')
                ->where('inquiries.data.0.phone', '+51 987 654 321')
                ->where('inquiries.data.0.message', 'Quisiera coordinar una visita.')
                ->where(
                    'inquiries.data.0.received_at',
                    fn (string $receivedAt): bool => (new DateTimeImmutable($receivedAt))->getTimestamp() === $expectedReceivedAt->getTimestamp(),
                )
            );
    }

    public function test_admin_sees_inquiries_for_all_properties_with_visitor_details(): void
    {
        $admin = User::factory()->admin()->create();
        $firstAgent = User::factory()->agent()->create();
        $secondAgent = User::factory()->agent()->create();

        $adminProperty = Property::factory()->for($admin)->create([
            'title' => 'Propiedad del administrador',
        ]);
        $firstAgentProperty = Property::factory()->for($firstAgent)->create([
            'title' => 'Propiedad de la primera agente',
        ]);
        $secondAgentProperty = Property::factory()->for($secondAgent)->create([
            'title' => 'Propiedad de la segunda agente',
        ]);

        $adminReceivedAt = new DateTimeImmutable('2026-10-28T14:30:00+00:00');
        $firstAgentReceivedAt = new DateTimeImmutable('2026-10-28T14:31:00+00:00');
        $secondAgentReceivedAt = new DateTimeImmutable('2026-10-28T14:32:00+00:00');

        $adminInquiry = PropertyInquiryModel::factory()->for($adminProperty)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Consulta por la propiedad del administrador.',
            'created_at' => $adminReceivedAt->format('Y-m-d H:i:s'),
        ]);
        $firstAgentInquiry = PropertyInquiryModel::factory()->for($firstAgentProperty)->create([
            'name' => 'Luis Rojas',
            'email' => 'luis@example.com',
            'phone' => null,
            'message' => 'Consulta por la propiedad de la primera agente.',
            'created_at' => $firstAgentReceivedAt->format('Y-m-d H:i:s'),
        ]);
        $secondAgentInquiry = PropertyInquiryModel::factory()->for($secondAgentProperty)->create([
            'name' => 'Carla Torres',
            'email' => 'carla@example.com',
            'phone' => '+51 999 123 456',
            'message' => 'Consulta por la propiedad de la segunda agente.',
            'created_at' => $secondAgentReceivedAt->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($admin)->get(route('panel.inquiries.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 3)
                ->where('canSeeAllInquiries', true)
                ->where('inquiries.data.0.id', $secondAgentInquiry->id)
                ->where('inquiries.data.0.property.title', $secondAgentProperty->title)
                ->where('inquiries.data.0.name', $secondAgentInquiry->name)
                ->where('inquiries.data.0.email', $secondAgentInquiry->email)
                ->where('inquiries.data.0.phone', $secondAgentInquiry->phone)
                ->where('inquiries.data.0.message', $secondAgentInquiry->message)
                ->where(
                    'inquiries.data.0.received_at',
                    fn (string $receivedAt): bool => (new DateTimeImmutable($receivedAt))->getTimestamp() === $secondAgentReceivedAt->getTimestamp(),
                )
                ->where('inquiries.data.1.id', $firstAgentInquiry->id)
                ->where('inquiries.data.1.property.title', $firstAgentProperty->title)
                ->where('inquiries.data.1.name', $firstAgentInquiry->name)
                ->where('inquiries.data.1.email', $firstAgentInquiry->email)
                ->where('inquiries.data.1.phone', $firstAgentInquiry->phone)
                ->where('inquiries.data.1.message', $firstAgentInquiry->message)
                ->where(
                    'inquiries.data.1.received_at',
                    fn (string $receivedAt): bool => (new DateTimeImmutable($receivedAt))->getTimestamp() === $firstAgentReceivedAt->getTimestamp(),
                )
                ->where('inquiries.data.2.id', $adminInquiry->id)
                ->where('inquiries.data.2.property.title', $adminProperty->title)
                ->where('inquiries.data.2.name', $adminInquiry->name)
                ->where('inquiries.data.2.email', $adminInquiry->email)
                ->where('inquiries.data.2.phone', $adminInquiry->phone)
                ->where('inquiries.data.2.message', $adminInquiry->message)
                ->where(
                    'inquiries.data.2.received_at',
                    fn (string $receivedAt): bool => (new DateTimeImmutable($receivedAt))->getTimestamp() === $adminReceivedAt->getTimestamp(),
                )
            );
    }

    public function test_agent_receives_an_empty_inquiry_list_when_no_inquiries_exist(): void
    {
        $agent = User::factory()->agent()->create();

        Property::factory()->for($agent)->create();

        $response = $this->actingAs($agent)->get(route('panel.inquiries.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 0)
            );
    }

    public function test_admin_receives_an_empty_inquiry_list_when_no_inquiries_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('panel.inquiries.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 0)
            );
    }

    public function test_agent_sees_an_inquiry_without_a_phone_number_when_none_was_provided(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create();
        PropertyInquiryModel::factory()->for($property)->create([
            'phone' => null,
        ]);

        $response = $this->actingAs($agent)->get(route('panel.inquiries.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('inquiries.data.0.phone', null)
            );
    }

    public function test_admin_can_paginate_inquiries_across_all_properties(): void
    {
        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();
        $adminProperty = Property::factory()->for($admin)->create();
        $agentProperty = Property::factory()->for($agent)->create();
        $createdAt = '2026-10-28 14:30:00';
        $adminInquiries = PropertyInquiryModel::factory()
            ->count(8)
            ->for($adminProperty)
            ->create(['created_at' => $createdAt]);
        $agentInquiries = PropertyInquiryModel::factory()
            ->count(8)
            ->for($agentProperty)
            ->create(['created_at' => $createdAt]);
        $expectedIds = $adminInquiries->pluck('id')
            ->concat($agentInquiries->pluck('id'))
            ->sort()
            ->reverse()
            ->values()
            ->all();

        $firstPage = $this->actingAs($admin)->get(route('panel.inquiries.index'));

        $firstPage
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 15)
                ->where('inquiries.total', 16)
                ->where('inquiries.current_page', 1)
            );
        $firstPageIds = collect($firstPage->inertiaProps('inquiries.data'))->pluck('id')->all();

        $secondPage = $this->actingAs($admin)->get(route('panel.inquiries.index', ['page' => 2]));

        $secondPage
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('inquiries.total', 16)
                ->where('inquiries.current_page', 2)
            );
        $secondPageIds = collect($secondPage->inertiaProps('inquiries.data'))->pluck('id')->all();
        $actualIds = collect($firstPageIds)
            ->concat($secondPageIds)
            ->values()
            ->all();

        $this->assertSame($expectedIds, $actualIds);
    }

    public function test_agent_can_paginate_their_inquiry_list(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create();
        PropertyInquiryModel::factory()->count(16)->for($property)->create();

        $firstPage = $this->actingAs($agent)->get(route('panel.inquiries.index'));

        $firstPage
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 15)
                ->where('inquiries.total', 16)
                ->where('inquiries.current_page', 1)
            );

        $secondPage = $this->actingAs($agent)->get(route('panel.inquiries.index', ['page' => 2]));

        $secondPage
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('inquiries.total', 16)
                ->where('inquiries.current_page', 2)
            );
    }

    public function test_agent_list_excludes_inquiries_for_other_agents_properties(): void
    {
        $agent = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa propia',
        ]);
        $otherProperty = Property::factory()->for($otherAgent)->create([
            'title' => 'Casa ajena',
        ]);
        $ownInquiry = PropertyInquiryModel::factory()->for($property)->create([
            'name' => 'Ana Pérez',
        ]);
        PropertyInquiryModel::factory()->for($otherProperty)->create([
            'name' => 'Luis Rojas',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.inquiries.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('inquiries.data.0.id', $ownInquiry->id)
                ->where('inquiries.data.0.name', 'Ana Pérez')
            );
    }

    public function test_agent_keeps_inquiries_after_their_properties_are_closed(): void
    {
        $agent = User::factory()->agent()->create();
        $saleProperty = Property::factory()->for($agent)->create([
            'operation' => 'sale',
            'status' => 'available',
            'title' => 'Casa en venta',
        ]);
        $rentProperty = Property::factory()->for($agent)->create([
            'operation' => 'rent',
            'status' => 'available',
            'title' => 'Departamento en alquiler',
        ]);
        $saleInquiry = PropertyInquiryModel::factory()->for($saleProperty)->create([
            'created_at' => '2026-10-28 14:30:00',
        ]);
        $rentInquiry = PropertyInquiryModel::factory()->for($rentProperty)->create([
            'created_at' => '2026-10-28 14:31:00',
        ]);

        $this->actingAs($agent)
            ->patch(route('panel.properties.close', $saleProperty))
            ->assertRedirect(route('panel.properties.index'));

        $this->actingAs($agent)
            ->patch(route('panel.properties.close', $rentProperty))
            ->assertRedirect(route('panel.properties.index'));

        $this->assertDatabaseHas('properties', [
            'id' => $saleProperty->id,
            'status' => 'sold',
        ]);
        $this->assertDatabaseHas('properties', [
            'id' => $rentProperty->id,
            'status' => 'rented',
        ]);

        $response = $this->actingAs($agent)->get(route('panel.inquiries.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->where('inquiries.data', function (Collection $inquiries) use ($saleInquiry, $rentInquiry): bool {
                    $actualIds = $inquiries->pluck('id')->sort()->values()->all();
                    $expectedIds = collect([$saleInquiry->id, $rentInquiry->id])->sort()->values()->all();

                    return $actualIds === $expectedIds;
                })
            );
    }

    public function test_agent_keeps_inquiries_after_archiving_their_property(): void
    {
        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'available',
            'title' => 'Casa archivada',
        ]);
        $inquiry = PropertyInquiryModel::factory()->for($property)->create();

        $this->actingAs($agent)
            ->patch(route('panel.properties.archive', $property))
            ->assertRedirect(route('panel.properties.index'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);

        $this->actingAs($agent)
            ->get(route('panel.inquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('inquiries.data.0.id', $inquiry->id)
                ->where('inquiries.data.0.property.title', 'Casa archivada')
            );
    }

    public function test_admin_keeps_inquiries_after_properties_are_sold_or_rented(): void
    {
        $admin = User::factory()->admin()->create();
        $saleAgent = User::factory()->agent()->create();
        $rentAgent = User::factory()->agent()->create();
        $saleProperty = Property::factory()->for($saleAgent)->create([
            'operation' => 'sale',
            'status' => 'available',
        ]);
        $rentProperty = Property::factory()->for($rentAgent)->create([
            'operation' => 'rent',
            'status' => 'available',
        ]);
        $saleInquiry = PropertyInquiryModel::factory()->for($saleProperty)->create();
        $rentInquiry = PropertyInquiryModel::factory()->for($rentProperty)->create();

        $this->actingAs($saleAgent)
            ->patch(route('panel.properties.close', $saleProperty))
            ->assertRedirect(route('panel.properties.index'));

        $this->actingAs($rentAgent)
            ->patch(route('panel.properties.close', $rentProperty))
            ->assertRedirect(route('panel.properties.index'));

        $this->assertDatabaseHas('properties', [
            'id' => $saleProperty->id,
            'status' => 'sold',
        ]);
        $this->assertDatabaseHas('properties', [
            'id' => $rentProperty->id,
            'status' => 'rented',
        ]);

        $response = $this->actingAs($admin)->get(route('panel.inquiries.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->where('inquiries.data', function (Collection $inquiries) use ($saleInquiry, $rentInquiry): bool {
                    $actualIds = $inquiries->pluck('id')->sort()->values()->all();
                    $expectedIds = collect([$saleInquiry->id, $rentInquiry->id])->sort()->values()->all();

                    return $actualIds === $expectedIds;
                })
            );
    }

    public function test_guest_is_redirected_to_login_when_opening_agent_inquiries(): void
    {
        $this->get(route('panel.inquiries.index'))
            ->assertRedirect(route('login'));
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

    public function test_closed_property_inquiry_returns_not_found_without_sending_an_email(): void
    {
        Mail::fake();

        $property = Property::factory()->create([
            'operation' => 'sale',
            'status' => 'sold',
        ]);

        $this->post(route('properties.inquiries.store', $property), $this->validPayload())
            ->assertNotFound();

        Mail::assertNothingSent();
    }

    public function test_archived_property_inquiry_returns_not_found_without_saving_or_sending_an_email(): void
    {
        Mail::fake();

        $property = Property::factory()->create([
            'status' => 'archived',
        ]);

        $this->post(route('properties.inquiries.store', $property), $this->validPayload())
            ->assertNotFound();

        $this->assertDatabaseCount('property_inquiries', 0);
        Mail::assertNothingSent();
    }

    public function test_closed_property_inquiry_with_invalid_payload_returns_not_found_without_sending_an_email(): void
    {
        Mail::fake();

        $property = Property::factory()->create([
            'operation' => 'sale',
            'status' => 'sold',
        ]);

        $this->post(route('properties.inquiries.store', $property), [])
            ->assertNotFound();

        Mail::assertNothingSent();
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
