import { Head, Link } from '@inertiajs/react';
import { Building2, MapPin } from 'lucide-react';

type PropertyCard = {
    id: number;
    title: string;
    operation_label: string;
    type_label: string;
    price_formatted: string;
    city: string;
    zone: string;
    cover_photo_url: string | null;
    url: string;
};

export default function PropertiesIndex({
    properties,
}: {
    properties: PropertyCard[];
}) {
    return (
        <>
            <Head title="Propiedades" />

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Propiedades
                </h1>
                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Casas y departamentos en venta y alquiler.
                </p>
            </div>

            {properties.length === 0 ? (
                <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 py-20 text-center dark:border-neutral-700">
                    <Building2 className="mb-3 size-10 text-neutral-400" />
                    <p className="font-medium">
                        Aún no hay propiedades publicadas.
                    </p>
                    <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Vuelve pronto para ver las nuevas propiedades.
                    </p>
                </div>
            ) : (
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {properties.map((property) => (
                        <Link
                            key={property.id}
                            href={property.url}
                            className="group overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
                        >
                            <div className="aspect-[4/3] overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                {property.cover_photo_url ? (
                                    <img
                                        src={property.cover_photo_url}
                                        alt={property.title}
                                        className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center text-neutral-400">
                                        <Building2 className="size-10" />
                                    </div>
                                )}
                            </div>
                            <div className="space-y-3 p-4">
                                <div className="flex items-center justify-between gap-2 text-xs font-medium">
                                    <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                        {property.operation_label}
                                    </span>
                                    <span className="text-neutral-500 dark:text-neutral-400">
                                        {property.type_label}
                                    </span>
                                </div>
                                <div>
                                    <h2 className="line-clamp-2 font-semibold">
                                        {property.title}
                                    </h2>
                                    <p className="mt-1 flex items-center gap-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        <MapPin className="size-3.5" />
                                        {property.city} · {property.zone}
                                    </p>
                                </div>
                                <p className="text-lg font-semibold">
                                    {property.price_formatted}
                                </p>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </>
    );
}
