import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export default function FilterMenu({
    id,
    label,
    valueLabel,
    legend,
    open,
    align = 'left',
    onOpenChange,
    children,
}: {
    id: string;
    label: string;
    valueLabel: string;
    legend: string;
    open: boolean;
    align?: 'left' | 'right';
    onOpenChange: (open: boolean) => void;
    children: ReactNode;
}) {
    return (
        <details
            data-filter-menu={id}
            open={open}
            onToggle={(event) => onOpenChange(event.currentTarget.open)}
            className="group relative min-w-0 flex-1 md:flex-none"
        >
            <summary
                data-filter-summary={id}
                className="flex min-h-11 w-full cursor-pointer list-none items-center justify-between gap-2 rounded-lg border border-transparent px-2.5 text-xs text-muted-foreground transition-colors duration-[250ms] group-open:border-border group-open:bg-card hover:border-border hover:bg-card md:w-auto md:justify-start md:px-3.5 [&::-webkit-details-marker]:hidden"
            >
                <span>{label}</span>
                <strong className="text-xs font-bold text-foreground">
                    {valueLabel}
                </strong>
                <span
                    aria-hidden="true"
                    className="mb-1 size-[7px] rotate-45 border-r-[1.5px] border-b-[1.5px] border-primary group-open:mt-1 group-open:mb-0 group-open:rotate-[225deg]"
                />
            </summary>

            <fieldset
                className={cn(
                    'vj-menu-fade absolute top-full z-30 mt-2 w-[205px] rounded-[10px] border border-border bg-popover p-2 shadow-[0_12px_30px_#192f301c]',
                    align === 'right'
                        ? 'right-0'
                        : 'right-0 max-md:right-auto max-md:left-0',
                )}
            >
                <legend className="sr-only">{legend}</legend>
                {children}
            </fieldset>
        </details>
    );
}

export function FilterOption({
    checked,
    label,
    name,
    onSelect,
    value,
}: {
    checked: boolean;
    label: string;
    name: string;
    onSelect: () => void;
    value: string;
}) {
    return (
        <label className="block cursor-pointer">
            <input
                type="radio"
                name={name}
                value={value}
                checked={checked}
                onChange={onSelect}
                className="peer sr-only"
            />
            <span className="block rounded-md px-3 py-2.5 text-[13px] text-foreground peer-checked:bg-secondary peer-checked:font-bold peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary peer-focus-visible:ring-inset hover:bg-secondary">
                {label}
            </span>
        </label>
    );
}
