import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowDown, ArrowUp, X } from 'lucide-react';
import {
    panelIndex,
    update,
} from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PropertyFormFields from '@/components/property-form-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type EditPhoto = {
    id: number;
    url: string;
    position: number;
};

type EditProperty = {
    id: number;
    operation: string;
    type: string;
    title: string;
    description: string;
    price: string;
    currency: string;
    city: string;
    zone: string;
    bedrooms: number;
    bathrooms: number;
    area: string;
    photos: EditPhoto[];
};

export default function EditPropertyPage({
    property,
}: {
    property: EditProperty;
}) {
    const [orderedPhotos, setOrderedPhotos] = useState<EditPhoto[]>(() =>
        [...property.photos].sort((a, b) => a.position - b.position),
    );

    function movePhoto(id: number, direction: -1 | 1) {
        setOrderedPhotos((photos) => {
            const index = photos.findIndex((photo) => photo.id === id);
            const target = index + direction;

            if (index === -1 || target < 0 || target >= photos.length) {
                return photos;
            }

            const next = [...photos];
            [next[index], next[target]] = [next[target], next[index]];

            return next;
        });
    }

    function removePhoto(id: number) {
        setOrderedPhotos((photos) => photos.filter((photo) => photo.id !== id));
    }

    return (
        <>
            <Head title={`Editar ${property.title}`} />

            <h1 className="sr-only">Editar propiedad</h1>

            <div className="space-y-8 p-4">
                <Heading
                    title="Editar propiedad"
                    description="Actualiza los datos y reordena las fotos. La primera foto será la portada."
                />

                <Form
                    {...update.form(property.id)}
                    className="max-w-3xl space-y-8"
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors, progress }) => (
                        <>
                            <PropertyFormFields
                                errors={errors}
                                defaults={{
                                    operation: property.operation,
                                    type: property.type,
                                    title: property.title,
                                    description: property.description,
                                    price: property.price,
                                    currency: property.currency,
                                    city: property.city,
                                    zone: property.zone,
                                    bedrooms: property.bedrooms,
                                    bathrooms: property.bathrooms,
                                    area: property.area,
                                }}
                            />

                            <section className="space-y-4">
                                <h2 className="text-base font-semibold">
                                    Fotos
                                </h2>

                                {orderedPhotos.length > 0 ? (
                                    <ul className="grid gap-4 sm:grid-cols-2">
                                        {orderedPhotos.map((photo, index) => (
                                            <li
                                                key={photo.id}
                                                className="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900"
                                            >
                                                <div className="relative aspect-[4/3] bg-neutral-100 dark:bg-neutral-800">
                                                    <img
                                                        src={photo.url}
                                                        alt={`${property.title} — foto ${index + 1}`}
                                                        className="size-full object-cover"
                                                        loading="lazy"
                                                    />
                                                    {index === 0 && (
                                                        <span className="absolute top-2 left-2 rounded-full bg-neutral-900 px-2.5 py-1 text-xs font-medium text-white dark:bg-white dark:text-neutral-900">
                                                            Portada
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="flex items-center justify-between gap-2 p-2">
                                                    <div className="flex items-center gap-1">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            disabled={
                                                                index === 0
                                                            }
                                                            onClick={() =>
                                                                movePhoto(
                                                                    photo.id,
                                                                    -1,
                                                                )
                                                            }
                                                            aria-label={`Subir foto ${index + 1}`}
                                                        >
                                                            <ArrowUp className="size-4" />
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            disabled={
                                                                index ===
                                                                orderedPhotos.length -
                                                                    1
                                                            }
                                                            onClick={() =>
                                                                movePhoto(
                                                                    photo.id,
                                                                    1,
                                                                )
                                                            }
                                                            aria-label={`Bajar foto ${index + 1}`}
                                                        >
                                                            <ArrowDown className="size-4" />
                                                        </Button>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        aria-label={`Quitar foto ${index + 1}`}
                                                        onClick={() =>
                                                            removePhoto(
                                                                photo.id,
                                                            )
                                                        }
                                                    >
                                                        <X className="size-4" />
                                                        Quitar
                                                    </Button>
                                                </div>
                                                <input
                                                    type="hidden"
                                                    name="photo_order[]"
                                                    value={photo.id}
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                        Quitaste todas las fotos actuales.
                                        Agrega al menos una nueva para poder
                                        guardar.
                                    </p>
                                )}

                                <InputError message={errors.photo_order} />

                                {Object.entries(errors)
                                    .filter(([key]) =>
                                        key.startsWith('photo_order.'),
                                    )
                                    .map(([key, message]) => (
                                        <InputError
                                            key={key}
                                            message={message}
                                        />
                                    ))}

                                <div className="grid gap-2">
                                    <Label htmlFor="photos">
                                        Agregar fotos
                                    </Label>
                                    <Input
                                        id="photos"
                                        name="photos[]"
                                        type="file"
                                        multiple
                                        accept="image/jpeg,image/png,image/webp"
                                    />
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                        Entre 1 y 8 fotos en total entre las
                                        conservadas y las nuevas, en JPG, PNG o
                                        WebP, de máximo 5 MB cada una. Las
                                        nuevas se agregan al final.
                                    </p>
                                    {Object.entries(errors)
                                        .filter(
                                            ([key]) =>
                                                key === 'photos' ||
                                                key.startsWith('photos.'),
                                        )
                                        .map(([key, message]) => (
                                            <InputError
                                                key={key}
                                                message={message}
                                            />
                                        ))}
                                </div>

                                {progress && (
                                    <div
                                        className="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800"
                                        role="progressbar"
                                        aria-label="Subiendo fotos"
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-valuenow={Math.round(
                                            progress.percentage ?? 0,
                                        )}
                                    >
                                        <div
                                            className="h-full bg-neutral-900 transition-all dark:bg-neutral-100"
                                            style={{
                                                width: `${progress.percentage ?? 0}%`,
                                            }}
                                        />
                                    </div>
                                )}
                            </section>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Guardar cambios
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

EditPropertyPage.layout = ({ property }: { property: EditProperty }) => ({
    breadcrumbs: [
        {
            title: 'Propiedades',
            href: panelIndex(),
        },
        {
            title: 'Editar propiedad',
            href: update.url(property.id),
        },
    ],
});
