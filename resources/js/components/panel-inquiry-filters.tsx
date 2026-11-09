import { ArrowUpDown, Building2, Clock3, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const triggerClasses =
    'h-9 w-full min-w-0 border-0 bg-transparent px-0 text-xs text-foreground shadow-none focus-visible:ring-0 dark:bg-transparent';

function useHydrated(): boolean {
    const [isHydrated, setIsHydrated] = useState(false);

    useEffect(() => {
        setIsHydrated(true);
    }, []);

    return isHydrated;
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
    propertyTitles,
}: {
    propertyTitles: string[];
}) {
    const isHydrated = useHydrated();

    return (
        <>
            <div className="flex flex-col gap-2.5 px-4 pt-4 pb-3 md:flex-row md:items-center md:px-6">
                <label className="flex h-11 min-w-0 flex-1 items-center gap-2.5 rounded-[10px] border border-[#cbd8ce] bg-card px-3 text-muted-foreground focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 dark:border-border">
                    <Search className="size-4 shrink-0" aria-hidden="true" />
                    <span className="sr-only">
                        Buscar persona, propiedad o mensaje
                    </span>
                    <Input
                        type="search"
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
                        <Select defaultValue="all">
                            <SelectTrigger
                                aria-label="Filtrar por propiedad"
                                className={triggerClasses}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all" className="text-xs">
                                    Todas las propiedades
                                </SelectItem>
                                {propertyTitles.map((title) => (
                                    <SelectItem
                                        key={title}
                                        value={title}
                                        className="text-xs"
                                    >
                                        {title}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ) : (
                        <SelectSkeleton className="w-[120px]" />
                    )}
                </div>

                <div className="flex h-11 items-center gap-2.5 self-end text-muted-foreground focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50 md:w-[168px] md:self-auto md:rounded-[10px] md:border md:border-[#cbd8ce] md:bg-card md:pr-1 md:pl-3 dark:border-border">
                    <ArrowUpDown
                        className="size-4 shrink-0"
                        aria-hidden="true"
                    />
                    {isHydrated ? (
                        <Select defaultValue="recent">
                            <SelectTrigger
                                aria-label="Ordenar consultas"
                                className={triggerClasses}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="recent" className="text-xs">
                                    Más recientes
                                </SelectItem>
                                <SelectItem value="oldest" className="text-xs">
                                    Más antiguas
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    ) : (
                        <SelectSkeleton className="w-[82px]" />
                    )}
                </div>
            </div>

            <p className="flex items-center gap-1.5 px-4 pb-3 text-[10px] text-muted-foreground md:px-6">
                <Clock3 className="size-3.5 shrink-0" aria-hidden="true" />
                La búsqueda y los filtros todavía no aplican a las consultas.
            </p>
        </>
    );
}
