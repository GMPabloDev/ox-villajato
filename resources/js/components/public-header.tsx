import PublicContainer from '@/components/public-container';
import PublicLogo from '@/components/public-logo';
import { home } from '@/routes';

export default function PublicHeader() {
    const catalogUrl = `${home.url()}#propiedades`;
    const introUrl = `${home.url()}#como-funciona`;

    return (
        <header className="bg-background">
            <PublicContainer className="flex h-[72px] items-center justify-between gap-3 md:h-[92px] md:gap-6">
                <PublicLogo />

                <nav
                    aria-label="Navegación principal"
                    className="hidden items-center gap-10 text-[13px] font-semibold text-muted-foreground md:flex"
                >
                    <a href={catalogUrl} className="hover:text-primary">
                        Propiedades
                    </a>
                    <a href={introUrl} className="hover:text-primary">
                        Cómo funciona
                    </a>
                </nav>

                <a
                    href={catalogUrl}
                    className="border-b border-primary pb-1.5 text-xs font-bold text-primary md:pb-2.5 md:text-[13px]"
                >
                    Encontrar mi hogar{' '}
                    <span aria-hidden="true" className="pl-1">
                        ↗
                    </span>
                </a>
            </PublicContainer>
        </header>
    );
}
