import { Link } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { cn } from '@/lib/utils';

type PropertyGalleryProperty = {
    id: number;
    title: string;
    operation_label: string;
    type_label: string;
    price_formatted: string;
    city: string;
    zone: string;
    bedrooms: number;
    bathrooms: number;
    area: string;
    cover_photo_url: string | null;
    url: string;
};

function specsLabel(property: PropertyGalleryProperty): string {
    const bedrooms = `${property.bedrooms} ${property.bedrooms === 1 ? 'dormitorio' : 'dormitorios'}`;
    const bathrooms = `${property.bathrooms} ${property.bathrooms === 1 ? 'baño' : 'baños'}`;
    const area = Number(property.area).toLocaleString('es-PE', {
        maximumFractionDigits: 2,
    });

    return `${bedrooms} · ${bathrooms} · ${area} m²`;
}

export default function PropertyGalleryCard({
    property,
    index,
    total,
}: {
    property: PropertyGalleryProperty;
    index: number;
    total: number;
}) {
    const isReversed = index % 2 === 1;
    const position = String(index + 1).padStart(2, '0');
    const totalLabel = String(total).padStart(2, '0');

    return (
        <article
            className={cn(
                'grid bg-muted md:min-h-[330px]',
                isReversed
                    ? 'md:grid-cols-[1fr_1.25fr]'
                    : 'md:grid-cols-[1.25fr_1fr]',
            )}
        >
            <div
                className={cn(
                    'relative h-60 overflow-hidden md:aspect-[1.48] md:h-auto',
                    isReversed && 'md:order-2',
                )}
            >
                <Link
                    href={property.url}
                    tabIndex={-1}
                    aria-hidden="true"
                    className="group block size-full overflow-hidden bg-[#dce7df]"
                >
                    {property.cover_photo_url ? (
                        <img
                            src={property.cover_photo_url}
                            alt=""
                            loading="lazy"
                            className="size-full object-cover transition-transform duration-[350ms] group-hover:scale-[1.025] motion-reduce:transition-none"
                        />
                    ) : (
                        <span className="flex size-full items-center justify-center text-muted-foreground">
                            <Building2 className="size-10" />
                        </span>
                    )}
                </Link>
                <span className="pointer-events-none absolute top-[18px] left-[18px] bg-white px-3 py-2.5 text-[11px] font-bold text-[#192f30]">
                    {property.operation_label} · {property.type_label}
                </span>
            </div>

            <div className="flex flex-col justify-center p-6 md:p-[42px]">
                <div className="flex items-start justify-between gap-4 text-[11px] font-semibold tracking-[0.03em] text-muted-foreground">
                    <span>
                        {property.city} · {property.zone}
                    </span>
                    <span aria-hidden="true">
                        {position} / {totalLabel}
                    </span>
                </div>

                <h3 className="mt-7 mb-3 max-w-[390px] font-display text-[clamp(1.55rem,3vw,2.3rem)] leading-[1.2] font-bold">
                    <Link
                        href={property.url}
                        className="transition-colors hover:text-primary"
                    >
                        {property.title}
                    </Link>
                </h3>

                <p className="text-[13px] text-muted-foreground">
                    {specsLabel(property)}
                </p>

                <div className="mt-9 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
                    <strong className="font-display text-[19px] font-bold">
                        {property.price_formatted}
                    </strong>
                    <Link
                        href={property.url}
                        className="text-xs font-bold text-primary hover:underline"
                    >
                        Ver ficha{' '}
                        <span aria-hidden="true" className="pl-1">
                            ↗
                        </span>
                    </Link>
                </div>
            </div>
        </article>
    );
}
