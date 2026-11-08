import type { ReactNode } from 'react';
import { Building2 } from 'lucide-react';

export type PanelPropertyCardData = {
    id: number;
    title: string;
    price_formatted: string;
    cover_photo_url: string | null;
};

type PanelPropertyCardProps = {
    property: PanelPropertyCardData;
    statusBadge?: ReactNode;
    metadata?: ReactNode;
    contentSpacing?: 'compact' | 'normal';
    children: ReactNode;
};

export function PanelPropertyCard({
    property,
    statusBadge,
    metadata,
    contentSpacing = 'normal',
    children,
}: PanelPropertyCardProps) {
    const contentSpacingClass =
        contentSpacing === 'compact' ? 'space-y-2 p-4' : 'space-y-3 p-4';

    return (
        <li className="overflow-hidden rounded-xl border border-border bg-card shadow-sm ">
            <div className="aspect-[4/3] overflow-hidden bg-muted ">
                {property.cover_photo_url ? (
                    <img
                        src={property.cover_photo_url}
                        alt={property.title}
                        className="size-full object-cover"
                        loading="lazy"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center text-muted-foreground">
                        <Building2 className="size-10" />
                    </div>
                )}
            </div>
            <div className={contentSpacingClass}>
                {statusBadge === undefined ? (
                    <h2 className="line-clamp-2 font-semibold">
                        {property.title}
                    </h2>
                ) : (
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <h2 className="line-clamp-2 font-semibold">
                            {property.title}
                        </h2>
                        {statusBadge}
                    </div>
                )}
                {metadata}
                <p className="text-lg font-semibold">
                    {property.price_formatted}
                </p>
                {children}
            </div>
        </li>
    );
}
