import { Building2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

export function PropertyImage({
    src,
    alt,
    className,
    imageClassName,
    fallbackIconClassName = 'size-6',
    loading = 'lazy',
}: {
    src: string | null;
    alt: string;
    className?: string;
    imageClassName?: string;
    fallbackIconClassName?: string;
    loading?: 'eager' | 'lazy';
}) {
    const imageRef = useRef<HTMLImageElement>(null);
    const [loadedSrc, setLoadedSrc] = useState<string | null>(null);
    const [failedSrc, setFailedSrc] = useState<string | null>(null);

    const imageFailed = src !== null && failedSrc === src;
    const imageLoaded = src !== null && loadedSrc === src;

    useEffect(() => {
        const image = imageRef.current;

        if (src === null || image === null || !image.complete) {
            return;
        }

        if (image.naturalWidth > 0) {
            setLoadedSrc(src);
        } else {
            setFailedSrc(src);
        }
    }, [src]);

    return (
        <span
            className={cn('relative block overflow-hidden bg-muted', className)}
        >
            {src === null || imageFailed ? (
                <span className="grid size-full place-items-center text-muted-foreground">
                    <Building2
                        className={cn(fallbackIconClassName)}
                        aria-hidden="true"
                    />
                </span>
            ) : (
                <>
                    <img
                        ref={imageRef}
                        src={src}
                        alt={alt}
                        loading={loading}
                        decoding="async"
                        onLoad={() => setLoadedSrc(src)}
                        onError={() => setFailedSrc(src)}
                        className={cn(
                            'vj-image relative size-full object-cover transition-opacity duration-300',
                            imageLoaded && 'vj-image-ready',
                            imageClassName,
                        )}
                    />
                    <span
                        aria-hidden="true"
                        className={cn(
                            'vj-image-skeleton pointer-events-none absolute inset-0 transition-opacity duration-300',
                            imageLoaded && 'opacity-0',
                        )}
                    >
                        <span className="block size-full animate-pulse bg-muted-foreground/10" />
                    </span>
                </>
            )}
        </span>
    );
}
