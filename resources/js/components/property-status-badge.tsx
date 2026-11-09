import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type PropertyStatusValue = 'available' | 'archived' | 'rented' | 'sold';

const statusStyles: Record<PropertyStatusValue, string> = {
    available: 'bg-secondary text-secondary-foreground',
    sold: 'bg-[#edf1f7] text-[#46617e] dark:bg-[#22303d] dark:text-[#a9c6de]',
    rented: 'bg-[#fbf1e5] text-[#865d28] dark:bg-[#3a2c1d] dark:text-[#e8c79a]',
    archived: 'bg-muted text-muted-foreground',
};

export function PropertyStatusBadge({
    status,
    label,
    className,
}: {
    status: PropertyStatusValue;
    label: string;
    className?: string;
}) {
    return (
        <Badge
            className={cn(
                'gap-1.5 rounded-[6px] border-transparent px-2 py-1 text-[10px] font-semibold',
                statusStyles[status],
                className,
            )}
        >
            <span
                aria-hidden="true"
                className="size-1.5 rounded-full bg-current"
            />
            {label}
        </Badge>
    );
}
