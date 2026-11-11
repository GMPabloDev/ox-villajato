import {
    Archive,
    Bath,
    BedDouble,
    Check,
    MapPin,
    Pencil,
    RotateCcw,
    Ruler,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { PropertyImage } from '@/components/property-image';
import {
    PropertyStatusBadge,
    type PropertyStatusValue,
} from '@/components/property-status-badge';
import { Button } from '@/components/ui/button';
import { locationLabel } from '@/lib/ubigeo';

export type PanelPropertyDetailData = {
    id: number;
    title: string;
    description: string;
    operation: 'rent' | 'sale';
    operation_label: string;
    type_label: string;
    price_formatted: string;
    currency: string;
    district: string;
    province: string;
    department: string;
    bedrooms: number;
    bathrooms: number;
    area: string;
    cover_photo_url: string | null;
    status: PropertyStatusValue;
    status_label: string;
    status_action: 'close' | 'reopen' | null;
    status_action_label: string | null;
};

function propertyReference(id: number): string {
    return `VJ-${String(id).padStart(4, '0')}`;
}

function formatArea(area: string): string {
    const value = Number(area);

    if (Number.isNaN(value)) {
        return area;
    }

    return new Intl.NumberFormat('es-PE', {
        maximumFractionDigits: 2,
    }).format(value);
}

function Feature({
    icon,
    label,
    value,
}: {
    icon: ReactNode;
    label: string;
    value: string;
}) {
    return (
        <div className="flex min-w-0 flex-col items-center gap-2 text-[10px] text-muted-foreground">
            {icon}
            <dt className="sr-only">{label}</dt>
            <dd className="text-center text-xs font-semibold text-foreground">
                {value}
            </dd>
        </div>
    );
}

export function PanelPropertyDetail({
    property,
    onEdit,
    onStatusChange,
    onArchive,
}: {
    property: PanelPropertyDetailData;
    onEdit: () => void;
    onStatusChange: () => void;
    onArchive: () => void;
}) {
    const isRent = property.operation === 'rent';
    const canArchive = property.status === 'available';

    return (
        <div className="min-w-0">
            <PropertyImage
                src={property.cover_photo_url}
                alt={`Foto de ${property.title}`}
                className="aspect-[1.65] w-full rounded-[10px]"
                fallbackIconClassName="size-10"
                loading="eager"
            />

            <div className="mt-5 flex flex-wrap items-center justify-between gap-2">
                <p className="flex flex-wrap items-center gap-2 text-[10px] tracking-[0.04em] text-muted-foreground">
                    <span>{propertyReference(property.id)}</span>
                    <span
                        aria-hidden="true"
                        className="size-[3px] rounded-full bg-border"
                    />
                    <span>{property.operation_label}</span>
                    <span
                        aria-hidden="true"
                        className="size-[3px] rounded-full bg-border"
                    />
                    <span>{property.type_label}</span>
                </p>
                <PropertyStatusBadge
                    status={property.status}
                    label={property.status_label}
                />
            </div>

            <h2 className="mt-3 font-display text-[21px] leading-[1.45] font-extrabold tracking-[-0.5px]">
                {property.title}
            </h2>

            <p className="mt-2 flex items-start gap-1.5 text-xs text-muted-foreground">
                <MapPin
                    className="mt-0.5 size-3.5 shrink-0"
                    aria-hidden="true"
                />
                <span>{locationLabel(property)}</span>
            </p>

            <p className="mt-5 text-[25px] font-semibold tracking-[-0.8px] tabular-nums">
                {property.price_formatted}
                <span className="ml-1.5 text-[11px] font-normal tracking-normal text-muted-foreground">
                    {property.currency}
                    {isRent ? ' / mes' : ''}
                </span>
            </p>

            <dl className="my-5 grid grid-cols-3 gap-2 border-y border-border py-4">
                <Feature
                    icon={<BedDouble className="size-4" aria-hidden="true" />}
                    label="Dormitorios"
                    value={`${property.bedrooms} ${
                        property.bedrooms === 1 ? 'dormitorio' : 'dormitorios'
                    }`}
                />
                <Feature
                    icon={<Bath className="size-4" aria-hidden="true" />}
                    label="Baños"
                    value={`${property.bathrooms} ${
                        property.bathrooms === 1 ? 'baño' : 'baños'
                    }`}
                />
                <Feature
                    icon={<Ruler className="size-4" aria-hidden="true" />}
                    label="Área"
                    value={`${formatArea(property.area)} m²`}
                />
            </dl>

            <h3 className="text-[11px] font-semibold">Sobre este inmueble</h3>
            <p className="mt-2 text-xs leading-[1.9] whitespace-pre-line text-muted-foreground">
                {property.description}
            </p>

            <div className="mt-6 flex flex-wrap gap-2">
                <Button
                    type="button"
                    onClick={onEdit}
                    className="h-10 flex-1 basis-[190px] rounded-[10px] text-xs font-semibold"
                >
                    <Pencil className="size-4" aria-hidden="true" />
                    Editar propiedad
                </Button>

                {property.status_action !== null &&
                    property.status_action_label !== null && (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onStatusChange}
                            className="h-10 flex-1 basis-[190px] rounded-[10px] border-input text-xs font-semibold"
                        >
                            {property.status_action === 'close' ? (
                                <Check className="size-4" aria-hidden="true" />
                            ) : (
                                <RotateCcw
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            )}
                            {property.status_action_label}
                        </Button>
                    )}

                {canArchive && (
                    <Button
                        type="button"
                        variant="ghost"
                        onClick={onArchive}
                        className="h-9 w-full justify-start px-2 text-xs font-semibold text-primary hover:bg-secondary hover:text-primary"
                    >
                        <Archive className="size-4" aria-hidden="true" />
                        Archivar propiedad
                    </Button>
                )}
            </div>
        </div>
    );
}
