const LIMA_TIME_ZONE = 'America/Lima';

const dayFormatter = new Intl.DateTimeFormat('es-PE', {
    day: 'numeric',
    month: 'short',
    timeZone: LIMA_TIME_ZONE,
});

const dateTimeFormatter = new Intl.DateTimeFormat('es-PE', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: LIMA_TIME_ZONE,
});

function toDate(value: string | null): Date | null {
    if (value === null) {
        return null;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatInquiryDay(value: string | null): string {
    const date = toDate(value);

    return date === null ? 'Sin fecha' : dayFormatter.format(date);
}

export function formatInquiryDateTime(value: string | null): string {
    const date = toDate(value);

    return date === null
        ? 'Fecha no disponible'
        : dateTimeFormatter.format(date);
}
