export type PanelInquirySort = 'recent' | 'oldest';

export type PanelInquiryFiltersValue = {
    search: string | null;
    property: number | null;
    sort: PanelInquirySort;
};

export type PanelInquiryPropertyOption = {
    id: number;
    title: string;
};

export type PanelInquirySelectOption = {
    value: string;
    label: string;
};

export const ALL_PROPERTIES_VALUE = 'all';

export const panelInquirySortOptions: {
    value: PanelInquirySort;
    label: string;
}[] = [
    { value: 'recent', label: 'Más recientes' },
    { value: 'oldest', label: 'Más antiguas' },
];

export function isPanelInquirySort(value: string): value is PanelInquirySort {
    return panelInquirySortOptions.some((option) => option.value === value);
}

export function hasActivePanelInquiryFilters(
    filters: PanelInquiryFiltersValue,
): boolean {
    return filters.search !== null || filters.property !== null;
}

/**
 * Build the query the panel understands. Empty filters and the default order
 * stay out so shared URLs remain short, and the page is left out so every
 * change starts again at the first page.
 */
export function panelInquiryFiltersToQuery(
    filters: PanelInquiryFiltersValue,
): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.search !== null) {
        query.search = filters.search;
    }

    if (filters.property !== null) {
        query.property = String(filters.property);
    }

    if (filters.sort !== 'recent') {
        query.sort = filters.sort;
    }

    return query;
}

/**
 * Options of the property selector. Repeated titles also show the identifier
 * so every option filters a single property, and a filter coming from the URL
 * without accessible inquiries stays visible instead of leaving the control
 * empty.
 */
export function propertySelectorOptions(
    propertyOptions: PanelInquiryPropertyOption[],
    selectedId: number | null,
): PanelInquirySelectOption[] {
    const titleCounts = new Map<string, number>();

    for (const option of propertyOptions) {
        titleCounts.set(option.title, (titleCounts.get(option.title) ?? 0) + 1);
    }

    const options = propertyOptions.map((option) => ({
        value: String(option.id),
        label:
            (titleCounts.get(option.title) ?? 0) > 1
                ? `${option.title} (#${option.id})`
                : option.title,
    }));

    if (
        selectedId !== null &&
        !propertyOptions.some((option) => option.id === selectedId)
    ) {
        options.push({
            value: String(selectedId),
            label: `Propiedad #${selectedId}`,
        });
    }

    return options;
}
