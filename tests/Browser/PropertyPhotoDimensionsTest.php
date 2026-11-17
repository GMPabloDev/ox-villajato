<?php

namespace Tests\Browser;

use App\Models\Property;
use App\Models\User;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PropertyPhotoDimensionsTest extends DuskTestCase
{
    use DatabaseMigrations;

    private const SUBMIT = 'form button[type="submit"]';

    private const REVIEW_STATUS = '[role="status"]';

    private static ?string $imageDirectory = null;

    public static function tearDownAfterClass(): void
    {
        if (self::$imageDirectory !== null) {
            File::deleteDirectory(self::$imageDirectory);
            self::$imageDirectory = null;
        }

        parent::tearDownAfterClass();
    }

    public function test_size_warnings_follow_the_recommended_minimum_and_ignore_the_ideal_size(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $this->selectPhotos($browser, [
                self::photo(1600, 1067),
                self::photo(2000, 1333),
                self::photo(1599, 1067),
                self::photo(1600, 1066),
            ]);

            $this->waitForReviewSettled($browser);

            $this->assertNoticeContains($browser, 'foto-1600x1067.jpg', 'Tamaño 1600 × 1067 px. Cumple las recomendaciones.');
            $this->assertNoticeContains($browser, 'foto-2000x1333.jpg', 'Tamaño 2000 × 1333 px. Cumple las recomendaciones.');
            $this->assertNoticeContains($browser, 'foto-1599x1067.jpg', 'Tamaño 1599 × 1067 px. El mínimo recomendado es 1600 × 1067 px.');
            $this->assertNoticeContains($browser, 'foto-1600x1066.jpg', 'Tamaño 1600 × 1066 px. El mínimo recomendado es 1600 × 1067 px.');
            $this->assertNoticeOmits($browser, 'foto-1599x1067.jpg', 'Se recomienda 3:2 horizontal');
            $this->assertNoticeOmits($browser, 'foto-1600x1066.jpg', 'Se recomienda 3:2 horizontal');

            $browser->assertSeeIn(self::REVIEW_STATUS, 'Dimensiones revisadas. Hay avisos que no impiden guardar.')
                ->assertEnabled(self::SUBMIT)
                ->assertSee('4 de 8 fotos añadidas');
        });
    }

    public function test_ratio_warnings_include_the_recommended_bounds(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $this->selectPhotos($browser, [
                self::photo(2400, 1684),
                self::photo(2400, 1685),
                self::photo(2400, 1524),
                self::photo(2400, 1523),
                self::photo(1710, 1200),
                self::photo(1890, 1200),
            ]);

            $this->waitForReviewSettled($browser);

            $this->assertNoticeContains($browser, 'foto-2400x1684.jpg', 'Cumple las recomendaciones.');
            $this->assertNoticeContains($browser, 'foto-2400x1524.jpg', 'Cumple las recomendaciones.');
            $this->assertNoticeContains($browser, 'foto-1710x1200.jpg', 'Tamaño 1710 × 1200 px. Cumple las recomendaciones.');
            $this->assertNoticeContains($browser, 'foto-1890x1200.jpg', 'Tamaño 1890 × 1200 px. Cumple las recomendaciones.');
            $this->assertNoticeContains($browser, 'foto-2400x1685.jpg', 'Se recomienda 3:2 horizontal.');
            $this->assertNoticeContains($browser, 'foto-2400x1523.jpg', 'Se recomienda 3:2 horizontal.');
            $this->assertNoticeOmits($browser, 'foto-2400x1685.jpg', 'El mínimo recomendado');
            $this->assertNoticeOmits($browser, 'foto-2400x1523.jpg', 'El mínimo recomendado');
        });
    }

    public function test_a_photo_reports_size_and_ratio_together_and_links_them_to_the_thumbnail_and_the_remove_button(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $this->selectPhotos($browser, [
                self::photo(1000, 1600),
                self::photo(1600, 1600),
            ]);

            $this->waitForReviewSettled($browser);

            $this->assertNoticeContains($browser, 'foto-1000x1600.jpg', 'El mínimo recomendado es 1600 × 1067 px.');
            $this->assertNoticeContains($browser, 'foto-1000x1600.jpg', 'Se recomienda 3:2 horizontal.');
            $this->assertNoticeContains($browser, 'foto-1600x1600.jpg', 'Se recomienda 3:2 horizontal.');
            $this->assertNoticeOmits($browser, 'foto-1600x1600.jpg', 'El mínimo recomendado');
            $this->assertNoticeOmits($browser, 'foto-1600x1600.jpg', 'Cumple las recomendaciones');

            $linked = $browser->driver->executeScript(<<<'JS'
                const fileName = arguments[0];
                const image = Array.from(document.querySelectorAll('img[alt^="Vista previa de "]'))
                    .find((candidate) => candidate.alt === `Vista previa de ${fileName}`);
                const button = document.querySelector(`button[aria-label="Quitar ${fileName}"]`);
                const imageNotice = image?.getAttribute('aria-describedby');
                const buttonNotice = button?.getAttribute('aria-describedby');
                const notice = imageNotice ? document.getElementById(imageNotice) : null;

                return Boolean(notice) && imageNotice === buttonNotice && notice.innerText.includes('Aviso: no impide guardar');
                JS, ['foto-1000x1600.jpg']);

            $this->assertTrue($linked, 'La miniatura y su botón de quitar debían apuntar al mismo aviso.');
        });
    }

    public function test_dropping_a_photo_onto_the_picker_reviews_its_dimensions(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $dropped = $browser->driver->executeAsyncScript(<<<'JS'
                const done = arguments[arguments.length - 1];
                const [width, height, fileName] = [arguments[0], arguments[1], arguments[2]];
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const context = canvas.getContext('2d');
                const gradient = context.createLinearGradient(0, 0, width, height);
                gradient.addColorStop(0, '#2b5f8a');
                gradient.addColorStop(1, '#c98a5b');
                context.fillStyle = gradient;
                context.fillRect(0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (!blob) {
                        done('sin imagen');

                        return;
                    }

                    const transfer = new DataTransfer();
                    transfer.items.add(new File([blob], fileName, { type: blob.type }));
                    const picker = document.querySelector('label[for="property-photos"]');

                    ['dragenter', 'dragover', 'drop'].forEach((type) => {
                        picker.dispatchEvent(new DragEvent(type, { bubbles: true, cancelable: true, dataTransfer: transfer }));
                    });

                    done(`${fileName}:${blob.type}`);
                }, 'image/jpeg', 0.8);
                JS, [1599, 1067, 'arrastrada-1599x1067.jpg']);

            $this->assertSame('arrastrada-1599x1067.jpg:image/jpeg', $dropped);

            $this->waitForReviewSettled($browser);

            $this->assertNoticeContains($browser, 'arrastrada-1599x1067.jpg', 'Tamaño 1599 × 1067 px. El mínimo recomendado es 1600 × 1067 px.');
            $browser->assertSeeIn(self::REVIEW_STATUS, 'Hay avisos que no impiden guardar')
                ->assertSee('1 de 8 fotos añadidas');
        });
    }

    public function test_reordering_keeps_each_warning_with_its_photo(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $this->selectPhotos($browser, [
                self::photo(1599, 1067),
                self::photo(2000, 1333),
            ]);

            $this->waitForReviewSettled($browser);

            $this->assertSame(['foto-1599x1067.jpg', 'foto-2000x1333.jpg'], $this->newPhotoNames($browser));

            $browser->click('button[aria-label="Mover foto-1599x1067.jpg a la derecha"]');

            $this->assertSame(['foto-2000x1333.jpg', 'foto-1599x1067.jpg'], $this->newPhotoNames($browser));
            $this->assertNoticeContains($browser, 'foto-1599x1067.jpg', 'El mínimo recomendado es 1600 × 1067 px.');
            $this->assertNoticeContains($browser, 'foto-2000x1333.jpg', 'Cumple las recomendaciones.');
            $this->assertNoticeOmits($browser, 'foto-2000x1333.jpg', 'Aviso');
        });
    }

    public function test_removing_a_photo_drops_its_pending_review_and_unblocks_the_form(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );
            $this->holdPhotoLoads($browser);

            $browser->attach('#property-photos', self::photo(1599, 1067));

            $browser->waitForTextIn(self::REVIEW_STATUS, 'Revisando las dimensiones de las fotos nuevas…');
            $this->waitForSubmitBlocked($browser);

            $browser->click('button[aria-label="Quitar foto-1599x1067.jpg"]')
                ->assertMissing('img[alt="Vista previa de foto-1599x1067.jpg"]')
                ->assertSee('0 de 8 fotos añadidas');
            $this->waitForSubmitEnabled($browser);

            $browser->attach('#property-photos', self::photo(2000, 1333));
            $this->waitForSubmitBlocked($browser);

            $this->releaseHeldPhotoLoads($browser);
            $this->waitForReviewSettled($browser);

            $browser->assertSeeIn(self::REVIEW_STATUS, 'Dimensiones de las fotos nuevas revisadas.')
                ->assertSee('1 de 8 fotos añadidas')
                ->assertEnabled(self::SUBMIT);
            $this->assertSame(['foto-2000x1333.jpg'], $this->newPhotoNames($browser));
        });
    }

    public function test_the_form_waits_for_the_dimension_review_before_it_can_submit(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );
            $this->holdPhotoLoads($browser);

            $browser->attach('#property-photos', self::photo(1599, 1067));

            $browser->waitForTextIn(self::REVIEW_STATUS, 'Revisando las dimensiones de las fotos nuevas…')
                ->assertSeeIn(self::REVIEW_STATUS, 'Revisando las dimensiones de las fotos nuevas…')
                ->assertSeeIn('[data-slot="sheet-footer"]', 'Revisando las dimensiones de las fotos nuevas…');
            $this->waitForSubmitBlocked($browser);
            $this->assertNoticeContains($browser, 'foto-1599x1067.jpg', 'Revisando las dimensiones…');
            $browser->assertDisabled(self::SUBMIT);

            $this->releaseHeldPhotoLoads($browser);
            $this->waitForSubmitEnabled($browser);

            $browser->assertSeeIn(self::REVIEW_STATUS, 'Dimensiones revisadas. Hay avisos que no impiden guardar.')
                ->assertSeeIn('[data-slot="sheet-footer"]', '* Campos obligatorios')
                ->assertEnabled(self::SUBMIT);
            $this->assertNoticeContains($browser, 'foto-1599x1067.jpg', 'El mínimo recomendado es 1600 × 1067 px.');
        });
    }

    public function test_a_dimension_read_that_times_out_is_reported_without_blocking(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );
            $this->holdPhotoLoads($browser);

            $browser->script(<<<'JS'
                const nativeSetTimeout = window.setTimeout;
                window.setTimeout = function (callback, delay, ...args) {
                    return nativeSetTimeout(callback, delay === 10000 ? 1000 : delay, ...args);
                };
                JS);

            $browser->attach('#property-photos', self::photo(2000, 1333))
                ->waitForTextIn(self::REVIEW_STATUS, 'Revisando las dimensiones de las fotos nuevas…')
                ->assertDisabled(self::SUBMIT)
                ->waitForTextIn(self::REVIEW_STATUS, 'No pudimos comprobar algunas fotos y puedes guardar igualmente.');

            $this->waitForSubmitEnabled($browser);
            $this->assertNoticeContains($browser, 'foto-2000x1333.jpg', 'No pudimos comprobar sus dimensiones. Puedes continuar y guardarla.');
            $browser->assertEnabled(self::SUBMIT);
        });
    }

    public function test_a_photo_whose_dimensions_cannot_be_read_is_reported_without_blocking(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $browser->attach('#property-photos', self::unreadablePhoto());

            $browser->waitForTextIn(self::REVIEW_STATUS, 'No pudimos comprobar algunas fotos y puedes guardar igualmente.');
            $this->waitForSubmitEnabled($browser);

            $this->assertNoticeContains($browser, 'ilegible.jpg', 'No pudimos comprobar sus dimensiones. Puedes continuar y guardarla.');
            $browser->assertEnabled(self::SUBMIT);
        });
    }

    public function test_a_photo_with_a_size_warning_is_saved_when_it_passes_the_existing_validation(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $browser->type('#title', 'Casa con foto por debajo del mínimo')
                ->type('#description', 'Casa con una foto más pequeña que la recomendación.')
                ->type('#price', '250000')
                ->type('#bedrooms', '3')
                ->type('#bathrooms', '2')
                ->type('#area', '120')
                ->click('input[name="has_garage"][value="1"] + span')
                ->click('input[name="is_furnished"][value="0"] + span')
                ->attach('#property-photos', base_path('database/seeders/assets/properties/casa.jpeg'));

            $this->selectLocation($browser, '#property-department', 'Lima');
            $this->selectLocation($browser, '#property-province', 'Lima');
            $this->selectLocation($browser, '#property-district', 'Miraflores');

            $this->waitForReviewSettled($browser);

            $this->assertNoticeContains($browser, 'casa.jpeg', 'Tamaño 354 × 240 px. El mínimo recomendado es 1600 × 1067 px.');
            $this->assertPhotoNoticeIsVisible($browser, 'casa.jpeg');

            $browser->press(self::SUBMIT)
                ->waitForText('Casa con foto por debajo del mínimo');
        });

        $property = Property::sole();
        $photo = $property->photos()->firstOrFail();

        $this->assertSame(1, $property->photos()->count());
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_editing_keeps_saved_photos_without_a_retroactive_review_and_reviews_the_new_one(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa con una foto guardada',
            'status' => 'available',
        ]);

        $saved = $property->photos()->create([
            'path' => 'properties/foto-guardada.jpg',
            'position' => 1,
        ]);

        Storage::disk('public')->put($saved->path, file_get_contents(self::photo(1600, 1067)));

        $this->browse(function (Browser $browser) use ($agent, $property): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa con una foto guardada')
                ->press('Editar propiedad')
                ->waitForText('Actualiza la información y las fotos del anuncio.')
                ->waitForTextIn('#property-district', 'Lima');

            $browser->assertSee('Foto 1')
                ->assertMissing('img[alt^="Vista previa de "]')
                ->assertDontSee('Revisando las dimensiones')
                ->assertDontSee('Aviso: no impide guardar')
                ->assertEnabled(self::SUBMIT);

            $browser->attach('#property-photos', base_path('database/seeders/assets/properties/casa.jpeg'));

            $this->waitForReviewSettled($browser);

            $this->assertNoticeContains($browser, 'casa.jpeg', 'El mínimo recomendado es 1600 × 1067 px.');
            $browser->assertSeeIn(self::REVIEW_STATUS, 'Hay avisos que no impiden guardar')
                ->assertSee('2 de 8 fotos añadidas')
                ->press(self::SUBMIT)
                ->waitForLocation(parse_url(route('properties.show', $property), PHP_URL_PATH))
                ->waitForText('Casa con una foto guardada');
        });

        $photos = $property->photos()->orderBy('position')->get();

        $this->assertCount(2, $photos);
        $this->assertSame($saved->id, $photos[0]->id);
        $this->assertSame(1, $photos[0]->position);
        $this->assertSame(2, $photos[1]->position);
        Storage::disk('public')->assertExists($photos[1]->path);
    }

    public function test_the_cover_and_order_controls_still_work_with_a_warning_present(): void
    {
        $agent = User::factory()->agent()->create();

        $property = Property::factory()->for($agent)->create([
            'title' => 'Casa con dos fotos guardadas',
            'status' => 'available',
        ]);

        $cover = $property->photos()->create([
            'path' => 'properties/portada-actual.jpg',
            'position' => 1,
        ]);
        $second = $property->photos()->create([
            'path' => 'properties/segunda-foto.jpg',
            'position' => 2,
        ]);

        foreach ([$cover, $second] as $photo) {
            Storage::disk('public')->put($photo->path, file_get_contents(self::photo(1600, 1067)));
        }

        $this->browse(function (Browser $browser) use ($agent, $property, $cover, $second): void {
            $browser->loginAs($agent)
                ->visit(route('panel.properties.index'))
                ->waitForText('Casa con dos fotos guardadas')
                ->press('Editar propiedad')
                ->waitForText('Actualiza la información y las fotos del anuncio.')
                ->waitForTextIn('#property-district', 'Lima')
                ->attach('#property-photos', base_path('database/seeders/assets/properties/casa.jpeg'));

            $this->waitForReviewSettled($browser);

            $browser->click('button[aria-label="Mover foto 2 a la izquierda"]');

            $this->assertSame([$second->id, $cover->id], $this->photoOrder($browser));
            $this->assertTrue($this->firstPhotoShowsCoverBadge($browser));

            $this->assertNoticeContains($browser, 'casa.jpeg', 'Aviso: no impide guardar');
            $this->assertPhotoNoticeIsVisible($browser, 'casa.jpeg');

            $browser->press(self::SUBMIT)
                ->waitForLocation(parse_url(route('properties.show', $property), PHP_URL_PATH))
                ->waitForText('Casa con dos fotos guardadas');
        });

        $photos = $property->photos()->orderBy('position')->get();

        $this->assertCount(3, $photos);
        $this->assertSame([$second->id, $cover->id, $photos[2]->id], $photos->pluck('id')->all());
        $this->assertSame([1, 2, 3], $photos->pluck('position')->all());
        Storage::disk('public')->assertExists($photos[2]->path);
    }

    public function test_the_form_still_rejects_unsupported_formats_and_oversized_photos(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $browser->attach('#property-photos', self::gifPhoto())
                ->assertSee('Solo se admiten fotos JPG, PNG o WebP.')
                ->assertSee('0 de 8 fotos añadidas')
                ->assertMissing('img[alt="Vista previa de no-admitida.gif"]');

            $browser->attach('#property-photos', self::oversizedPhoto())
                ->assertSee('Cada foto debe pesar como máximo 5 MB.')
                ->assertSee('0 de 8 fotos añadidas')
                ->assertMissing('img[alt="Vista previa de demasiado-pesada.jpg"]');

            $browser->attach('#property-photos', self::photo(2000, 1333));

            $this->waitForReviewSettled($browser);

            $browser->waitUntilMissingText('Cada foto debe pesar como máximo 5 MB.')
                ->assertSee('1 de 8 fotos añadidas')
                ->assertSeeIn(self::REVIEW_STATUS, 'Dimensiones de las fotos nuevas revisadas.');
        });
    }

    public function test_the_form_still_limits_the_selection_to_eight_photos(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $this->openCreateSheet(
                $browser->loginAs($agent)->visit(route('panel.properties.index')),
            );

            $this->selectPhotos($browser, array_fill(0, 8, self::photo(1600, 1067)));

            $this->waitForReviewSettled($browser);

            $browser->assertSee('8 de 8 fotos añadidas')
                ->assertDontSee('Puedes añadir como máximo 8 fotos.');

            $this->selectPhotos($browser, [self::photo(2000, 1333)]);

            $browser->assertSee('Puedes añadir como máximo 8 fotos.')
                ->assertSee('8 de 8 fotos añadidas')
                ->assertMissing('img[alt="Vista previa de foto-2000x1333.jpg"]');
        });
    }

    public function test_a_warned_photo_is_reviewed_and_saved_on_a_mobile_screen(): void
    {
        $agent = User::factory()->agent()->create();

        $this->browse(function (Browser $browser) use ($agent): void {
            $browser->resize(390, 844)
                ->loginAs($agent)
                ->visit(route('panel.properties.index'));

            $this->openCreateSheet($browser);

            $browser->type('#title', 'Casa con foto advertida en móvil')
                ->type('#description', 'Casa publicada desde una pantalla pequeña.')
                ->type('#price', '180000')
                ->type('#bedrooms', '2')
                ->type('#bathrooms', '1')
                ->type('#area', '85')
                ->click('input[name="has_garage"][value="0"] + span')
                ->click('input[name="is_furnished"][value="1"] + span')
                ->attach('#property-photos', base_path('database/seeders/assets/properties/casa.jpeg'));

            $this->selectLocation($browser, '#property-department', 'Lima');
            $this->selectLocation($browser, '#property-province', 'Lima');
            $this->selectLocation($browser, '#property-district', 'Miraflores');

            $this->waitForReviewSettled($browser);

            $this->assertNoticeContains($browser, 'casa.jpeg', 'El mínimo recomendado es 1600 × 1067 px.');
            $this->assertPhotoNoticeIsVisible($browser, 'casa.jpeg');

            $browser->press(self::SUBMIT)
                ->waitForText('Casa con foto advertida en móvil');
        });

        $property = Property::sole();

        $this->assertSame(1, $property->photos()->count());
        Storage::disk('public')->assertExists($property->photos()->firstOrFail()->path);
    }

    /**
     * Open the create sheet once the location catalog allows submitting.
     */
    private function openCreateSheet(Browser $browser): void
    {
        $browser->press('Publicar propiedad')
            ->waitForText('Añade la información para crear tu anuncio.');
        $this->waitForSubmitEnabled($browser);
    }

    /**
     * The form blocks its submit while any selected photo is still under review.
     */
    private function waitForSubmitBlocked(Browser $browser): void
    {
        $browser->waitUntil("document.querySelector('".self::SUBMIT."').disabled");
    }

    private function waitForSubmitEnabled(Browser $browser): void
    {
        $browser->waitUntil("!document.querySelector('".self::SUBMIT."').disabled");
    }

    /**
     * The review is over once no photo is pending and the form accepts a submit.
     */
    private function waitForReviewSettled(Browser $browser): void
    {
        $browser->waitUntil("!document.querySelector('".self::SUBMIT."').disabled && !document.body.innerText.includes('Revisando las dimensiones')");
    }

    /**
     * Defer every dimension read so the review state can be observed, the way a
     * slow connection keeps a photo pending.
     */
    private function holdPhotoLoads(Browser $browser): void
    {
        $browser->script(<<<'JS'
            if (window.__releaseHeldPhotoLoads) {
                return;
            }

            const nativeSource = Object.getOwnPropertyDescriptor(HTMLImageElement.prototype, 'src').set;
            const NativeImage = window.Image;
            window.__heldPhotoLoads = [];

            window.Image = function () {
                const image = new NativeImage();

                Object.defineProperty(image, 'src', {
                    configurable: true,
                    get: function () {
                        return image.getAttribute('src') || '';
                    },
                    set: function (value) {
                        window.__heldPhotoLoads.push({ image: image, source: value });
                    },
                });

                return image;
            };

            window.__releaseHeldPhotoLoads = function () {
                const held = window.__heldPhotoLoads.splice(0);

                held.forEach(function (entry) {
                    nativeSource.call(entry.image, entry.source);
                });

                return held.length;
            };
            JS);
    }

    private function releaseHeldPhotoLoads(Browser $browser): void
    {
        $browser->script('window.__releaseHeldPhotoLoads();');
    }

    /**
     * Read the review notice a thumbnail points at, following aria-describedby.
     */
    private function noticeFor(Browser $browser, string $fileName): string
    {
        $notice = $browser->driver->executeScript(<<<'JS'
            const fileName = arguments[0];
            const image = Array.from(document.querySelectorAll('img[alt^="Vista previa de "]'))
                .find((candidate) => candidate.alt === `Vista previa de ${fileName}`);

            if (!image) {
                return null;
            }

            const notice = document.getElementById(image.getAttribute('aria-describedby') || '');

            return notice ? notice.innerText.replace(/\s+/g, ' ').trim() : null;
            JS, [$fileName]);

        return is_string($notice) ? $notice : '';
    }

    private function assertNoticeContains(Browser $browser, string $fileName, string $expected): void
    {
        $notice = $this->noticeFor($browser, $fileName);

        $this->assertStringContainsString(
            $expected,
            $notice,
            "La foto {$fileName} debía mostrar «{$expected}» y mostró «{$notice}». Fotos: ".implode(', ', $this->newPhotoNames($browser)).'.',
        );
    }

    private function assertNoticeOmits(Browser $browser, string $fileName, string $unexpected): void
    {
        $this->assertStringNotContainsString(
            $unexpected,
            $this->noticeFor($browser, $fileName),
            "La foto {$fileName} no debía mostrar «{$unexpected}».",
        );
    }

    /**
     * The notice and the submit button must fit inside the visible viewport.
     */
    private function assertPhotoNoticeIsVisible(Browser $browser, string $fileName): void
    {
        $result = $browser->driver->executeScript(<<<'JS'
            const fileName = arguments[0];
            const image = Array.from(document.querySelectorAll('img[alt^="Vista previa de "]'))
                .find((candidate) => candidate.alt === `Vista previa de ${fileName}`);
            const notice = image ? document.getElementById(image.getAttribute('aria-describedby') || '') : null;
            const submit = document.querySelector('form button[type="submit"]');

            if (!notice || !submit) {
                return 'falta el aviso o el botón de envío';
            }

            const noticeRect = notice.getBoundingClientRect();
            const submitRect = submit.getBoundingClientRect();
            const noticeInsideViewport = noticeRect.width > 0 && noticeRect.left >= -1 && noticeRect.right <= window.innerWidth + 1;
            const noticeIsNotClipped = notice.scrollWidth <= notice.clientWidth + 1;
            const submitInsideViewport = submitRect.width > 0 && submitRect.top >= -1 && submitRect.bottom <= window.innerHeight + 1;
            const isVisible = noticeInsideViewport && noticeIsNotClipped && submitInsideViewport && !submit.disabled;

            return isVisible ? 'visible' : `aviso=${JSON.stringify(noticeRect)} recortado=${notice.scrollWidth}/${notice.clientWidth} boton=${JSON.stringify(submitRect)} deshabilitado=${submit.disabled} ventana=${window.innerWidth}x${window.innerHeight}`;
            JS, [$fileName]);

        $this->assertSame('visible', $result);
    }

    /**
     * @return array<int, string>
     */
    private function newPhotoNames(Browser $browser): array
    {
        $names = $browser->driver->executeScript(<<<'JS'
            return Array.from(document.querySelectorAll('img[alt^="Vista previa de "]'))
                .map((image) => image.alt.slice('Vista previa de '.length));
            JS);

        return is_array($names) ? array_values($names) : [];
    }

    /**
     * @return array<int, int>
     */
    private function photoOrder(Browser $browser): array
    {
        $order = $browser->driver->executeScript(<<<'JS'
            return Array.from(document.querySelectorAll('input[name="photo_order[]"]'))
                .map((input) => Number(input.value));
            JS);

        return is_array($order) ? array_map(intval(...), $order) : [];
    }

    private function firstPhotoShowsCoverBadge(Browser $browser): bool
    {
        $showsBadge = $browser->driver->executeScript(<<<'JS'
            const input = document.querySelector('input[name="photo_order[]"]');

            return input !== null && input.closest('li').innerText.includes('Portada');
            JS);

        return $showsBadge === true;
    }

    private function selectLocation(Browser $browser, string $triggerSelector, string $optionLabel): void
    {
        $browser->click($triggerSelector)
            ->waitFor('[role="listbox"]');

        $browser->driver
            ->findElement(WebDriverBy::xpath(
                "//div[@role='option'][normalize-space(.)='{$optionLabel}']",
            ))
            ->click();

        $browser->waitUntilMissing('[role="listbox"]');
    }

    /**
     * Select the given files from the picker, one after another.
     *
     * ChromeDriver appends every path to the files the input already lists,
     * while the native dialog replaces them. The picker keeps the selection in
     * the input for submission, so clear it first and let the change event carry
     * only the new file, as it does in a real browser.
     *
     * @param  array<int, string>  $paths
     */
    private function selectPhotos(Browser $browser, array $paths): void
    {
        foreach ($paths as $path) {
            $browser->script("document.getElementById('property-photos').value = '';");
            $browser->attach('#property-photos', $path);
        }
    }

    /**
     * Build a real JPEG with the exact dimensions a case needs.
     */
    private static function photo(int $width, int $height): string
    {
        $path = self::imageDirectory()."/foto-{$width}x{$height}.jpg";

        if (! is_file($path)) {
            $image = imagecreatetruecolor($width, $height);
            $color = imagecolorallocate($image, 40, 90, 140);
            imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $color);
            imagejpeg($image, $path);
        }

        return $path;
    }

    /**
     * A JPG whose bytes are not an image, so the browser cannot read its size.
     */
    private static function unreadablePhoto(): string
    {
        $path = self::imageDirectory().'/ilegible.jpg';

        if (! is_file($path)) {
            file_put_contents($path, 'esto no es una imagen');
        }

        return $path;
    }

    private static function gifPhoto(): string
    {
        $path = self::imageDirectory().'/no-admitida.gif';

        if (! is_file($path)) {
            $image = imagecreate(20, 20);
            imagegif($image, $path);
        }

        return $path;
    }

    private static function oversizedPhoto(): string
    {
        $path = self::imageDirectory().'/demasiado-pesada.jpg';

        if (! is_file($path)) {
            file_put_contents($path, str_repeat('0', 5 * 1024 * 1024 + 1));
        }

        return $path;
    }

    private static function imageDirectory(): string
    {
        if (self::$imageDirectory === null) {
            $directory = sys_get_temp_dir().'/ox-photo-dimensions-'.bin2hex(random_bytes(8));

            if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
                throw new \RuntimeException("No se pudo crear el directorio temporal {$directory}.");
            }

            self::$imageDirectory = $directory;
        }

        return self::$imageDirectory;
    }
}
