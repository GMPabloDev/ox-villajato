import { Form, Head } from '@inertiajs/react';
import {
    create,
    store,
} from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const selectClassName =
    'border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30';

export default function CreateProperty() {
    return (
        <>
            <Head title="Publicar propiedad" />

            <div className="space-y-8 p-4">
                <Heading
                    title="Publicar propiedad"
                    description="Completa los datos y adjunta las fotos. La primera foto será la portada."
                />

                <Form
                    {...store.form()}
                    className="max-w-3xl space-y-8"
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors, progress }) => (
                        <>
                            <section className="space-y-4">
                                <h2 className="text-base font-semibold">
                                    Datos de la propiedad
                                </h2>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="operation">
                                            Operación
                                        </Label>
                                        <select
                                            id="operation"
                                            name="operation"
                                            defaultValue="sale"
                                            className={selectClassName}
                                        >
                                            <option value="sale">Venta</option>
                                            <option value="rent">
                                                Alquiler
                                            </option>
                                        </select>
                                        <InputError
                                            message={errors.operation}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="type">Tipo</Label>
                                        <select
                                            id="type"
                                            name="type"
                                            defaultValue="house"
                                            className={selectClassName}
                                        >
                                            <option value="house">Casa</option>
                                            <option value="apartment">
                                                Departamento
                                            </option>
                                        </select>
                                        <InputError message={errors.type} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="title">Título</Label>
                                    <Input
                                        id="title"
                                        name="title"
                                        maxLength={255}
                                        placeholder="Casa frente al parque"
                                    />
                                    <InputError message={errors.title} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        Descripción
                                    </Label>
                                    <textarea
                                        id="description"
                                        name="description"
                                        rows={5}
                                        placeholder="Describe la propiedad, sus ambientes y acabados."
                                        className="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </section>

                            <section className="space-y-4">
                                <h2 className="text-base font-semibold">
                                    Precio y ubicación
                                </h2>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="price">Precio</Label>
                                        <Input
                                            id="price"
                                            name="price"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            placeholder="250000"
                                        />
                                        <InputError message={errors.price} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="currency">Moneda</Label>
                                        <select
                                            id="currency"
                                            name="currency"
                                            defaultValue="PEN"
                                            className={selectClassName}
                                        >
                                            <option value="PEN">
                                                Soles (PEN)
                                            </option>
                                            <option value="USD">
                                                Dólares (USD)
                                            </option>
                                        </select>
                                        <InputError message={errors.currency} />
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="city">Ciudad</Label>
                                        <Input
                                            id="city"
                                            name="city"
                                            placeholder="Lima"
                                        />
                                        <InputError message={errors.city} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="zone">Zona</Label>
                                        <Input
                                            id="zone"
                                            name="zone"
                                            placeholder="Miraflores"
                                        />
                                        <InputError message={errors.zone} />
                                    </div>
                                </div>
                            </section>

                            <section className="space-y-4">
                                <h2 className="text-base font-semibold">
                                    Detalles
                                </h2>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="grid gap-2">
                                        <Label htmlFor="bedrooms">
                                            Dormitorios
                                        </Label>
                                        <Input
                                            id="bedrooms"
                                            name="bedrooms"
                                            type="number"
                                            min="0"
                                            step="1"
                                            placeholder="3"
                                        />
                                        <InputError message={errors.bedrooms} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="bathrooms">Baños</Label>
                                        <Input
                                            id="bathrooms"
                                            name="bathrooms"
                                            type="number"
                                            min="0"
                                            step="1"
                                            placeholder="2"
                                        />
                                        <InputError
                                            message={errors.bathrooms}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="area">Área (m²)</Label>
                                        <Input
                                            id="area"
                                            name="area"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            placeholder="120.50"
                                        />
                                        <InputError message={errors.area} />
                                    </div>
                                </div>
                            </section>

                            <section className="space-y-4">
                                <h2 className="text-base font-semibold">
                                    Fotos
                                </h2>

                                <div className="grid gap-2">
                                    <Label htmlFor="photos">
                                        Fotos de la propiedad
                                    </Label>
                                    <Input
                                        id="photos"
                                        name="photos[]"
                                        type="file"
                                        multiple
                                        accept="image/jpeg,image/png,image/webp"
                                    />
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                        Entre 1 y 8 fotos en JPG, PNG o WebP, de
                                        máximo 5 MB cada una. La primera será la
                                        portada.
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
                                    <div className="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-800">
                                        <div
                                            className="h-full bg-neutral-900 transition-all dark:bg-neutral-100"
                                            style={{
                                                width: `${progress.percentage}%`,
                                            }}
                                        />
                                    </div>
                                )}
                            </section>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Publicar propiedad
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateProperty.layout = {
    breadcrumbs: [
        {
            title: 'Publicar propiedad',
            href: create(),
        },
    ],
};
