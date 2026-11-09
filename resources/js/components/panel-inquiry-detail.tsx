import { Building2, Mail, Phone } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/hooks/use-initials';
import { formatInquiryDateTime } from '@/lib/inquiry-date';

export type PanelInquiryDetailData = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    message: string;
    received_at: string | null;
    property: {
        title: string;
    };
    publisher?: {
        name: string | null;
    };
};

function inquiryReference(id: number): string {
    return `C-${String(id).padStart(4, '0')}`;
}

export function PanelInquiryDetail({
    inquiry,
    showPublisher,
}: {
    inquiry: PanelInquiryDetailData;
    showPublisher: boolean;
}) {
    const getInitials = useInitials();
    const hasPhone = inquiry.phone !== null && inquiry.phone.trim() !== '';
    const publisherName = inquiry.publisher?.name ?? null;

    return (
        <article className="min-w-0">
            <div className="flex flex-wrap items-center justify-between gap-2.5 text-[9px] tracking-[0.07em] text-muted-foreground">
                <span className="font-semibold uppercase">
                    Consulta {inquiryReference(inquiry.id)}
                </span>
                <time
                    dateTime={inquiry.received_at ?? undefined}
                    className="tabular-nums"
                >
                    {formatInquiryDateTime(inquiry.received_at)}
                </time>
            </div>

            <div className="mt-5 flex items-center gap-3">
                <span
                    aria-hidden="true"
                    className="grid size-[46px] shrink-0 place-items-center rounded-[13px] bg-secondary text-[13px] font-semibold text-secondary-foreground"
                >
                    {getInitials(inquiry.name)}
                </span>
                <div className="min-w-0">
                    <h2 className="font-display text-[21px] leading-[1.4] font-extrabold tracking-[-0.5px] break-words">
                        {inquiry.name}
                    </h2>
                    <p className="mt-1 text-[11px] text-muted-foreground">
                        Persona interesada
                    </p>
                </div>
            </div>

            <section
                aria-label="Propiedad de interés"
                className="mt-6 flex gap-3 rounded-[10px] border border-border bg-muted/60 px-4 py-3.5"
            >
                <span
                    aria-hidden="true"
                    className="mt-0.5 grid size-[33px] shrink-0 place-items-center rounded-lg bg-secondary text-primary"
                >
                    <Building2 className="size-4" />
                </span>
                <div className="min-w-0">
                    <p className="text-[9px] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                        Propiedad de interés
                    </p>
                    <h3 className="mt-2 text-xs leading-[1.6] font-semibold break-words">
                        {inquiry.property.title}
                    </h3>
                    {showPublisher && publisherName !== null && (
                        <p className="mt-1.5 text-[10px] leading-[1.6] text-muted-foreground">
                            Publicada por {publisherName}
                        </p>
                    )}
                </div>
            </section>

            <div className="mt-4 flex flex-wrap gap-2">
                <Button
                    asChild
                    className="h-11 flex-1 basis-[190px] rounded-[10px] text-xs font-semibold"
                >
                    <a href={`mailto:${inquiry.email}`}>
                        <Mail className="size-4" aria-hidden="true" />
                        Escribir correo
                    </a>
                </Button>

                {hasPhone && (
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 rounded-[10px] border-[#cfdcd4] text-xs font-semibold dark:border-border"
                    >
                        <a href={`tel:${inquiry.phone}`}>
                            <Phone className="size-4" aria-hidden="true" />
                            Llamar
                        </a>
                    </Button>
                )}
            </div>

            <section
                aria-label="Mensaje recibido"
                className="border-b border-border py-6"
            >
                <p className="text-[9px] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                    Mensaje
                </p>
                <p className="mt-3 text-[13px] leading-[1.95] break-words whitespace-pre-wrap text-foreground/90">
                    {inquiry.message}
                </p>
            </section>

            <section aria-label="Datos de contacto" className="pt-6">
                <p className="text-[9px] font-semibold tracking-[0.12em] text-muted-foreground uppercase">
                    Datos de contacto
                </p>

                <ul className="mt-4 grid gap-3.5">
                    <li className="flex items-start gap-2.5 text-[11px] leading-[1.6]">
                        <Mail
                            className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span className="min-w-0">
                            <a
                                href={`mailto:${inquiry.email}`}
                                className="break-all hover:underline"
                            >
                                {inquiry.email}
                            </a>
                            <small className="mt-1 block text-[9px] text-muted-foreground">
                                Correo electrónico
                            </small>
                        </span>
                    </li>

                    <li className="flex items-start gap-2.5 text-[11px] leading-[1.6]">
                        <Phone
                            className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span className="min-w-0">
                            {hasPhone ? (
                                <a
                                    href={`tel:${inquiry.phone}`}
                                    className="hover:underline"
                                >
                                    {inquiry.phone}
                                </a>
                            ) : (
                                <span className="text-muted-foreground">
                                    No dejó teléfono
                                </span>
                            )}
                            <small className="mt-1 block text-[9px] text-muted-foreground">
                                {hasPhone
                                    ? 'Teléfono de contacto'
                                    : 'Puedes contactarle por correo'}
                            </small>
                        </span>
                    </li>
                </ul>

                <p className="mt-5 text-[10px] leading-[1.8] text-muted-foreground">
                    El contacto se hace desde tu correo o teléfono. El panel no
                    registra el envío.
                </p>
            </section>
        </article>
    );
}
