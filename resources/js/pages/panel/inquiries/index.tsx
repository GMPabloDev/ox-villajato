import { Head, Link } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import { index as inquiriesIndex } from '@/actions/App/Http/Controllers/PropertyInquiryController';
import Heading from '@/components/heading';

type PropertyInquiry = {
    id: number;
    property: {
        title: string;
    };
    publisher?: {
        name: string | null;
    };
    name: string;
    email: string;
    phone: string | null;
    message: string;
    received_at: string | null;
};

type PaginationLink = {
    active: boolean;
    label: string;
    url: string | null;
};

type PaginatedInquiries = {
    data: PropertyInquiry[];
    last_page: number;
    links: PaginationLink[];
};

export default function PanelInquiriesIndex({
    inquiries,
    canSeeAllInquiries,
}: {
    inquiries: PaginatedInquiries;
    canSeeAllInquiries: boolean;
}) {
    const pageText = canSeeAllInquiries
        ? {
              description: 'Consultas recibidas de todas las propiedades.',
              emptyTitle: 'Aún no hay consultas.',
              emptyDescription:
                  'Las consultas recibidas en las propiedades aparecerán aquí.',
          }
        : {
              description: 'Consultas recibidas por tus propiedades.',
              emptyTitle: 'Aún no tienes consultas.',
              emptyDescription:
                  'Las consultas por tus propiedades aparecerán aquí.',
          };

    return (
        <>
            <Head title="Consultas" />

            <div className="space-y-6 p-4">
                <Heading
                    level="h1"
                    title="Consultas"
                    description={pageText.description}
                />

                {inquiries.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border py-20 text-center ">
                        <MessageSquare className="size-10 text-muted-foreground" />
                        <p className="font-medium">{pageText.emptyTitle}</p>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            {pageText.emptyDescription}
                        </p>
                    </div>
                ) : (
                    <>
                        <ul className="grid gap-4">
                            {inquiries.data.map((inquiry) => (
                                <li
                                    key={inquiry.id}
                                    className="rounded-xl border border-border bg-card p-4 "
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div className="space-y-1">
                                            <h2 className="font-semibold">
                                                {inquiry.property.title}
                                            </h2>
                                            {canSeeAllInquiries &&
                                                inquiry.publisher?.name && (
                                                    <p className="text-sm text-muted-foreground">
                                                        Publicada por{' '}
                                                        {inquiry.publisher.name}
                                                    </p>
                                                )}
                                            <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                                {inquiry.name}
                                            </p>
                                        </div>
                                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                                            Recibida:{' '}
                                            <time
                                                dateTime={
                                                    inquiry.received_at ??
                                                    undefined
                                                }
                                            >
                                                {inquiry.received_at
                                                    ? new Date(
                                                          inquiry.received_at,
                                                      ).toLocaleString('es-PE')
                                                    : ''}
                                            </time>
                                        </p>
                                    </div>

                                    <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div className="grid gap-1">
                                            <dt className="text-sm text-neutral-500 dark:text-neutral-400">
                                                Correo
                                            </dt>
                                            <dd>
                                                <a
                                                    href={`mailto:${inquiry.email}`}
                                                >
                                                    {inquiry.email}
                                                </a>
                                            </dd>
                                        </div>
                                        {inquiry.phone !== null && (
                                            <div className="grid gap-1">
                                                <dt className="text-sm text-muted-foreground">
                                                    Teléfono
                                                </dt>
                                                <dd>
                                                    <a
                                                        href={`tel:${inquiry.phone}`}
                                                    >
                                                        {inquiry.phone}
                                                    </a>
                                                </dd>
                                            </div>
                                        )}
                                    </dl>

                                    <div className="mt-4 grid gap-1">
                                        <h3 className="text-sm text-muted-foreground">
                                            Mensaje
                                        </h3>
                                        <p className="whitespace-pre-wrap">
                                            {inquiry.message}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        {inquiries.last_page > 1 && (
                            <nav
                                aria-label="Paginación de consultas"
                                className="flex flex-wrap justify-center gap-2"
                            >
                                {inquiries.links.map((link, index) => {
                                    const label =
                                        index === 0
                                            ? 'Anterior'
                                            : index ===
                                                inquiries.links.length - 1
                                              ? 'Siguiente'
                                              : link.label;
                                    const className = link.active
                                        ? 'rounded-md border border-neutral-900 bg-neutral-900 px-3 py-2 text-sm text-white dark:border-neutral-100 dark:bg-neutral-100 dark:text-neutral-900'
                                        : 'rounded-md border border-neutral-200 bg-white px-3 py-2 text-sm text-neutral-700 hover:bg-neutral-100 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800';

                                    return link.url === null ? (
                                        <span
                                            key={index}
                                            aria-disabled="true"
                                            className={`${className} cursor-not-allowed opacity-50`}
                                        >
                                            {label}
                                        </span>
                                    ) : (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            aria-current={
                                                link.active ? 'page' : undefined
                                            }
                                            className={className}
                                        >
                                            {label}
                                        </Link>
                                    );
                                })}
                            </nav>
                        )}
                    </>
                )}
            </div>
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
