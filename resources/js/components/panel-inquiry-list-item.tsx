import { ChevronRight, Mail, Phone } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { formatInquiryDateTime, formatInquiryDay } from '@/lib/inquiry-date';
import { cn } from '@/lib/utils';

export type PanelInquiryListItemData = {
    id: number;
    name: string;
    property: {
        title: string;
    };
    publisher?: {
        name: string | null;
    };
    phone: string | null;
    message: string;
    received_at: string | null;
};

export function PanelInquiryListItem({
    inquiry,
    isSelected,
    showPublisher,
    onSelect,
}: {
    inquiry: PanelInquiryListItemData;
    isSelected: boolean;
    showPublisher: boolean;
    onSelect: (inquiry: PanelInquiryListItemData) => void;
}) {
    const getInitials = useInitials();
    const hasPhone = inquiry.phone !== null && inquiry.phone.trim() !== '';
    const publisherName = inquiry.publisher?.name ?? null;

    return (
        <li>
            <button
                type="button"
                onClick={() => onSelect(inquiry)}
                aria-pressed={isSelected}
                className={cn(
                    'flex w-full items-start gap-3 border-b border-l-[3px] border-border px-4 py-5 text-left transition-colors duration-150 md:px-5',
                    isSelected
                        ? 'border-l-primary bg-muted'
                        : 'border-l-transparent hover:bg-muted/60',
                )}
            >
                <span
                    aria-hidden="true"
                    className={cn(
                        'grid size-[38px] shrink-0 place-items-center rounded-xl text-[11px] font-semibold',
                        isSelected
                            ? 'bg-accent text-accent-foreground'
                            : 'bg-secondary text-secondary-foreground',
                    )}
                >
                    {getInitials(inquiry.name)}
                </span>

                <span className="min-w-0 flex-1">
                    <span className="flex items-baseline justify-between gap-2">
                        <span className="truncate text-xs leading-5 font-semibold">
                            {inquiry.name}
                        </span>
                        <time
                            dateTime={inquiry.received_at ?? undefined}
                            title={formatInquiryDateTime(inquiry.received_at)}
                            className="shrink-0 text-[10px] text-muted-foreground"
                        >
                            {formatInquiryDay(inquiry.received_at)}
                        </time>
                    </span>

                    <span className="mt-1.5 block truncate text-[11px] leading-[1.5] text-muted-foreground">
                        {inquiry.property.title}
                    </span>

                    <span className="mt-1.5 block truncate text-[11px] leading-[1.6] text-muted-foreground">
                        {inquiry.message.replaceAll('\n', ' ')}
                    </span>

                    <span className="mt-2 flex items-center gap-1.5 text-[9px] text-muted-foreground">
                        {hasPhone ? (
                            <Phone
                                className="size-3 shrink-0"
                                aria-hidden="true"
                            />
                        ) : (
                            <Mail
                                className="size-3 shrink-0"
                                aria-hidden="true"
                            />
                        )}
                        {hasPhone ? 'Correo y teléfono' : 'Solo correo'}
                        {showPublisher && publisherName !== null && (
                            <span className="truncate">· {publisherName}</span>
                        )}
                    </span>
                </span>

                <ChevronRight
                    className="mt-7 size-3.5 shrink-0 text-muted-foreground lg:hidden"
                    aria-hidden="true"
                />
            </button>
        </li>
    );
}
