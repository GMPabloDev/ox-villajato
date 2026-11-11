import { CheckIcon, ChevronDownIcon } from 'lucide-react';
import { useState } from 'react';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type ComboboxProps = {
    id: string;
    value: string;
    onValueChange: (value: string) => void;
    options: ReadonlyArray<{ code: string; name: string }>;
    placeholder: string;
    searchPlaceholder: string;
    searchLabel: string;
    dialogLabel: string;
    labelId: string;
    emptyMessage: string;
    disabled?: boolean;
    invalid?: boolean;
    'aria-describedby'?: string;
    className?: string;
};

function fold(value: string): string {
    return value
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase();
}

function Combobox({
    id,
    value,
    onValueChange,
    options,
    placeholder,
    searchPlaceholder,
    searchLabel,
    dialogLabel,
    labelId,
    emptyMessage,
    disabled,
    invalid,
    className,
    ...aria
}: ComboboxProps) {
    const [open, setOpen] = useState(false);
    const selected = options.find((option) => option.code === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    type="button"
                    id={id}
                    aria-haspopup="dialog"
                    aria-labelledby={`${labelId} ${id}-value`}
                    aria-expanded={open}
                    aria-invalid={invalid}
                    disabled={disabled}
                    {...aria}
                    className={cn(
                        'flex items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40',
                        !selected && 'text-muted-foreground',
                        className,
                    )}
                >
                    <span id={`${id}-value`} className="truncate">
                        {selected?.name ?? placeholder}
                    </span>
                    <ChevronDownIcon className="size-4 shrink-0 opacity-50" />
                </button>
            </PopoverTrigger>
            <PopoverContent
                aria-label={dialogLabel}
                align="start"
                className="w-(--radix-popover-trigger-width) p-0"
            >
                <Command
                    filter={(item, search) =>
                        fold(item).includes(fold(search)) ? 1 : 0
                    }
                >
                    <CommandInput
                        placeholder={searchPlaceholder}
                        aria-label={searchLabel}
                    />
                    <CommandList>
                        <CommandEmpty>{emptyMessage}</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => (
                                <CommandItem
                                    key={option.code}
                                    value={option.name}
                                    onSelect={() => {
                                        onValueChange(option.code);
                                        setOpen(false);
                                    }}
                                >
                                    {option.name}
                                    <CheckIcon
                                        className={cn(
                                            'ml-auto size-4',
                                            value === option.code
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
export { Combobox };
