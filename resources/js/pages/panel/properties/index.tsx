import { Head, Link } from '@inertiajs/react';
import { Building2, PlusCircle } from 'lucide-react';
import { panelIndex } from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';

type PanelProperty = {
    id: number;
    title: string;
    price_formatted: string;
    cover_photo_url: string | null;
    edit_url: string;
};

export default function PanelPropertiesIndex({
    properties,
    create_url,
}: {
    properties: PanelProperty[];
    create_url: string;
}) {
    return (
        <>
            <Head title="Propiedades" />

            <h1 className="sr-only">Propiedades</h1>

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="Propiedades"
                        description="Todas las propiedades publicadas con su portada, título y precio."
                    />
                    <Button asChild>
                        <Link href={create_url}>
                            <PlusCircle className="size-4" />
                            Publicar propiedad
                        </Link>
                    </Button>
                </div>

                {properties.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 py-20 text-center dark:border-neutral-700">
                        <Building2 className="mb-3 size-10 text-neutral-400" />
                        <p className="font-medium">
                            Aún no hay propiedades publicadas.
                        </p>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            Publica la primera para que aparezca en el catálogo.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href={create_url}>Publicar propiedad</Link>
                        </Button>
                    </div>
                ) : (
                    <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {properties.map((property) => (
                            <li
                                key={property.id}
                                className="overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <div className="aspect-[4/3] overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                    {property.cover_photo_url ? (
                                        <img
                                            src={property.cover_photo_url}
                                            alt={property.title}
                                            className="size-full object-cover"
                                            loading="lazy"
                                        />
                                    ) : (
                                        <div className="flex size-full items-center justify-center text-neutral-400">
                                            <Building2 className="size-10" />
                                        </div>
                                    )}
                                </div>
                                <div className="space-y-3 p-4">
                                    <h2 className="line-clamp-2 font-semibold">
                                        {property.title}
                                    </h2>
                                    <p className="text-lg font-semibold">
                                        {property.price_formatted}
                                    </p>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="w-full"
                                    >
                                        <Link href={property.edit_url}>
                                            Editar
                                        </Link>
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

PanelPropertiesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Propiedades',
            href: panelIndex(),
        },
    ],
};
