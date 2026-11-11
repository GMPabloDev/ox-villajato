import { ArrowUpDown, Building2, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    ALL_PROPERTIES_VALUE,
    isPanelInquirySort,
    panelInquirySortOptions,
    propertySelectorOptions,
    type PanelInquiryFiltersValue,
    type PanelInquiryPropertyOption,
} from '@/lib/panel-inquiry-filters';
import { cn } from '@/lib/utils';

/**
 * Wait after the last keystroke before searching, so typing does not trigger
 * one navigation per letter.
 */
const SEARCH_DELAY_MS = 350;

const triggerClasses =
    'h-9 w-full min-w-0 border-0 bg-transparent px-0 text-xs text-foreground shadow-none focus-visible:ring-0 dark:bg-transparent';

function useHydrated(): boolean {
    const [isHydrated, setIsHydrated] = useState(false);

    useEffect(() => {
        setIsHydrated(true);
    }, []);

    return isHydrated;
}

function emptyToNull(value: string): string | null {
    const trimmed = value.trim();

    return trimmed === '' ? null : trimmed;
}

function isTextField(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    return (
        target.isContentEditable ||
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.tagName === 'SELECT'
    );
}

function SelectSkeleton({ className }: { className?: string }) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'block h-4 animate-pulse rounded bg-muted-foreground/15',
                className,
            )}
        />
    );
}

export function PanelInquiryFilters({
    filters,
    propertyOptions,
    onApply,
}: {
    filters: PanelInquiryFiltersValue;
    propertyOptions: PanelInquiryPropertyOption[];
    onApply: (filters: PanelInquiryFiltersValue) => void;
}) {
    const isHydrated = useHydrated();
    const searchInput = useRef<HTMLInputElement>(null);
    const [searchValue, setSearchValue] = useState(filters.search ?? '');
    const [pendingFilters, setPendingFilters] = useState(filters);
    const pendingFiltersRef = useRef(filters);
    const lastAppliedSearch = useRef<string | null>(filters.search);
    const pendingSearches = useRef(new Set<string | null>());
    const selectOptions = propertySelectorOptions(
        propertyOptions,
        pendingFilters.property,
    );

    useEffect(() => {
        pendingFiltersRef.current = filters;
        setPendingFilters(filters);
    }, [filters]);

    useEffect(() => {
        function handleHistoryNavigation() {
            pendingSearches.current.clear();
        }

        window.addEventListener('popstate', handleHistoryNavigation);
        return () =>
            window.removeEventListener('popstate', handleHistoryNavigation);
    }, []);

    useEffect(() => {
        if (pendingSearches.current.delete(filters.search)) {
            return;
        }

        setSearchValue(filters.search ?? '');
        lastAppliedSearch.current = filters.search;
    }, [filters.search]);

    useEffect(() => {
        const search = emptyToNull(searchValue);

        if (search === lastAppliedSearch.current) {
            return;
        }

        const timer = window.setTimeout(() => {
            lastAppliedSearch.current = search;
            pendingSearches.current.add(search);
            const next = { ...pendingFiltersRef.current, search };
            pendingFiltersRef.current = next;
            setPendingFilters(next);
            onApply(next);
        }, SEARCH_DELAY_MS);

        return () => window.clearTimeout(timer);
    }, [searchValue, filters, onApply]);

    useEffect(() => {
        function focusSearch(event: KeyboardEvent): void {
            if (
                event.key !== '/' ||
                event.defaultPrevented ||
                event.altKey ||
                event.ctrlKey ||
                event.metaKey ||
                isTextField(event.target)
            ) {
                return;
            }

            const input = searchInput.current;

            if (input === null) {
                return;
            }

            event.preventDefault();
            input.focus();
        }

        window.addEventListener('keydown', focusSearch);

        return () => window.removeEventListener('keydown', focusSearch);
    }, []);

    /**
     * The text in the search box travels with the other controls, so a term
     * being typed is never left out of the URL applied next.
     */
    function applyFilters(changes: Partial<PanelInquiryFiltersValue>) {
        const search = emptyToNull(searchValue);

        lastAppliedSearch.current = search;
        if (search !== filters.search) {
            pendingSearches.current.add(search);
        }
        const next = { ...pendingFiltersRef.current, search, ...changes };
        pendingFiltersRef.current = next;
        setPendingFilters(next);
        onApply(next);
    }

    function changeProperty(value: string) {
        const property = value === ALL_PROPERTIES_VALUE ? null : Number(value);

        if (property !== pendingFiltersRef.current.property) {
            applyFilters({ property });
        }
    }

    function changeSort(value: string) {
        if (
            !isPanelInquirySort(value) ||
            value === pendingFiltersRef.current.sort
        ) {
            return;
        }

        applyFilters({ sort: value });
    }

    return (
        <div className="flex flex-col gap-2.5 px-4 pt-4 pb-3 md:flex-row md:items-center md:px-6">
            <label className="flex h-11 min-w-0 flex-1 items-center gap-2.5 rounded-[10px] border border-[#cbd8ce] bg-card px-3 text-muted-foreground focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 dark:border-border">
                <Search className="size-4 shrink-0" aria-hidden="true" />
                <span className="sr-only">
                    Buscar persona, propiedad o mensaje
                </span>
                <Input
                    ref={searchInput}
                    id="panel-inquiry-search"
                    name="search"
                    type="search"
                    value={searchValue}
                    onChange={(event) => setSearchValue(event.target.value)}
                    autoComplete="off"
                    placeholder="Buscar persona, propiedad o mensaje…"
                    className="h-9 border-0 bg-transparent px-0 text-xs text-foreground shadow-none focus-visible:ring-0"
                />
                <kbd
                    aria-hidden="true"
                    className="hidden size-5 shrink-0 place-items-center rounded-[4px] border border-border text-[11px] md:grid"
                >
                    /
                </kbd>
            </label>

            <div className="flex h-11 items-center gap-2.5 rounded-[10px] border border-[#cbd8ce] bg-card pr-1 pl-3 text-muted-foreground focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 md:w-[235px] dark:border-border">
                <Building2 className="size-4 shrink-0" aria-hidden="true" />
                {isHydrated ? (
                    <Select
                        value={
                            pendingFilters.property === null
                                ? ALL_PROPERTIES_VALUE
                                : String(pendingFilters.property)
                        }
                        onValueChange={changeProperty}
                    >
                        <SelectTrigger
                            aria-label="Filtrar por propiedad"
                            className={triggerClasses}
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                value={ALL_PROPERTIES_VALUE}
                                className="text-xs"
                            >
                                Todas las propiedades
                            </SelectItem>
                            {selectOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                    className="text-xs"
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : (
                    <SelectSkeleton className="w-[120px]" />
                )}
            </div>

            <div className="flex h-11 items-center gap-2.5 self-end text-muted-foreground focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 md:w-[168px] md:self-auto md:rounded-[10px] md:border md:border-[#cbd8ce] md:bg-card md:pr-1 md:pl-3 dark:border-border">
                <ArrowUpDown className="size-4 shrink-0" aria-hidden="true" />
                {isHydrated ? (
                    <Select
                        value={pendingFilters.sort}
                        onValueChange={changeSort}
                    >
                        <SelectTrigger
                            aria-label="Ordenar consultas"
                            className={triggerClasses}
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {panelInquirySortOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                    className="text-xs"
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : (
                    <SelectSkeleton className="w-[82px]" />
                )}
            </div>
        </div>
    );
}
