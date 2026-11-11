import { PropertyImage } from '@/components/property-image';
import {
    PropertyStatusBadge,
    type PropertyStatusValue,
} from '@/components/property-status-badge';
import { locationLabel } from '@/lib/ubigeo';
import { cn } from '@/lib/utils';

export type PanelPropertyListItemData = {
    id: number;
    title: string;
    operation: 'rent' | 'sale';
    operation_label: string;
    district: string;
    province: string;
    department: string;
    price_formatted: string;
    cover_photo_url: string | null;
    status: PropertyStatusValue;
    status_label: string;
};

export function PanelPropertyListItem({
    property,
    isSelected,
    onSelect,
}: {
    property: PanelPropertyListItemData;
    isSelected: boolean;
    onSelect: (property: PanelPropertyListItemData) => void;
}) {
    return (
        <li>
            <button
                type="button"
                onClick={() => onSelect(property)}
                aria-pressed={isSelected}
                className={cn(
                    'grid w-full grid-cols-[72px_minmax(0,1fr)] items-center gap-3 border-b border-l-[3px] border-border px-4 py-[17px] text-left transition-colors duration-150 md:px-5',
                    isSelected
                        ? 'border-l-primary bg-muted'
                        : 'border-l-transparent hover:bg-muted/60',
                )}
            >
                <PropertyImage
                    src={property.cover_photo_url}
                    alt=""
                    className="size-[72px] rounded-[7px]"
                />

                <span className="min-w-0">
                    <span className="line-clamp-2 block font-display text-[13px] leading-[1.6] font-extrabold tracking-[-0.2px]">
                        {property.title}
                    </span>
                    <span className="mt-1 block text-[11px] text-muted-foreground">
                        {property.operation_label}
                    </span>
                    <span className="block text-[11px] text-muted-foreground">
                        {locationLabel(property)}
                    </span>
                    <span className="mt-2 flex flex-wrap items-center justify-between gap-2">
                        <span className="text-[13px] font-semibold tabular-nums">
                            {property.price_formatted}
                            {property.operation === 'rent' && (
                                <span className="ml-1 text-[10px] font-normal text-muted-foreground">
                                    / mes
                                </span>
                            )}
                        </span>
                        <PropertyStatusBadge
                            status={property.status}
                            label={property.status_label}
                        />
                    </span>
                </span>
            </button>
        </li>
    );
}
