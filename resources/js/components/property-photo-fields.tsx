import { AlertTriangle, ArrowLeft, ArrowRight, Upload, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type ExistingPropertyPhoto = {
    id: number;
    url: string;
    position: number;
};

type NewPropertyPhoto = {
    key: number;
    file: File;
    url: string;
};

type PhotoDimensions = {
    width: number;
    height: number;
};

type PhotoDimensionIssue = 'size' | 'aspect-ratio';

type PhotoDimensionReview =
    | { status: 'pending' }
    | {
          status: 'checked';
          dimensions: PhotoDimensions;
          issues: PhotoDimensionIssue[];
      }
    | { status: 'unreadable' };

type PhotoReviewSummary = {
    isPending: boolean;
    hasIssues: boolean;
    hasFailures: boolean;
};

const MAX_PHOTOS = 8;
const MAX_FILE_SIZE = 5 * 1024 * 1024;
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

const MIN_PHOTO_WIDTH = 1600;
const MIN_PHOTO_HEIGHT = 1067;
const MIN_PHOTO_RATIO = 1.425;
const MAX_PHOTO_RATIO = 1.575;
const PHOTO_DIMENSION_TIMEOUT = 10_000;

/**
 * Aplica por separado las recomendaciones de tamaño y de proporción. Los
 * extremos del intervalo de proporción y el mínimo de tamaño son válidos.
 */
function photoDimensionIssues({
    width,
    height,
}: PhotoDimensions): PhotoDimensionIssue[] {
    const issues: PhotoDimensionIssue[] = [];

    if (width < MIN_PHOTO_WIDTH || height < MIN_PHOTO_HEIGHT) {
        issues.push('size');
    }

    const ratio = width / height;

    if (ratio < MIN_PHOTO_RATIO || ratio > MAX_PHOTO_RATIO) {
        issues.push('aspect-ratio');
    }

    return issues;
}

function formatPhotoRatio({ width, height }: PhotoDimensions): string {
    return (width / height).toFixed(2).replace('.', ',');
}

/**
 * Lee las dimensiones de la foto y falla si la imagen no carga o si la lectura
 * supera el tiempo máximo, para no dejar el formulario bloqueado.
 */
function readPhotoDimensions(url: string): Promise<PhotoDimensions> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        let isTimedOut = false;

        const timer = window.setTimeout(() => {
            isTimedOut = true;
            image.removeAttribute('src');
            reject(new Error('La lectura de dimensiones agotó el tiempo.'));
        }, PHOTO_DIMENSION_TIMEOUT);

        image.onload = () => {
            if (isTimedOut) {
                return;
            }

            window.clearTimeout(timer);

            if (image.naturalWidth === 0 || image.naturalHeight === 0) {
                reject(new Error('La imagen no informó sus dimensiones.'));

                return;
            }

            resolve({
                width: image.naturalWidth,
                height: image.naturalHeight,
            });
        };

        image.onerror = () => {
            if (isTimedOut) {
                return;
            }

            window.clearTimeout(timer);
            reject(
                new Error('No se pudieron leer las dimensiones de la foto.'),
            );
        };

        image.src = url;
    });
}

function describeReviewStatus({
    isPending,
    hasIssues,
    hasFailures,
}: PhotoReviewSummary): string {
    if (isPending) {
        return 'Revisando las dimensiones de las fotos nuevas…';
    }

    if (hasIssues) {
        return 'Dimensiones revisadas. Hay avisos que no impiden guardar.';
    }

    if (hasFailures) {
        return 'Dimensiones revisadas. No pudimos comprobar algunas fotos y puedes guardar igualmente.';
    }

    return 'Dimensiones de las fotos nuevas revisadas.';
}

