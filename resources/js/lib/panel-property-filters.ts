import type { PropertyStatusValue } from '@/components/property-status-badge';
import {
    type PropertyCurrencyValue,
    type PropertyOperationValue,
    type PropertyTypeValue,
} from '@/lib/property-options';

export type PanelPropertySort = 'recent' | 'title' | 'price-asc' | 'price-desc';

export type PanelPropertyStatusFilter = Exclude<
    PropertyStatusValue,
    'archived'
>;

export type PanelPropertyFiltersValue = {
    search: string | null;
    status: PanelPropertyStatusFilter | null;
    operation: PropertyOperationValue | null;
    type: PropertyTypeValue | null;
    city: string | null;
    zone: string | null;
    currency: PropertyCurrencyValue | null;
    min_price: string | null;
    max_price: string | null;
    bedrooms: number | null;
    sort: PanelPropertySort;
};

export type PanelPropertyZonesByCity = Record<string, string[]>;

export const panelPropertySortOptions: {
    value: PanelPropertySort;
    label: string;
}[] = [
    { value: 'recent', label: 'Más recientes' },
    { value: 'title', label: 'Título A–Z' },
    { value: 'price-asc', label: 'Precio: menor primero' },
    { value: 'price-desc', label: 'Precio: mayor primero' },
];

export const PANEL_PRICE_RANGE_ERROR =
    'El precio mínimo no puede ser mayor que el precio máximo.';

export function isPriceSort(sort: PanelPropertySort): boolean {
    return sort === 'price-asc' || sort === 'price-desc';
}

export function isPanelPropertySort(value: string): value is PanelPropertySort {
    return panelPropertySortOptions.some((option) => option.value === value);
}

/**
 * Build the query the panel understands, leaving out empty filters and the
 * default order so shared URLs stay short.
 */
export function panelFiltersToQuery(
    filters: PanelPropertyFiltersValue,
): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.search !== null) {
        query.search = filters.search;
    }

    if (filters.status !== null) {
        query.status = filters.status;
    }

    if (filters.operation !== null) {
        query.operation = filters.operation;
    }

    if (filters.type !== null) {
        query.type = filters.type;
    }

    if (filters.city !== null) {
        query.city = filters.city;
    }

    if (filters.zone !== null) {
        query.zone = filters.zone;
    }

    if (filters.currency !== null) {
        query.currency = filters.currency;

        if (filters.min_price !== null) {
            query.min_price = filters.min_price;
        }

        if (filters.max_price !== null) {
            query.max_price = filters.max_price;
        }
    }

    if (filters.bedrooms !== null) {
        query.bedrooms = String(filters.bedrooms);
    }

    if (filters.sort !== 'recent') {
        query.sort = filters.sort;
    }

    return query;
}

export function hasActivePanelFilters(
    filters: PanelPropertyFiltersValue,
): boolean {
    return (
        filters.search !== null ||
        filters.status !== null ||
        filters.operation !== null ||
        filters.type !== null ||
        filters.city !== null ||
        filters.zone !== null ||
        filters.currency !== null ||
        filters.min_price !== null ||
        filters.max_price !== null ||
        filters.bedrooms !== null
    );
}

function compareText(first: string, second: string): number {
    return first.localeCompare(second, 'es', { sensitivity: 'base' });
}

export function sortLocations(locations: string[]): string[] {
    return [...locations].sort(compareText);
}

/**
 * Zones that belong to a city. A null city lists every accessible zone.
 */
export function zonesForCity(
    zonesByCity: PanelPropertyZonesByCity,
    city: string | null,
): string[] {
    if (city === null) {
        return sortLocations([
            ...new Set(Object.values(zonesByCity).flatMap((zones) => zones)),
        ]);
    }

    const zones = Object.hasOwn(zonesByCity, city)
        ? zonesByCity[city]
        : undefined;

    return Array.isArray(zones) ? zones : [];
}

/**
 * Keep a value that only exists in the URL visible in a select that lists the
 * current options.
 */
export function withCurrentValue(
    options: string[],
    currentValue: string | null,
): string[] {
    if (currentValue === null || options.includes(currentValue)) {
        return options;
    }

    return sortLocations([...options, currentValue]);
}
