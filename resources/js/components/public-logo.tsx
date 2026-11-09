import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import { cn } from '@/lib/utils';

export default function PublicLogo({ className }: { className?: string }) {
    return (
        <Link
            href={home()}
            aria-label="VillaJato, inicio"
            className={cn('flex shrink-0 items-center gap-2', className)}
        >
            <svg
                viewBox="0 0 48 48"
                fill="none"
                aria-hidden="true"
                className="size-5 text-brand-primary md:size-7"
            >
                <path
                    d="M9 28C16 18 24 13 37 11"
                    stroke="currentColor"
                    strokeWidth="5.5"
                    strokeLinecap="round"
                />
                <path
                    d="M12 39C23 36 30 31 36 22"
                    stroke="currentColor"
                    strokeWidth="5.5"
                    strokeLinecap="round"
                />
            </svg>
            <span className="font-display text-[20px] leading-none font-extrabold tracking-[-0.04em] text-foreground md:text-[24px]">
                Villa<span className="text-brand-primary">Jato</span>
            </span>
        </Link>
    );
}
