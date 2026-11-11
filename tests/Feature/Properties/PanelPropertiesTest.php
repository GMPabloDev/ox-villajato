<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PanelPropertiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_member_sees_panel_list_with_cover_title_price_and_form_data(): void
    {
        $property = Property::factory()->create([
            'title' => 'Casa frente al parque',
            'description' => 'Casa de tres pisos con jardín.',
            'price' => 250000,
            'currency' => 'PEN',
            'district_code' => '150122',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 120.5,
        ]);

        $property->photos()->createMany([
            ['path' => 'properties/cover.jpg', 'position' => 1],
            ['path' => 'properties/second.jpg', 'position' => 2],
        ]);

        $photos = $property->photos()->orderBy('position')->get();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('panel.properties.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->has('properties.data', 1)
                ->where('properties.data.0.id', $property->id)
                ->where('properties.data.0.title', 'Casa frente al parque')
                ->where('properties.data.0.description', 'Casa de tres pisos con jardín.')
                ->where('properties.data.0.price', '250000.00')
                ->where('properties.data.0.price_formatted', 'S/ 250,000')
                ->where('properties.data.0.currency', 'PEN')
                ->where('properties.data.0.department_code', '15')
                ->where('properties.data.0.province_code', '1501')
                ->where('properties.data.0.district_code', '150122')
                ->where('properties.data.0.department', 'Lima')
                ->where('properties.data.0.province', 'Lima')
                ->where('properties.data.0.district', 'Miraflores')
                ->where('properties.data.0.bedrooms', 3)
                ->where('properties.data.0.bathrooms', 2)
                ->where('properties.data.0.area', '120.50')
                ->where('properties.data.0.cover_photo_url', Storage::disk('public')->url('properties/cover.jpg'))
                ->has('properties.data.0.photos', 2)
                ->where('properties.data.0.photos.0.id', $photos[0]->id)
                ->where('properties.data.0.photos.0.url', Storage::disk('public')->url('properties/cover.jpg'))
                ->where('properties.data.0.photos.1.id', $photos[1]->id)
                ->where('properties.data.0.photos.1.url', Storage::disk('public')->url('properties/second.jpg'))
            );
    }

    public function test_panel_list_shows_each_property_status_and_the_action_that_corresponds(): void
    {
        $sale = Property::factory()->create([
            'operation' => 'sale',
            'title' => 'Casa en venta',
            'status' => 'available',
        ]);
        $rent = Property::factory()->create([
            'operation' => 'rent',
            'title' => 'Departamento en alquiler',
            'status' => 'available',
        ]);
        $sold = Property::factory()->create([
            'operation' => 'sale',
            'title' => 'Casa vendida',
            'status' => 'sold',
        ]);
        $rented = Property::factory()->create([
            'operation' => 'rent',
            'title' => 'Departamento alquilado',
            'status' => 'rented',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('panel.properties.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->has('properties.data', 4)
                ->where('properties.data.0.id', $rented->id)
                ->where('properties.data.0.status', 'rented')
                ->where('properties.data.0.status_label', 'Alquilada')
                ->where('properties.data.0.status_action', 'reopen')
                ->where('properties.data.0.status_action_label', 'Volver a disponible')
                ->where('properties.data.1.id', $sold->id)
                ->where('properties.data.1.status', 'sold')
                ->where('properties.data.1.status_label', 'Vendida')
                ->where('properties.data.1.status_action', 'reopen')
                ->where('properties.data.1.status_action_label', 'Volver a disponible')
                ->where('properties.data.2.id', $rent->id)
                ->where('properties.data.2.status', 'available')
                ->where('properties.data.2.status_label', 'Disponible')
                ->where('properties.data.2.status_action', 'close')
                ->where('properties.data.2.status_action_label', 'Marcar como alquilada')
                ->where('properties.data.3.id', $sale->id)
                ->where('properties.data.3.status', 'available')
                ->where('properties.data.3.status_label', 'Disponible')
                ->where('properties.data.3.status_action', 'close')
                ->where('properties.data.3.status_action_label', 'Marcar como vendida')
            );
    }

    public function test_panel_list_is_empty_when_there_are_no_properties(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('panel.properties.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->has('properties.data', 0)
            );
    }

    public function test_panel_list_preloads_the_data_and_ordered_photos_for_the_edit_sheet(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'type' => 'house',
            'title' => 'Casa frente al parque',
            'description' => 'Casa de tres pisos con jardín.',
            'price' => 250000,
            'currency' => 'PEN',
            'district_code' => '150122',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 120.5,
        ]);

        $property->photos()->createMany([
            ['path' => 'properties/living.jpg', 'position' => 2],
            ['path' => 'properties/cover.jpg', 'position' => 1],
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('panel.properties.index'));

        $photos = $property->photos()->orderBy('position')->get();

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/index')
                ->where('properties.data.0.id', $property->id)
                ->where('properties.data.0.operation', 'sale')
                ->where('properties.data.0.type', 'house')
                ->where('properties.data.0.title', 'Casa frente al parque')
                ->where('properties.data.0.description', 'Casa de tres pisos con jardín.')
                ->where('properties.data.0.price', '250000.00')
                ->where('properties.data.0.currency', 'PEN')
                ->where('properties.data.0.department_code', '15')
                ->where('properties.data.0.province_code', '1501')
                ->where('properties.data.0.district_code', '150122')
                ->where('properties.data.0.department', 'Lima')
                ->where('properties.data.0.province', 'Lima')
                ->where('properties.data.0.district', 'Miraflores')
                ->where('properties.data.0.bedrooms', 3)
                ->where('properties.data.0.bathrooms', 2)
                ->where('properties.data.0.area', '120.50')
                ->has('properties.data.0.photos', 2)
                ->where('properties.data.0.photos.0.id', $photos[0]->id)
                ->where('properties.data.0.photos.0.url', Storage::disk('public')->url('properties/cover.jpg'))
                ->where('properties.data.0.photos.0.position', 1)
                ->where('properties.data.0.photos.1.id', $photos[1]->id)
                ->where('properties.data.0.photos.1.url', Storage::disk('public')->url('properties/living.jpg'))
                ->where('properties.data.0.photos.1.position', 2)
            );
    }

    public function test_member_can_update_property_data_and_sees_it_on_the_public_page_and_catalog(): void
    {
        $property = Property::factory()->create([
            'title' => 'Casa frente al parque',
            'price' => 250000,
            'currency' => 'PEN',
            'district_code' => '150122',
        ]);

        $property->photos()->createMany([
            ['path' => 'properties/cover.jpg', 'position' => 1],
            ['path' => 'properties/living.jpg', 'position' => 2],
        ]);

        $photoIds = $property->photos()->orderBy('position')->pluck('id')->all();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                'operation' => 'rent',
                'type' => 'apartment',
                'title' => 'Departamento renovado',
                'description' => 'Descripción actualizada.',
                'price' => 1500,
                'currency' => 'USD',
                ...$this->propertyLocation('040103'),
                'bedrooms' => 2,
                'bathrooms' => 1,
                'area' => 95.5,
                'photo_order' => $photoIds,
            ]);

        $response
            ->assertRedirect(route('properties.show', $property))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Propiedad actualizada.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'operation' => 'rent',
            'type' => 'apartment',
            'title' => 'Departamento renovado',
            'description' => 'Descripción actualizada.',
            'price' => 1500,
            'currency' => 'USD',
            'district_code' => '040103',
            'bedrooms' => 2,
            'bathrooms' => 1,
            'area' => 95.5,
        ]);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('property.title', 'Departamento renovado')
                ->where('property.price_formatted', 'US$ 1,500')
            );

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('properties.0.title', 'Departamento renovado')
                ->where('properties.0.price_formatted', 'US$ 1,500')
            );
    }

    public function test_editing_a_closed_property_without_changing_operation_keeps_it_closed_and_out_of_the_catalog(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'title' => 'Casa frente al parque',
            'district_code' => '150122',
            'status' => 'sold',
        ]);

        $property->photos()->create(['path' => 'properties/cover.jpg', 'position' => 1]);
        $photoId = $property->photos()->sole()->id;

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'title' => 'Casa frente al parque remodelada',
                    'description' => 'Descripción actualizada.',
                    'price' => 260000,
                    'currency' => 'PEN',
                ]),
                'photo_order' => [$photoId],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Propiedad actualizada.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => 'Casa frente al parque remodelada',
            'status' => 'sold',
        ]);

        $this->get(route('properties.show', $property))->assertNotFound();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 0)
            );

        $this->get(route('panel.properties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('properties.data.0.id', $property->id)
                ->where('properties.data.0.status_label', 'Vendida')
            );
    }

    public function test_changing_the_operation_when_editing_reopens_the_property_in_the_catalog(): void
    {
        $property = Property::factory()->create([
            'operation' => 'sale',
            'title' => 'Casa frente al parque',
            'district_code' => '150122',
            'status' => 'sold',
        ]);

        $property->photos()->create(['path' => 'properties/cover.jpg', 'position' => 1]);
        $photoId = $property->photos()->sole()->id;

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'operation' => 'rent',
                    'title' => 'Casa frente al parque en alquiler',
                    'description' => 'Descripción actualizada.',
                    'price' => 1500,
                    'currency' => 'USD',
                ]),
                'photo_order' => [$photoId],
            ]);

        $response
            ->assertRedirect(route('properties.show', $property))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => 'Propiedad actualizada.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'operation' => 'rent',
            'status' => 'available',
        ]);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('property.title', 'Casa frente al parque en alquiler')
                ->where('property.operation_label', 'Alquiler')
            );

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('properties', 1)
                ->where('properties.0.id', $property->id)
            );
    }

    public function test_member_can_add_and_remove_photos_keeping_order_and_cover(): void
    {
        Storage::fake('public');

        $property = Property::factory()->create();

        $property->photos()->createMany([
            ['path' => 'properties/keep.jpg', 'position' => 1],
            ['path' => 'properties/remove.jpg', 'position' => 2],
        ]);

        Storage::disk('public')->put('properties/keep.jpg', 'keep');
        Storage::disk('public')->put('properties/remove.jpg', 'remove');

        $keptId = $property->photos()->where('path', 'properties/keep.jpg')->sole()->id;
        $removedPath = 'properties/remove.jpg';

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property),
                'photo_order' => [$keptId],
                'photos' => [
                    UploadedFile::fake()->image('new.jpg'),
                ],
            ]);

        $response->assertRedirect(route('properties.show', $property));

        $this->assertDatabaseMissing('property_photos', ['property_id' => $property->id, 'path' => $removedPath]);
        Storage::disk('public')->assertMissing($removedPath);

        $photos = $property->refresh()->photos;
        $this->assertCount(2, $photos);
        $this->assertSame('properties/keep.jpg', $photos[0]->path);
        $this->assertSame(1, $photos[0]->position);
        $this->assertStringEndsWith('.jpg', $photos[1]->path);
        $this->assertSame(2, $photos[1]->position);
        $this->assertStoredPhotos([
            'properties/keep.jpg',
            $photos[1]->path,
        ]);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('property.photos', 2)
                ->where('property.cover_photo_url', Storage::disk('public')->url('properties/keep.jpg'))
            );

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('properties.0.cover_photo_url', Storage::disk('public')->url('properties/keep.jpg'))
            );
    }

    public function test_member_can_replace_all_photos_with_new_ones(): void
    {
        Storage::fake('public');

        $property = Property::factory()->create();

        $property->photos()->createMany([
            ['path' => 'properties/old-one.jpg', 'position' => 1],
            ['path' => 'properties/old-two.jpg', 'position' => 2],
        ]);

        Storage::disk('public')->put('properties/old-one.jpg', 'one');
        Storage::disk('public')->put('properties/old-two.jpg', 'two');

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property),
                // El formulario real no envía photo_order cuando no queda ninguna foto.
                'photos' => [UploadedFile::fake()->image('new.jpg')],
            ]);

        $response->assertRedirect(route('properties.show', $property));

        $photos = $property->refresh()->photos;
        $this->assertCount(1, $photos);
        $this->assertStringEndsWith('.jpg', $photos[0]->path);
        $this->assertSame(1, $photos[0]->position);
        Storage::disk('public')->assertMissing('properties/old-one.jpg');
        Storage::disk('public')->assertMissing('properties/old-two.jpg');
        $this->assertStoredPhotos([$photos[0]->path]);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('property.photos', 1)
                ->where('property.cover_photo_url', Storage::disk('public')->url($photos[0]->path))
            );

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('properties.0.cover_photo_url', Storage::disk('public')->url($photos[0]->path))
            );
    }

    public function test_reordering_photos_changes_the_catalog_cover(): void
    {
        $property = Property::factory()->create();

        $property->photos()->createMany([
            ['path' => 'properties/first.jpg', 'position' => 1],
            ['path' => 'properties/second.jpg', 'position' => 2],
            ['path' => 'properties/third.jpg', 'position' => 3],
        ]);

        $ids = $property->photos()->orderBy('position')->pluck('id')->all();
        $reordered = [$ids[2], $ids[0], $ids[1]];

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property),
                'photo_order' => $reordered,
            ]);

        $response->assertRedirect(route('properties.show', $property));

        $photos = $property->refresh()->photos;
        $this->assertSame($reordered, $photos->pluck('id')->all());
        $this->assertSame([1, 2, 3], $photos->pluck('position')->all());

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('property.cover_photo_url', Storage::disk('public')->url('properties/third.jpg'))
                ->where('property.photos.0.url', Storage::disk('public')->url('properties/third.jpg'))
                ->where('property.photos.1.url', Storage::disk('public')->url('properties/first.jpg'))
                ->where('property.photos.2.url', Storage::disk('public')->url('properties/second.jpg'))
            );

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('properties.0.cover_photo_url', Storage::disk('public')->url('properties/third.jpg'))
            );
    }

    public function test_member_can_keep_exactly_eight_photos(): void
    {
        Storage::fake('public');

        $property = $this->propertyWithPhotos(8);
        $ids = $property->photos()->orderBy('position')->pluck('id')->all();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property),
                'photo_order' => $ids,
            ]);

        $response->assertRedirect(route('properties.show', $property));

        $photos = $property->refresh()->photos;
        $this->assertSame($ids, $photos->pluck('id')->all());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], $photos->pluck('position')->all());
        $this->assertStoredPhotos($photos->pluck('path')->all());
    }

    public function test_update_with_invalid_data_keeps_everything_unchanged(): void
    {
        Storage::fake('public');

        $property = Property::factory()->create([
            'title' => 'Casa frente al parque',
            'price' => 250000,
        ]);

        $property->photos()->createMany([
            ['path' => 'properties/first.jpg', 'position' => 1],
            ['path' => 'properties/second.jpg', 'position' => 2],
        ]);

        Storage::disk('public')->put('properties/first.jpg', 'first');
        Storage::disk('public')->put('properties/second.jpg', 'second');

        $photoIds = $property->photos()->orderBy('position')->pluck('id')->all();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'title' => '',
                    'price' => -5,
                ]),
                'photo_order' => $photoIds,
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors([
                'title' => 'El campo título es obligatorio.',
                'price' => 'El campo precio debe ser mayor que cero.',
            ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => 'Casa frente al parque',
            'price' => 250000,
        ]);

        $this->assertPhotosUnchanged($property, $photoIds);
    }

    public function test_update_without_photos_is_rejected_without_changes(): void
    {
        Storage::fake('public');

        $property = $this->propertyWithPhotos(2);
        $photoIds = $property->photos()->orderBy('position')->pluck('id')->all();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), $this->scalarPayload($property, [
                'title' => 'No debe guardarse',
            ]));

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photos' => 'Debes adjuntar al menos una foto.']);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => $property->title,
        ]);

        $this->assertPhotosUnchanged($property, $photoIds);
    }

    public function test_update_with_more_than_eight_photos_is_rejected_without_changes(): void
    {
        Storage::fake('public');

        $property = $this->propertyWithPhotos(8);
        $photoIds = $property->photos()->orderBy('position')->pluck('id')->all();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'title' => 'No debe guardarse',
                ]),
                'photo_order' => $photoIds,
                'photos' => [UploadedFile::fake()->image('extra.jpg')],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photos' => 'Puedes adjuntar como máximo 8 fotos.']);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => $property->title,
        ]);

        $this->assertPhotosUnchanged($property, $photoIds);
    }

    public function test_update_with_an_unsupported_photo_format_is_rejected_without_changes(): void
    {
        Storage::fake('public');

        $property = $this->propertyWithPhotos(1);
        $photoIds = $property->photos()->orderBy('position')->pluck('id')->all();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'title' => 'No debe guardarse',
                ]),
                'photo_order' => $photoIds,
                'photos' => [UploadedFile::fake()->image('photo.gif')],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photos.0' => 'Cada foto debe ser JPG, PNG o WebP.']);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => $property->title,
        ]);

        $this->assertPhotosUnchanged($property, $photoIds);
    }

    public function test_update_with_a_photo_over_five_megabytes_is_rejected_without_changes(): void
    {
        Storage::fake('public');

        $property = $this->propertyWithPhotos(1);
        $photoIds = $property->photos()->orderBy('position')->pluck('id')->all();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'title' => 'No debe guardarse',
                ]),
                'photo_order' => $photoIds,
                'photos' => [UploadedFile::fake()->image('heavy.jpg')->size(5121)],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photos.0' => 'Cada foto debe pesar como máximo 5 MB.']);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => $property->title,
        ]);

        $this->assertPhotosUnchanged($property, $photoIds);
    }

    public function test_update_rejects_a_photo_belonging_to_another_property(): void
    {
        Storage::fake('public');

        $property = $this->propertyWithPhotos(1);
        $other = $this->propertyWithPhotos(1);

        $otherPhotoId = $other->photos()->sole()->id;
        $photoIds = $property->photos()->pluck('id')->all();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'title' => 'No debe guardarse',
                ]),
                'photo_order' => [$otherPhotoId],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photo_order.0' => 'Una de las fotos seleccionadas no es válida.']);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => $property->title,
        ]);

        $storedPaths = [
            ...$property->photos()->pluck('path')->all(),
            ...$other->photos()->pluck('path')->all(),
        ];

        $this->assertPhotosUnchanged($property, $photoIds, $storedPaths);
        $this->assertPhotosUnchanged($other, [$otherPhotoId], $storedPaths);
    }

    public function test_update_rejects_duplicate_photo_ids(): void
    {
        Storage::fake('public');

        $property = $this->propertyWithPhotos(1);
        $photoId = $property->photos()->sole()->id;

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'title' => 'No debe guardarse',
                ]),
                'photo_order' => [$photoId, $photoId],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photo_order.0' => 'Cada foto debe aparecer una sola vez.']);

        $this->assertPhotosUnchanged($property, [$photoId]);
    }

    public function test_update_rejects_non_sequential_photo_order_keys(): void
    {
        Storage::fake('public');

        $property = $this->propertyWithPhotos(1);
        $photoId = $property->photos()->sole()->id;

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('panel.properties.index'))
            ->put(route('panel.properties.update', $property), [
                ...$this->scalarPayload($property, [
                    'title' => 'No debe guardarse',
                ]),
                'photo_order' => [2 => $photoId],
            ]);

        $response
            ->assertRedirect(route('panel.properties.index'))
            ->assertSessionHasErrors(['photo_order' => 'El campo fotos debe ser una lista ordenada.']);

        $this->assertPhotosUnchanged($property, [$photoId]);
    }

    public function test_guest_is_redirected_to_login_for_panel_list_and_update(): void
    {
        $property = Property::factory()->create();
        $property->photos()->create(['path' => 'properties/cover.jpg', 'position' => 1]);

        $this->get(route('panel.properties.index'))->assertRedirect(route('login'));
        $this->put(route('panel.properties.update', $property))->assertRedirect(route('login'));
        $this->patch(route('panel.properties.close', $property))->assertRedirect(route('login'));
        $this->patch(route('panel.properties.reopen', $property))->assertRedirect(route('login'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'available',
        ]);
        $this->assertDatabaseCount('property_photos', 1);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function scalarPayload(Property $property, array $overrides = []): array
    {
        return array_merge([
            'operation' => $property->operation->value,
            'type' => $property->type->value,
            'title' => $property->title,
            'description' => $property->description,
            'price' => $property->price,
            'currency' => $property->currency->value,
            ...$this->propertyLocation($property->district_code),
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'area' => $property->area,
        ], $overrides);
    }

    private function propertyWithPhotos(int $count): Property
    {
        $property = Property::factory()->create();

        $rows = [];
        foreach (range(1, $count) as $index) {
            $path = "properties/{$property->id}-{$index}.jpg";
            $rows[] = ['path' => $path, 'position' => $index];
            Storage::disk('public')->put($path, 'photo');
        }

        $property->photos()->createMany($rows);

        return $property;
    }

    /**
     * @param  array<int, int>  $photoIds
     * @param  array<int, string>|null  $storedPaths
     */
    private function assertPhotosUnchanged(Property $property, array $photoIds, ?array $storedPaths = null): void
    {
        $photos = $property->refresh()->photos;

        $this->assertSame($photoIds, $photos->pluck('id')->all());
        $this->assertSame(range(1, count($photoIds)), $photos->pluck('position')->all());
        $this->assertStoredPhotos($storedPaths ?? $photos->pluck('path')->all());
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function assertStoredPhotos(array $paths): void
    {
        $this->assertEqualsCanonicalizing($paths, Storage::disk('public')->allFiles('properties'));
    }
}
