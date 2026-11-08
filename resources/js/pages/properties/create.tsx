import { Form, Head } from '@inertiajs/react';
import {
    create,
    store,
} from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PropertyFormFields from '@/components/property-form-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

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
                            <PropertyFormFields errors={errors} />

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
                                    <p className="text-xs text-muted-foreground">
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
                                    <div
                                        className="h-2 w-full overflow-hidden rounded-full bg-muted"
                                        role="progressbar"
                                        aria-label="Subiendo fotos"
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                        aria-valuenow={Math.round(
                                            progress.percentage ?? 0,
                                        )}
                                    >
                                        <div
                                            className="h-full bg-primary transition-all"
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
