import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Bath, BedDouble, MapPin, Maximize } from 'lucide-react';
import { home } from '@/routes';

type PropertyPhoto = {
    url: string;
    position: number;
};

type Property = {
    id: number;
    title: string;
    description: string;
    operation_label: string;
    type_label: string;
    price_formatted: string;
    city: string;
    zone: string;
    bedrooms: number;
    bathrooms: number;
    area: string;
    cover_photo_url: string | null;
    photos: PropertyPhoto[];
};

export default function PropertyShow({ property }: { property: Property }) {
    return (
        <>
            <Head title={property.title} />

            <Link
                href={home()}
                className="mb-6 inline-flex items-center gap-2 text-sm text-neutral-500 transition-colors hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white"
            >
                <ArrowLeft className="size-4" />
                Volver al catálogo
            </Link>

            <div className="grid gap-8 lg:grid-cols-[1.4fr_1fr]">
                <div className="space-y-4">
                    <div className="aspect-[4/3] overflow-hidden rounded-xl bg-neutral-100 dark:bg-neutral-800">
                        {property.cover_photo_url ? (
                            <img
                                src={property.cover_photo_url}
                                alt={property.title}
                                className="size-full object-cover"
                            />
                        ) : (
                            <div className="flex size-full items-center justify-center text-neutral-400">
                                Sin foto
                            </div>
                        )}
                    </div>

                    {property.photos.length > 1 && (
                        <div className="grid grid-cols-3 gap-3">
                            {property.photos.slice(1).map((photo) => (
                                <div
                                    key={photo.position}
                                    className="aspect-[4/3] overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-800"
                                >
                                    <img
                                        src={photo.url}
                                        alt={`${property.title} — foto ${photo.position}`}
                                        className="size-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="space-y-6">
                    <div>
                        <div className="flex flex-wrap gap-2 text-xs font-medium">
                            <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                {property.operation_label}
                            </span>
                            <span className="rounded-full bg-neutral-100 px-2.5 py-1 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                {property.type_label}
                            </span>
                        </div>
                        <h1 className="mt-3 text-2xl font-semibold tracking-tight">
                            {property.title}
                        </h1>
                        <p className="mt-2 flex items-center gap-1 text-sm text-neutral-500 dark:text-neutral-400">
                            <MapPin className="size-4" />
                            {property.city} · {property.zone}
                        </p>
                    </div>

                    <p className="text-3xl font-semibold">
                        {property.price_formatted}
                    </p>

                    <div className="grid grid-cols-3 gap-4 rounded-xl border border-neutral-200 p-4 text-sm dark:border-neutral-800">
                        <div className="flex items-center gap-2">
                            <BedDouble className="size-4" />
                            {property.bedrooms} dorm.
                        </div>
                        <div className="flex items-center gap-2">
                            <Bath className="size-4" />
                            {property.bathrooms} baños
                        </div>
                        <div className="flex items-center gap-2">
                            <Maximize className="size-4" />
                            {property.area} m²
                        </div>
                    </div>

                    <div>
                        <h2 className="font-semibold">Descripción</h2>
                        <p className="mt-2 text-sm leading-6 whitespace-pre-line text-neutral-600 dark:text-neutral-300">
                            {property.description}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
