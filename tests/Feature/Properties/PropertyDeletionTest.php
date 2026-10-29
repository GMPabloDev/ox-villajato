<?php

namespace Tests\Feature\Properties;

use App\Models\Property;
use App\Models\PropertyInquiry as PropertyInquiryModel;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PropertyDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_an_archived_property_after_exact_title_confirmation(): void
    {
        $disk = (string) config('properties.photos_disk');
        $backupDisk = Storage::fake('local');
        Storage::fake($disk);

        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();
        $title = 'Casa  de la Señora Cruz';
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/casa.jpg',
            'position' => 1,
        ]);
        Storage::disk($disk)->put($photo->path, 'photo');

        $response = $this->actingAs($admin)->delete(
            route('panel.properties.destroy', $property),
            ['title_confirmation' => 'Casa  de la Señora Cruz'],
        );

        $response
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message');

        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('property_photos', ['id' => $photo->id]);
        Storage::disk($disk)->assertMissing($photo->path);
        $backupDisk->assertEmpty();

        $this->actingAs($admin)
            ->get(route('panel.properties.archived'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/properties/archived')
                ->has('properties', 0)
            );
    }

    public function test_admin_can_delete_archived_property_when_photo_object_is_missing(): void
    {
        $disk = (string) config('properties.photos_disk');
        $photoDisk = Storage::fake($disk);
        $backupDisk = Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $property = Property::factory()->for($admin)->create([
            'status' => 'archived',
            'title' => 'Casa archivada sin archivo en S3',
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/casa-ausente.jpg',
            'position' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $property->title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('property_photos', ['id' => $photo->id]);
        $photoDisk->assertMissing($photo->path);
        $backupDisk->assertEmpty();
    }

    public function test_photo_read_failure_keeps_property_and_does_not_delete_photos(): void
    {
        $disk = (string) config('properties.photos_disk');
        $backupDisk = Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $property = Property::factory()->for($admin)->create([
            'status' => 'archived',
            'title' => 'Casa archivada con lectura fallida',
        ]);
        $photoPath = 'properties/casa-lectura-fallida.jpg';
        $property->photos()->create([
            'path' => $photoPath,
            'position' => 1,
        ]);

        $photoDisk = \Mockery::mock(FilesystemAdapter::class);
        $photoDisk->shouldReceive('fileExists')
            ->once()
            ->with($photoPath)
            ->andReturn(true);
        $photoDisk->shouldReceive('readStream')
            ->once()
            ->with($photoPath)
            ->andReturn(null);
        $photoDisk->shouldReceive('delete')->never();
        Storage::shouldReceive('disk')
            ->once()
            ->with($disk)
            ->andReturn($photoDisk);
        Storage::shouldReceive('disk')
            ->once()
            ->with('local')
            ->andReturn($backupDisk);

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $property->title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'error')
            ->assertInertiaFlash('toast.message', 'No se pudieron preparar las fotos para su eliminación. Inténtalo de nuevo.');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['path' => $photoPath]);
        $backupDisk->assertEmpty();
    }

    public function test_storage_deletion_failure_restores_partially_deleted_photos_and_reports_error(): void
    {
        $disk = (string) config('properties.photos_disk');
        $backupDisk = Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $title = 'Casa archivada con fotos inaccesibles';
        $property = Property::factory()->for($admin)->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $photoPaths = [
            'properties/casa-inaccesible-uno.jpg',
            'properties/casa-inaccesible-dos.jpg',
        ];
        $property->photos()->createMany([
            ['path' => $photoPaths[0], 'position' => 1],
            ['path' => $photoPaths[1], 'position' => 2],
        ]);
        $photoFiles = [
            $photoPaths[0] => 'first photo contents',
            $photoPaths[1] => 'second photo contents',
        ];
        $restoreOptions = [];

        $filesystem = \Mockery::mock(FilesystemAdapter::class);
        $filesystem->shouldReceive('readStream')
            ->twice()
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): mixed {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $photoFiles[$photoPath]);
                rewind($stream);

                return $stream;
            });
        $filesystem->shouldReceive('delete')
            ->once()
            ->with($photoPaths[0])
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): bool {
                unset($photoFiles[$photoPath]);

                return true;
            });
        $filesystem->shouldReceive('delete')
            ->once()
            ->with($photoPaths[1])
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): bool {
                unset($photoFiles[$photoPath]);

                return false;
            });
        $filesystem->shouldReceive('fileExists')
            ->twice()
            ->with($photoPaths[0])
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): bool {
                return array_key_exists($photoPath, $photoFiles);
            });
        $filesystem->shouldReceive('fileExists')
            ->twice()
            ->with($photoPaths[1])
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): bool {
                return array_key_exists($photoPath, $photoFiles);
            });
        $filesystem->shouldReceive('writeStream')
            ->twice()
            ->andReturnUsing(function (string $photoPath, mixed $stream, array $options) use (&$photoFiles, &$restoreOptions): bool {
                $photoFiles[$photoPath] = stream_get_contents($stream);
                $restoreOptions[$photoPath] = $options;

                return true;
            });
        Storage::shouldReceive('disk')
            ->once()
            ->with($disk)
            ->andReturn($filesystem);
        Storage::shouldReceive('disk')
            ->once()
            ->with('local')
            ->andReturn($backupDisk);

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'error')
            ->assertInertiaFlash('toast.message', 'No se pudieron eliminar las fotos de la propiedad. Inténtalo de nuevo.');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['path' => $photoPaths[0]]);
        $this->assertDatabaseHas('property_photos', ['path' => $photoPaths[1]]);
        $this->assertSame('first photo contents', $photoFiles[$photoPaths[0]]);
        $this->assertSame('second photo contents', $photoFiles[$photoPaths[1]]);
        $this->assertSame('public', $restoreOptions[$photoPaths[0]]['visibility']);
        $this->assertSame('public', $restoreOptions[$photoPaths[1]]['visibility']);
        $backupDisk->assertEmpty();
    }

    public function test_photo_restore_failure_keeps_backups_for_manual_recovery(): void
    {
        $disk = (string) config('properties.photos_disk');
        $backupDisk = Storage::fake('local');
        Log::spy();

        $admin = User::factory()->admin()->create();
        $title = 'Casa archivada con restauración fallida';
        $property = Property::factory()->for($admin)->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $photoPaths = [
            'properties/casa-restauracion-fallida-uno.jpg',
            'properties/casa-restauracion-fallida-dos.jpg',
        ];
        $property->photos()->createMany([
            ['path' => $photoPaths[0], 'position' => 1],
            ['path' => $photoPaths[1], 'position' => 2],
        ]);
        $photoFiles = [
            $photoPaths[0] => 'first photo contents',
            $photoPaths[1] => 'second photo contents',
        ];

        $filesystem = \Mockery::mock(FilesystemAdapter::class);
        $filesystem->shouldReceive('readStream')
            ->twice()
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): mixed {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $photoFiles[$photoPath]);
                rewind($stream);

                return $stream;
            });
        $filesystem->shouldReceive('delete')
            ->once()
            ->with($photoPaths[0])
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): bool {
                unset($photoFiles[$photoPath]);

                return true;
            });
        $filesystem->shouldReceive('delete')
            ->once()
            ->with($photoPaths[1])
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): bool {
                unset($photoFiles[$photoPath]);

                return false;
            });
        $filesystem->shouldReceive('fileExists')
            ->times(4)
            ->andReturnUsing(function (string $photoPath) use (&$photoFiles): bool {
                return array_key_exists($photoPath, $photoFiles);
            });
        $filesystem->shouldReceive('writeStream')
            ->twice()
            ->andReturn(false);
        Storage::shouldReceive('disk')
            ->once()
            ->with($disk)
            ->andReturn($filesystem);
        Storage::shouldReceive('disk')
            ->once()
            ->with('local')
            ->andReturn($backupDisk);

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'error')
            ->assertInertiaFlash('toast.message', 'No se pudieron eliminar ni restaurar todas las fotos. Contacta con soporte.');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['path' => $photoPaths[0]]);
        $this->assertDatabaseHas('property_photos', ['path' => $photoPaths[1]]);
        $this->assertArrayNotHasKey($photoPaths[0], $photoFiles);
        $this->assertArrayNotHasKey($photoPaths[1], $photoFiles);

        $backupPaths = $backupDisk->allFiles();
        $backupContents = array_map(
            fn (string $backupPath): string => (string) $backupDisk->get($backupPath),
            $backupPaths,
        );

        $this->assertCount(2, $backupPaths);
        $this->assertEqualsCanonicalizing(['first photo contents', 'second photo contents'], $backupContents);
        Log::shouldHaveReceived('critical')
            ->once()
            ->with(
                'Archived property photo restoration was incomplete.',
                \Mockery::on(fn (array $context): bool => $context['property_id'] === $property->id
                    && $context['photo_paths'] === $photoPaths
                    && str_starts_with($context['backup_directory'], 'property-deletion-backups/')),
            );
    }

    public function test_storage_deletion_failure_keeps_photos_that_were_not_deleted(): void
    {
        $disk = (string) config('properties.photos_disk');
        $backupDisk = Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $property = Property::factory()->for($admin)->create([
            'status' => 'archived',
            'title' => 'Casa archivada con archivo intacto',
        ]);
        $photoPath = 'properties/casa-no-eliminada.jpg';
        $property->photos()->create([
            'path' => $photoPath,
            'position' => 1,
        ]);
        $photoFiles = [$photoPath => 'original photo contents'];

        $photoDisk = \Mockery::mock(FilesystemAdapter::class);
        $photoDisk->shouldReceive('fileExists')
            ->twice()
            ->with($photoPath)
            ->andReturnUsing(function (string $path) use (&$photoFiles): bool {
                return array_key_exists($path, $photoFiles);
            });
        $photoDisk->shouldReceive('readStream')
            ->once()
            ->with($photoPath)
            ->andReturnUsing(function () use (&$photoFiles, $photoPath): mixed {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $photoFiles[$photoPath]);
                rewind($stream);

                return $stream;
            });
        $photoDisk->shouldReceive('delete')
            ->once()
            ->with($photoPath)
            ->andReturn(false);
        $photoDisk->shouldReceive('writeStream')->never();
        Storage::shouldReceive('disk')
            ->once()
            ->with($disk)
            ->andReturn($photoDisk);
        Storage::shouldReceive('disk')
            ->once()
            ->with('local')
            ->andReturn($backupDisk);

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $property->title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'error')
            ->assertInertiaFlash('toast.message', 'No se pudieron eliminar las fotos de la propiedad. Inténtalo de nuevo.');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['path' => $photoPath]);
        $this->assertSame('original photo contents', $photoFiles[$photoPath]);
        $backupDisk->assertEmpty();
    }

    public function test_property_deletion_failure_restores_photos_and_reports_database_error(): void
    {
        $disk = (string) config('properties.photos_disk');
        $photoDisk = Storage::fake($disk);
        $backupDisk = Storage::fake('local');

        $admin = User::factory()->admin()->create();
        $property = Property::factory()->for($admin)->create([
            'status' => 'archived',
            'title' => 'Casa archivada con error de base de datos',
        ]);
        $photoPath = 'properties/casa-error-base-datos.jpg';
        $property->photos()->create([
            'path' => $photoPath,
            'position' => 1,
        ]);
        $photoContents = 'original photo contents';
        $photoDisk->put($photoPath, $photoContents);
        Property::deleting(static function (Property $deletingProperty) use ($property): bool {
            return ! $deletingProperty->is($property);
        });

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $property->title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'error')
            ->assertInertiaFlash('toast.message', 'No se pudo eliminar la propiedad. Inténtalo de nuevo.');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['path' => $photoPath]);
        $photoDisk->assertExists($photoPath, $photoContents);
        $backupDisk->assertEmpty();
    }

    public function test_photo_backup_failure_keeps_property_and_does_not_delete_photos(): void
    {
        $disk = (string) config('properties.photos_disk');
        $admin = User::factory()->admin()->create();
        $title = 'Casa archivada sin respaldo disponible';
        $property = Property::factory()->for($admin)->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $photoPath = 'properties/casa-sin-respaldo.jpg';
        $property->photos()->create([
            'path' => $photoPath,
            'position' => 1,
        ]);

        $photoDisk = \Mockery::mock(FilesystemAdapter::class);
        $photoDisk->shouldReceive('fileExists')
            ->once()
            ->with($photoPath)
            ->andReturn(true);
        $photoDisk->shouldReceive('readStream')
            ->once()
            ->with($photoPath)
            ->andReturnUsing(function (): mixed {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, 'original photo contents');
                rewind($stream);

                return $stream;
            });
        $photoDisk->shouldReceive('delete')->never();

        $backupDisk = \Mockery::mock(Filesystem::class);
        $backupDisk->shouldReceive('writeStream')
            ->once()
            ->andReturn(false);
        $backupDisk->shouldReceive('deleteDirectory')
            ->once()
            ->andReturn(true);

        Storage::shouldReceive('disk')
            ->once()
            ->with($disk)
            ->andReturn($photoDisk);
        Storage::shouldReceive('disk')
            ->once()
            ->with('local')
            ->andReturn($backupDisk);

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'error')
            ->assertInertiaFlash('toast.message', 'No se pudieron preparar las fotos para su eliminación. Inténtalo de nuevo.');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['path' => $photoPath]);
    }

    public function test_admin_cannot_delete_a_non_archived_property(): void
    {
        $disk = (string) config('properties.photos_disk');
        Storage::fake($disk);

        $admin = User::factory()->admin()->create();
        $property = Property::factory()->for($admin)->create([
            'status' => 'available',
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/casa-disponible.jpg',
            'position' => 1,
        ]);
        Storage::disk($disk)->put($photo->path, 'photo');

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $property->title,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('property_photos', ['id' => $photo->id]);
        Storage::disk($disk)->assertExists($photo->path);
    }

    public function test_agent_cannot_delete_their_own_archived_property(): void
    {
        $disk = (string) config('properties.photos_disk');
        Storage::fake($disk);

        $agent = User::factory()->agent()->create();
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/casa-del-agente.jpg',
            'position' => 1,
        ]);
        Storage::disk($disk)->put($photo->path, 'photo');

        $this->actingAs($agent)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $property->title,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['id' => $photo->id]);
        Storage::disk($disk)->assertExists($photo->path);
    }

    public function test_guest_cannot_delete_an_archived_property(): void
    {
        $disk = (string) config('properties.photos_disk');
        Storage::fake($disk);

        $title = 'Casa archivada para invitado';
        $property = Property::factory()->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/casa-para-invitado.jpg',
            'position' => 1,
        ]);
        Storage::disk($disk)->put($photo->path, 'photo');

        $this->delete(route('panel.properties.destroy', $property), [
            'title_confirmation' => $title,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['id' => $photo->id]);
        Storage::disk($disk)->assertExists($photo->path);
    }

    public function test_admin_cannot_delete_archived_property_when_confirmation_has_extra_spaces(): void
    {
        $disk = (string) config('properties.photos_disk');
        Storage::fake($disk);

        $admin = User::factory()->admin()->create();
        $title = 'Casa  de la Señora Cruz';
        $property = Property::factory()->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/casa.jpg',
            'position' => 1,
        ]);
        Storage::disk($disk)->put($photo->path, 'photo');

        $response = $this->actingAs($admin)
            ->from(route('panel.properties.archived'))
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => ' Casa  de la Señora Cruz ',
            ]);

        $response
            ->assertRedirect(route('panel.properties.archived'))
            ->assertSessionHasErrors('title_confirmation')
            ->assertInertiaFlashMissing('toast');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => $title,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['id' => $photo->id]);
        Storage::disk($disk)->assertExists($photo->path);
    }

    public function test_admin_cannot_delete_archived_property_with_empty_title_confirmation(): void
    {
        $disk = (string) config('properties.photos_disk');
        Storage::fake($disk);

        $admin = User::factory()->admin()->create();
        $title = 'Casa archivada';
        $property = Property::factory()->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/casa.jpg',
            'position' => 1,
        ]);
        Storage::disk($disk)->put($photo->path, 'photo');

        $response = $this->actingAs($admin)
            ->from(route('panel.properties.archived'))
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => '',
            ]);

        $response
            ->assertRedirect(route('panel.properties.archived'))
            ->assertSessionHasErrors([
                'title_confirmation' => 'Escribe el título de la propiedad para confirmar.',
            ])
            ->assertInertiaFlashMissing('toast');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => $title,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['id' => $photo->id]);
        Storage::disk($disk)->assertExists($photo->path);
    }

    public function test_admin_cannot_delete_archived_property_when_confirmation_has_different_case(): void
    {
        $disk = (string) config('properties.photos_disk');
        Storage::fake($disk);

        $admin = User::factory()->admin()->create();
        $title = 'Casa  de la Señora Cruz';
        $property = Property::factory()->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $photo = $property->photos()->create([
            'path' => 'properties/casa.jpg',
            'position' => 1,
        ]);
        Storage::disk($disk)->put($photo->path, 'photo');

        $response = $this->actingAs($admin)
            ->from(route('panel.properties.archived'))
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => 'casa  de la Señora Cruz',
            ]);

        $response
            ->assertRedirect(route('panel.properties.archived'))
            ->assertSessionHasErrors([
                'title_confirmation' => 'El título no coincide con la propiedad.',
            ])
            ->assertInertiaFlashMissing('toast');

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'title' => $title,
            'status' => 'archived',
        ]);
        $this->assertDatabaseHas('property_photos', ['id' => $photo->id]);
        Storage::disk($disk)->assertExists($photo->path);
    }

    public function test_admin_can_still_see_an_inquiry_after_its_archived_property_is_deleted(): void
    {
        $disk = (string) config('properties.photos_disk');
        Storage::fake($disk);

        $admin = User::factory()->admin()->create();
        $agent = User::factory()->agent()->create();
        $title = 'Casa archivada con consulta';
        $property = Property::factory()->for($agent)->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $inquiry = PropertyInquiryModel::factory()->for($property)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ]);

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
        $this->assertDatabaseHas('property_inquiries', [
            'id' => $inquiry->id,
            'property_id' => $property->id,
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ]);

        $this->actingAs($admin)
            ->get(route('panel.inquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('canSeeAllInquiries', true)
                ->where('inquiries.data.0.id', $inquiry->id)
                ->where('inquiries.data.0.property.title', $title)
                ->where('inquiries.data.0.name', 'Ana Pérez')
                ->where('inquiries.data.0.email', 'ana@example.com')
                ->where('inquiries.data.0.phone', '+51 987 654 321')
                ->where('inquiries.data.0.message', 'Quisiera coordinar una visita.')
            );
    }

    public function test_publishing_agent_can_still_see_their_inquiry_after_property_is_deleted(): void
    {
        $disk = (string) config('properties.photos_disk');
        Storage::fake($disk);

        $admin = User::factory()->admin()->create();
        $publisher = User::factory()->agent()->create();
        $otherAgent = User::factory()->agent()->create();
        $title = 'Casa archivada del agente';
        $property = Property::factory()->for($publisher)->create([
            'status' => 'archived',
            'title' => $title,
        ]);
        $otherProperty = Property::factory()->for($otherAgent)->create([
            'title' => 'Departamento de otra agente',
        ]);
        $inquiry = PropertyInquiryModel::factory()->for($property)->create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'phone' => '+51 987 654 321',
            'message' => 'Quisiera coordinar una visita.',
        ]);
        PropertyInquiryModel::factory()->for($otherProperty)->create();

        $this->actingAs($admin)
            ->delete(route('panel.properties.destroy', $property), [
                'title_confirmation' => $title,
            ])
            ->assertRedirect(route('panel.properties.archived'))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertDatabaseMissing('properties', ['id' => $property->id]);

        $this->actingAs($publisher)
            ->get(route('panel.inquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('panel/inquiries/index')
                ->has('inquiries.data', 1)
                ->where('canSeeAllInquiries', false)
                ->where('inquiries.data.0.id', $inquiry->id)
                ->where('inquiries.data.0.property.title', $title)
                ->where('inquiries.data.0.name', 'Ana Pérez')
                ->where('inquiries.data.0.email', 'ana@example.com')
                ->where('inquiries.data.0.phone', '+51 987 654 321')
                ->where('inquiries.data.0.message', 'Quisiera coordinar una visita.')
            );
    }
}