function PhotoReviewNotice({
    id,
    review,
}: {
    id: string;
    review: PhotoDimensionReview | undefined;
}) {
    if (review === undefined) {
        return null;
    }

    if (review.status === 'pending') {
        return (
            <p
                id={id}
                className="text-[11px] leading-relaxed text-muted-foreground"
            >
                Revisando las dimensiones…
            </p>
        );
    }

    if (review.status === 'unreadable') {
        return (
            <p
                id={id}
                className="text-[11px] leading-relaxed text-muted-foreground"
            >
                No pudimos comprobar sus dimensiones. Puedes continuar y
                guardarla.
            </p>
        );
    }

    const { dimensions, issues } = review;
    const size = `${dimensions.width} × ${dimensions.height} px`;

    if (issues.length === 0) {
        return (
            <p
                id={id}
                className="text-[11px] leading-relaxed text-muted-foreground"
            >
                Tamaño {size}. Cumple las recomendaciones.
            </p>
        );
    }

    return (
        <div
            id={id}
            className="rounded-md border border-warning/40 bg-warning/10 px-2 py-1.5 text-[11px] leading-relaxed text-foreground"
        >
            <p className="flex items-start gap-1 font-semibold text-warning">
                <AlertTriangle
                    className="mt-px size-3 shrink-0"
                    aria-hidden="true"
                />
                Aviso: no impide guardar
            </p>
            <ul className="mt-1 space-y-1">
                {issues.includes('size') && (
                    <li>
                        Tamaño {size}. El mínimo recomendado es 1600 × 1067 px.
                    </li>
                )}
                {issues.includes('aspect-ratio') && (
                    <li>
                        Proporción {formatPhotoRatio(dimensions)}. Se recomienda
                        3:2 horizontal.
                    </li>
                )}
            </ul>
            <p className="mt-1">Puedes quitarla o continuar y guardarla.</p>
        </div>
    );
}

