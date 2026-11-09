import { Building2 } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

export type PropertyPhoto = {
    url: string;
    position: number;
};

export default function PropertyPhotoGallery({
    photos,
    title,
}: {
    photos: PropertyPhoto[];
    title: string;
}) {
    const [current, setCurrent] = useState(0);

    const total = photos.length;
    const canBrowse = total > 1;
    const mainPhoto = current < total ? photos[current] : undefined;
    const sidePhotos: PropertyPhoto[] = canBrowse
        ? Array.from({ length: Math.min(total - 1, 2) }, (_, offset) => {
              const index = (current + offset + 1) % total;

              return photos[index];
          })
        : [];

    const showPrevious = () => {
        setCurrent((index) => (index + total - 1) % total);
    };

    const showNext = () => {
        setCurrent((index) => (index + 1) % total);
    };

    return (
        <section aria-label="Fotografías de la propiedad">
            <div
                className={cn(
                    'grid h-[340px] gap-2.5 md:h-[510px]',
                    sidePhotos.length > 0 && 'md:grid-cols-[2fr_1fr]',
                )}
            >
                <div className="relative h-full overflow-hidden rounded-[4px] rounded-tr-[55px] bg-[#dce7df] md:rounded-tr-[70px]">
                    {mainPhoto ? (
                        <img
                            key={mainPhoto.url}
                            src={mainPhoto.url}
                            alt={`${title} — foto ${current + 1}`}
                            className="vj-photo-enter size-full object-cover"
                        />
                    ) : (
                        <div className="flex size-full items-center justify-center text-muted-foreground">
                            <Building2 className="size-10" aria-hidden="true" />
                            <span className="sr-only">
                                Esta propiedad aún no tiene fotografías
                            </span>
                        </div>
                    )}

                    {mainPhoto && (
                        <div className="absolute inset-x-3.5 bottom-3.5 flex items-center justify-between gap-2.5 md:inset-x-5 md:bottom-5">
                            <span
                                aria-live="polite"
                                className="rounded-[7px] bg-[#173e3b]/85 px-3.5 py-2.5 text-xs font-semibold text-white"
                            >
                                {String(current + 1).padStart(2, '0')} /{' '}
                                {String(total).padStart(2, '0')} fotos
                            </span>

                            {canBrowse && (
                                <div className="flex gap-[7px]">
                                    <button
                                        type="button"
                                        onClick={showPrevious}
                                        aria-label="Foto anterior"
                                        className="grid size-[42px] place-items-center rounded-full bg-card text-xl text-brand-primary transition-[transform,background-color] duration-200 hover:-translate-y-[3px] hover:bg-secondary motion-reduce:transition-none"
                                    >
                                        <span aria-hidden="true">←</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={showNext}
                                        aria-label="Foto siguiente"
                                        className="grid size-[42px] place-items-center rounded-full bg-card text-xl text-brand-primary transition-[transform,background-color] duration-200 hover:-translate-y-[3px] hover:bg-secondary motion-reduce:transition-none"
                                    >
                                        <span aria-hidden="true">→</span>
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {sidePhotos.length > 0 && (
                    <div
                        className={cn(
                            'hidden min-h-0 gap-2.5 md:grid',
                            sidePhotos.length > 1
                                ? 'grid-rows-2'
                                : 'grid-rows-1',
                        )}
                    >
                        {sidePhotos.map((photo, index) => (
                            <div
                                key={`${photo.url}-${index}`}
                                className="relative min-h-0 overflow-hidden bg-[#dce7df]"
                            >
                                <img
                                    src={photo.url}
                                    alt=""
                                    className="size-full object-cover"
                                />

                                {index === sidePhotos.length - 1 && (
                                    <button
                                        type="button"
                                        onClick={showNext}
                                        className="absolute inset-0 flex items-center justify-center gap-2 bg-[#112b2a]/30 text-sm font-bold text-white transition-colors duration-200 hover:bg-[#112b2a]/45 motion-reduce:transition-none"
                                    >
                                        Siguiente foto
                                        <span aria-hidden="true">↗</span>
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
