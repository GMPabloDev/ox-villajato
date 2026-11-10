import { Head, Link, router } from '@inertiajs/react';
import { MessageSquare, SearchX, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { index as inquiriesIndex } from '@/actions/App/Http/Controllers/PropertyInquiryController';
import { PanelInquiryDetail } from '@/components/panel-inquiry-detail';
import { PanelInquiryFilters } from '@/components/panel-inquiry-filters';
import {
    PanelInquiryListItem,
    type PanelInquiryListItemData,
} from '@/components/panel-inquiry-list-item';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    hasActivePanelInquiryFilters,
    panelInquiryFiltersToQuery,
    type PanelInquiryFiltersValue,
    type PanelInquiryPropertyOption,
} from '@/lib/panel-inquiry-filters';
import { cn } from '@/lib/utils';

type PropertyInquiry = PanelInquiryListItemData & {
    email: string;
};

type PaginationLink = {
    active: boolean;
    label: string;
    url: string | null;
};

type PaginatedInquiries = {
    data: PropertyInquiry[];
    current_page: number;
    last_page: number;
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
};

function pluralize(count: number, singular: string, plural: string): string {
    return `${count} ${count === 1 ? singular : plural}`;
}

export default function PanelInquiriesIndex({
    inquiries,
    canSeeAllInquiries,
    filters,
    accessibleTotal,
    propertyOptions,
}: {
    inquiries: PaginatedInquiries;
    canSeeAllInquiries: boolean;
    filters: PanelInquiryFiltersValue;
    accessibleTotal: number;
    propertyOptions: PanelInquiryPropertyOption[];
}) {
    const [selectedId, setSelectedId] = useState<number | null>(
        inquiries.data[0]?.id ?? null,
    );
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [filtersKey, setFiltersKey] = useState(0);
    const requestedFilters = useRef(filters);

    useEffect(() => {
        requestedFilters.current = filters;
    }, [filters]);

    const selectedInquiry =
        inquiries.data.find((inquiry) => inquiry.id === selectedId) ??
        inquiries.data[0] ??
        null;

    /**
     * Keep the selection inside the visible page, so the detail always reads
     * as part of the list below it.
     */
    useEffect(() => {
        setSelectedId(selectedInquiry?.id ?? null);
    }, [selectedInquiry?.id]);

    const hasInquiries = inquiries.data.length > 0;
    const hasActiveFilters = hasActivePanelInquiryFilters(filters);
    const pageText = canSeeAllInquiries
        ? {
              description: 'Consultas recibidas de todas las propiedades.',
              emptyTitle: 'Aún no hay consultas',
              emptyDescription:
                  'Las consultas recibidas en las propiedades aparecerán aquí.',
          }
        : {
              description: 'Consultas recibidas por tus propiedades.',
              emptyTitle: 'Aún no tienes consultas',
              emptyDescription:
                  'Las consultas por tus propiedades aparecerán aquí.',
          };
    const countLabel = pluralize(
        accessibleTotal,
        'consulta recibida',
        'consultas recibidas',
    );
    const matchLabel = pluralize(
        inquiries.total,
        'coincidencia',
        'coincidencias',
    );
    const listingLabel = hasActiveFilters
        ? matchLabel
        : pluralize(inquiries.total, 'consulta', 'consultas');
    const footerLabel = hasInquiries
        ? `Mostrando ${inquiries.from ?? 0} a ${inquiries.to ?? 0} de ${listingLabel}`
        : accessibleTotal === 0
          ? 'Sin consultas por ahora'
          : 'Sin coincidencias';

    function applyFilters(next: PanelInquiryFiltersValue) {
        requestedFilters.current = next;
        router.get(inquiriesIndex.url(), panelInquiryFiltersToQuery(next), {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function clearFilters() {
        setFiltersKey((key) => key + 1);
        applyFilters({ ...requestedFilters.current, search: null, property: null });
    }

    function selectInquiry(inquiry: PanelInquiryListItemData) {
        setSelectedId(inquiry.id);

        if (window.matchMedia('(max-width: 1023px)').matches) {
            setIsDetailOpen(true);
        }
    }

    function renderDetail(inquiry: PropertyInquiry) {
        return (
            <PanelInquiryDetail
                inquiry={inquiry}
                showPublisher={canSeeAllInquiries}
            />
        );
    }

    return (
        <>
            <Head title="Consultas" />

            <div className="mx-auto w-full max-w-[1660px] space-y-7 p-4 pb-10 md:p-6 lg:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-[10px] font-semibold tracking-[0.18em] text-muted-foreground">
                            EL INTERÉS EN TUS PROPIEDADES
                        </p>
                        <h1 className="mt-2.5 font-display text-[31px] leading-tight font-extrabold tracking-[-1.4px] sm:text-[34px]">
                            Consultas
                            <span className="text-[#bf7959] dark:text-brand-warm">
                                .
                            </span>
                        </h1>
                        <p className="mt-2.5 text-[13px] leading-[1.6] text-muted-foreground">
                            {pageText.description}
                        </p>
                    </div>

                    {accessibleTotal > 0 && (
                        <div className="hidden items-center gap-3 border-l border-border pl-[18px] sm:flex">
                            <span className="grid size-[39px] shrink-0 place-items-center rounded-[11px] bg-secondary text-primary">
                                <MessageSquare
                                    className="size-[18px]"
                                    aria-hidden="true"
                                />
                            </span>
                            <div>
                                <strong className="block text-xs font-semibold">
                                    {countLabel}
                                </strong>
                                <small className="mt-1.5 block text-[10px] text-muted-foreground">
                                    Desde las fichas de propiedades
                                </small>
                            </div>
                        </div>
                    )}
                </div>

                <section
                    aria-label="Consultas recibidas"
                    className="overflow-clip rounded-[17px] border border-border bg-card shadow-sm"
                >
                    <header className="flex min-h-[58px] flex-wrap items-center justify-between gap-2 border-b border-border px-4 md:px-6">
                        <h2 className="flex items-center gap-2 text-xs font-semibold">
                            Consultas recibidas
                            <span className="rounded-[5px] bg-secondary px-1.5 py-0.5 text-[10px] font-medium text-secondary-foreground">
                                {accessibleTotal}
                            </span>
                        </h2>
                        <span className="hidden text-[10px] text-muted-foreground sm:block">
                            Ordenadas por fecha de recepción
                        </span>
                    </header>

                    <PanelInquiryFilters
                        key={filtersKey}
                        filters={filters}
                        propertyOptions={propertyOptions}
                        onApply={applyFilters}
                    />

                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-border bg-muted/40 px-4 py-3 text-xs md:px-6">
                        <p aria-live="polite" className="text-muted-foreground">
                            <strong className="font-semibold text-foreground">
                                {listingLabel}
                            </strong>{' '}
                            {hasActiveFilters
                                ? `de ${pluralize(accessibleTotal, 'consulta', 'consultas')}`
                                : 'en total'}
                        </p>
                        {hasInquiries && (
                            <span className="hidden text-[11px] text-muted-foreground sm:inline">
                                Selecciona una consulta para leerla
                            </span>
                        )}
                    </div>

                    {hasInquiries ? (
                        <div className="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.04fr)]">
                            <ul
                                aria-label="Consultas recibidas"
                                className="min-w-0 lg:border-r lg:border-border [&>li:last-child>button]:border-b-0"
                            >
                                {inquiries.data.map((inquiry) => (
                                    <PanelInquiryListItem
                                        key={inquiry.id}
                                        inquiry={inquiry}
                                        isSelected={
                                            inquiry.id === selectedInquiry?.id
                                        }
                                        showPublisher={canSeeAllInquiries}
                                        onSelect={selectInquiry}
                                    />
                                ))}
                            </ul>

                            {selectedInquiry !== null && (
                                <section
                                    aria-label="Detalle de la consulta seleccionada"
                                    className="hidden min-w-0 p-5 lg:sticky lg:top-4 lg:block lg:max-h-[calc(100dvh-2rem)] lg:self-start lg:overflow-y-auto xl:p-6"
                                >
                                    <div aria-live="polite">
                                        {renderDetail(selectedInquiry)}
                                    </div>
                                </section>
                            )}
                        </div>
                    ) : accessibleTotal === 0 ? (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center md:py-20">
                            <span className="grid size-14 place-items-center rounded-2xl bg-muted text-primary">
                                <MessageSquare
                                    className="size-6"
                                    aria-hidden="true"
                                />
                            </span>
                            <h2 className="mt-5 font-display text-[19px] font-extrabold tracking-[-0.5px]">
                                {pageText.emptyTitle}
                            </h2>
                            <p className="mt-2 max-w-sm text-xs leading-[1.8] text-muted-foreground">
                                {pageText.emptyDescription}
                            </p>
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center px-6 py-16 text-center md:py-20">
                            <span className="grid size-14 place-items-center rounded-2xl bg-muted text-primary">
                                <SearchX
                                    className="size-6"
                                    aria-hidden="true"
                                />
                            </span>
                            <h2 className="mt-5 font-display text-[19px] font-extrabold tracking-[-0.5px]">
                                No hay consultas que coincidan
                            </h2>
                            <p className="mt-2 max-w-sm text-xs leading-[1.8] text-muted-foreground">
                                Prueba con otros términos o filtros, o limpia la
                                búsqueda para volver a ver todas tus consultas.
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={clearFilters}
                                className="mt-6 h-10 gap-2 rounded-[10px] border-input px-4 text-xs font-semibold"
                            >
                                Limpiar búsqueda y filtros
                            </Button>
                        </div>
                    )}

                    <footer className="flex flex-col gap-3 border-t border-border bg-muted/30 px-4 py-3 text-[11px] text-muted-foreground md:flex-row md:items-center md:justify-between md:px-6">
                        <span>{footerLabel}</span>

                        {inquiries.last_page > 1 && (
                            <nav
                                aria-label="Paginación de consultas"
                                className="flex flex-wrap items-center gap-1.5"
                            >
                                {inquiries.links.map((link, index) => {
                                    const label =
                                        index === 0
                                            ? 'Anterior'
                                            : index ===
                                                inquiries.links.length - 1
                                              ? 'Siguiente'
                                              : link.label;

                                    if (link.url === null) {
                                        return (
                                            <span
                                                key={index}
                                                aria-disabled="true"
                                                className="grid min-h-7 min-w-7 cursor-not-allowed place-items-center rounded-[6px] border border-border bg-card px-2 text-[10px] text-muted-foreground/50"
                                            >
                                                {label}
                                            </span>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            aria-current={
                                                link.active ? 'page' : undefined
                                            }
                                            className={cn(
                                                'grid min-h-7 min-w-7 place-items-center rounded-[6px] border px-2 text-[10px] transition-colors',
                                                link.active
                                                    ? 'border-primary bg-secondary font-semibold text-secondary-foreground'
                                                    : 'border-border bg-card text-muted-foreground hover:bg-muted hover:text-foreground',
                                            )}
                                        >
                                            {label}
                                        </Link>
                                    );
                                })}
                            </nav>
                        )}

                        <span>
                            El contacto continúa en tu aplicación de correo o
                            teléfono.
                        </span>
                    </footer>
                </section>
            </div>

            <Sheet open={isDetailOpen} onOpenChange={setIsDetailOpen}>
                <SheetContent
                    side="right"
                    showCloseButton={false}
                    className="flex w-full flex-col gap-0 p-0 sm:max-w-[520px]"
                >
                    <SheetHeader className="flex-row items-start justify-between gap-3 border-b border-border px-5 py-4 text-left">
                        <div className="min-w-0">
                            <p className="text-[10px] font-semibold tracking-[0.16em] text-muted-foreground">
                                CONSULTA RECIBIDA
                            </p>
                            <SheetTitle className="mt-1.5 font-display text-lg font-bold tracking-tight">
                                Detalle de la consulta
                            </SheetTitle>
                            <SheetDescription className="sr-only">
                                Información y datos de contacto de la consulta
                                seleccionada.
                            </SheetDescription>
                        </div>

                        <SheetClose asChild>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-9 shrink-0 rounded-[9px] text-muted-foreground hover:bg-muted"
                                aria-label="Cerrar detalle"
                            >
                                <X className="size-4" aria-hidden="true" />
                            </Button>
                        </SheetClose>
                    </SheetHeader>

                    <div className="min-h-0 flex-1 overflow-y-auto p-5">
                        {selectedInquiry !== null &&
                            renderDetail(selectedInquiry)}
                    </div>
                </SheetContent>
            </Sheet>
        </>
    );
}

PanelInquiriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Consultas',
            href: inquiriesIndex(),
        },
    ],
};