export default function PropertyPhotoFields({
    errors,
    existingPhotos = [],
    onReviewPendingChange,
}: {
    errors: Record<string, string>;
    existingPhotos?: ExistingPropertyPhoto[];
    onReviewPendingChange?: (isPending: boolean) => void;
}) {
    const [keptPhotos, setKeptPhotos] = useState<ExistingPropertyPhoto[]>(() =>
        [...existingPhotos].sort((a, b) => a.position - b.position),
    );
    const [newPhotos, setNewPhotos] = useState<NewPropertyPhoto[]>([]);
    const [reviews, setReviews] = useState<
        Record<number, PhotoDimensionReview | undefined>
    >({});
    const [clientError, setClientError] = useState<string | null>(null);
    const [isDragging, setIsDragging] = useState(false);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const nextPhotoKey = useRef(0);
    const newPhotosRef = useRef(newPhotos);
    newPhotosRef.current = newPhotos;

    useEffect(() => {
        return () => {
            newPhotosRef.current.forEach((photo) => {
                URL.revokeObjectURL(photo.url);
            });
        };
    }, []);

    useEffect(() => {
        const input = fileInputRef.current;

        if (input === null) {
            return;
        }

        const transfer = new DataTransfer();
        newPhotos.forEach((photo) => transfer.items.add(photo.file));
        input.files = transfer.files;
    }, [newPhotos]);

    const totalPhotos = keptPhotos.length + newPhotos.length;

    const isReviewPending = newPhotos.some(
        (photo) => reviews[photo.key]?.status === 'pending',
    );
    const hasIssuePhotos = newPhotos.some((photo) => {
        const review = reviews[photo.key];

        return review?.status === 'checked' && review.issues.length > 0;
    });
    const hasUnreadablePhotos = newPhotos.some(
        (photo) => reviews[photo.key]?.status === 'unreadable',
    );

    useEffect(() => {
        onReviewPendingChange?.(isReviewPending);
    }, [isReviewPending, onReviewPendingChange]);

    useEffect(() => {
        return () => {
            onReviewPendingChange?.(false);
        };
    }, [onReviewPendingChange]);

    /** Deja el resultado asociado a la clave de la foto; ignora resultados tardíos. */
    async function reviewPhotoDimensions(key: number, url: string) {
        let review: PhotoDimensionReview;

        try {
            const dimensions = await readPhotoDimensions(url);

            review = {
                status: 'checked',
                dimensions,
                issues: photoDimensionIssues(dimensions),
            };
        } catch {
            review = { status: 'unreadable' };
        }

        setReviews((current) => {
            if (!Object.hasOwn(current, key)) {
                return current;
            }

            return { ...current, [key]: review };
        });
    }

    function addFiles(files: FileList | null) {
        if (files === null || files.length === 0) {
            return;
        }

        const acceptedPhotos: NewPropertyPhoto[] = [];
        const messages = new Set<string>();

        for (const file of Array.from(files)) {
            if (totalPhotos + acceptedPhotos.length >= MAX_PHOTOS) {
                messages.add('Puedes añadir como máximo 8 fotos.');
                break;
            }

            if (!ACCEPTED_TYPES.includes(file.type)) {
                messages.add('Solo se admiten fotos JPG, PNG o WebP.');
                continue;
            }

            if (file.size > MAX_FILE_SIZE) {
                messages.add('Cada foto debe pesar como máximo 5 MB.');
                continue;
            }

            acceptedPhotos.push({
                key: nextPhotoKey.current++,
                file,
                url: URL.createObjectURL(file),
            });
        }

        if (acceptedPhotos.length > 0) {
            setNewPhotos((photos) => [...photos, ...acceptedPhotos]);
            setReviews((current) => {
                const next = { ...current };

                acceptedPhotos.forEach((photo) => {
                    next[photo.key] = { status: 'pending' };
                });

                return next;
            });
            acceptedPhotos.forEach((photo) => {
                void reviewPhotoDimensions(photo.key, photo.url);
            });
        }

        setClientError(
            messages.size > 0 ? Array.from(messages).join(' ') : null,
        );
    }

    function moveKeptPhoto(index: number, direction: -1 | 1) {
        const target = index + direction;

        if (target < 0 || target >= keptPhotos.length) {
            return;
        }

        setKeptPhotos((photos) => {
            const next = [...photos];
            [next[index], next[target]] = [next[target], next[index]];

            return next;
        });
    }

    function removeKeptPhoto(id: number) {
        setKeptPhotos((photos) => photos.filter((photo) => photo.id !== id));
    }

    function moveNewPhoto(index: number, direction: -1 | 1) {
        const target = index + direction;

        if (target < 0 || target >= newPhotos.length) {
            return;
        }

        setNewPhotos((photos) => {
            const next = [...photos];
            [next[index], next[target]] = [next[target], next[index]];

            return next;
        });
    }

    function removeNewPhoto(key: number) {
        const photo = newPhotos.find((item) => item.key === key);

        if (photo !== undefined) {
            URL.revokeObjectURL(photo.url);
        }

        setNewPhotos((photos) => photos.filter((item) => item.key !== key));
        setReviews((current) => {
            if (!Object.hasOwn(current, key)) {
                return current;
            }

            const next = { ...current };
            delete next[key];

            return next;
        });
    }

    return (
        <div className="space-y-4">
            <label
                htmlFor="property-photos"
                className={cn(
                    'flex cursor-pointer flex-col items-center rounded-xl border-[1.5px] border-dashed border-primary/35 bg-secondary/25 px-4 py-7 text-center transition-colors has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-ring/50',
                    isDragging && 'border-primary bg-secondary/60',
                )}
                onDragEnter={(event) => {
                    event.preventDefault();
                    setIsDragging(true);
                }}
                onDragOver={(event) => {
                    event.preventDefault();
                    setIsDragging(true);
                }}
                onDragLeave={(event) => {
                    event.preventDefault();
                    setIsDragging(false);
                }}
                onDrop={(event) => {
                    event.preventDefault();
                    setIsDragging(false);
                    addFiles(event.dataTransfer.files);
                }}
            >
                <span className="mb-3 grid size-10 place-items-center rounded-xl bg-secondary text-primary">
                    <Upload className="size-5" />
                </span>
                <span className="text-[13px] font-semibold text-primary">
                    Elige fotos o arrástralas aquí
                </span>
                <span className="mt-1 text-xs leading-relaxed text-muted-foreground">
                    De 1 a 8 fotos · JPG, PNG o WebP · Máximo 5 MB cada una
                </span>
                <input
                    ref={fileInputRef}
                    id="property-photos"
                    name="photos[]"
                    type="file"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    className="sr-only"
                    onChange={(event) => {
                        addFiles(event.target.files);
                        event.target.value = '';
                    }}
                />
            </label>

            <p className="text-xs leading-relaxed text-muted-foreground">
                <span className="font-semibold text-foreground">
                    Recomendaciones:
                </span>{' '}
                fotos horizontales en proporción 3:2, de al menos 1600 × 1067
                píxeles y con un tamaño ideal de 2000 × 1333 píxeles. Las fotos
                que no las cumplan se pueden guardar igualmente.
            </p>

            {newPhotos.length > 0 && (
                <p
                    role="status"
                    className={cn(
                        'text-xs leading-relaxed',
                        isReviewPending
                            ? 'text-foreground'
                            : 'text-muted-foreground',
                    )}
                >
                    {describeReviewStatus({
                        isPending: isReviewPending,
                        hasIssues: hasIssuePhotos,
                        hasFailures: hasUnreadablePhotos,
                    })}
                </p>
            )}

            {totalPhotos > 0 && (
                <ul className="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                    {keptPhotos.map((photo, index) => (
                        <li
                            key={`existing-${photo.id}`}
                            className="min-w-0 overflow-hidden rounded-lg border border-border bg-card"
                        >
                            <div className="relative aspect-[4/3] bg-muted">
                                <img
                                    src={photo.url}
                                    alt={`Foto ${index + 1} de la propiedad`}
                                    className="size-full object-cover"
                                    loading="lazy"
                                />
                                {index === 0 && (
                                    <span className="absolute top-2 left-2 rounded-full bg-neutral-900/85 px-2 py-0.5 text-[10px] font-semibold text-white">
                                        Portada
                                    </span>
                                )}
                            </div>
                            <div className="space-y-2 p-2">
                                <p className="truncate text-[11px] font-semibold text-foreground">
                                    Foto {index + 1}
                                </p>
                                <div className="flex items-center justify-between gap-1">
                                    <div className="flex gap-1">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            className="size-7"
                                            disabled={index === 0}
                                            onClick={() =>
                                                moveKeptPhoto(index, -1)
                                            }
                                            aria-label={`Mover foto ${index + 1} a la izquierda`}
                                        >
                                            <ArrowLeft className="size-3.5" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            className="size-7"
                                            disabled={
                                                index === keptPhotos.length - 1
                                            }
                                            onClick={() =>
                                                moveKeptPhoto(index, 1)
                                            }
                                            aria-label={`Mover foto ${index + 1} a la derecha`}
                                        >
                                            <ArrowRight className="size-3.5" />
                                        </Button>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-7 text-muted-foreground hover:text-destructive"
                                        onClick={() =>
                                            removeKeptPhoto(photo.id)
                                        }
                                        aria-label={`Quitar foto ${index + 1}`}
                                    >
                                        <X className="size-3.5" />
                                    </Button>
                                </div>
                            </div>
                            <input
                                type="hidden"
                                name="photo_order[]"
                                value={photo.id}
                            />
                        </li>
                    ))}

                    {newPhotos.map((photo, index) => {
                        const review = reviews[photo.key];
                        const reviewId = `photo-review-${photo.key}`;
                        const describedBy =
                            review === undefined ? undefined : reviewId;

                        return (
                            <li
                                key={`new-${photo.key}`}
                                className="min-w-0 overflow-hidden rounded-lg border border-border bg-card"
                            >
                                <div className="aspect-[4/3] bg-muted">
                                    <img
                                        src={photo.url}
                                        alt={`Vista previa de ${photo.file.name}`}
                                        className="size-full object-cover"
                                        aria-describedby={describedBy}
                                    />
                                </div>
                                <div className="space-y-2 p-2">
                                    <p
                                        className="truncate text-[11px] font-semibold text-foreground"
                                        title={photo.file.name}
                                    >
                                        Nueva · {photo.file.name}
                                    </p>
                                    <div className="flex items-center justify-between gap-1">
                                        <div className="flex gap-1">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                className="size-7"
                                                disabled={index === 0}
                                                onClick={() =>
                                                    moveNewPhoto(index, -1)
                                                }
                                                aria-label={`Mover ${photo.file.name} a la izquierda`}
                                            >
                                                <ArrowLeft className="size-3.5" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                className="size-7"
                                                disabled={
                                                    index ===
                                                    newPhotos.length - 1
                                                }
                                                onClick={() =>
                                                    moveNewPhoto(index, 1)
                                                }
                                                aria-label={`Mover ${photo.file.name} a la derecha`}
                                            >
                                                <ArrowRight className="size-3.5" />
                                            </Button>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="size-7 text-muted-foreground hover:text-destructive"
                                            onClick={() =>
                                                removeNewPhoto(photo.key)
                                            }
                                            aria-label={`Quitar ${photo.file.name}`}
                                            aria-describedby={describedBy}
                                        >
                                            <X className="size-3.5" />
                                        </Button>
                                    </div>
                                    <PhotoReviewNotice
                                        id={reviewId}
                                        review={review}
                                    />
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            <p className="text-xs text-muted-foreground">
                {totalPhotos} de {MAX_PHOTOS} fotos añadidas
            </p>

            <InputError message={clientError ?? undefined} />

            <InputError message={errors.photos} />

            {Object.entries(errors)
                .filter(
                    ([key]) =>
                        key.startsWith('photos.') ||
                        key === 'photo_order' ||
                        key.startsWith('photo_order.'),
                )
                .map(([key, message]) => (
                    <InputError key={key} message={message} />
                ))}
        </div>
    );
}
