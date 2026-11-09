import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export default function PublicContainer({
    className,
    children,
}: {
    className?: string;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'mx-auto w-[calc(100%_-_2.5rem)] max-w-[1360px] md:w-[calc(100%_-_7rem)]',
                className,
            )}
        >
            {children}
        </div>
    );
}
