import PublicContainer from '@/components/public-container';
import PublicLogo from '@/components/public-logo';

export default function PublicFooter() {
    return (
        <footer className="bg-background">
            <PublicContainer className="flex min-h-[120px] flex-wrap items-center gap-x-8 gap-y-4 py-8 md:min-h-[150px]">
                <PublicLogo />
                <p className="text-xs text-muted-foreground">
                    Tu próximo hogar, tu próximo gran paso.
                </p>
                <span className="w-full text-[10px] tracking-[0.1em] text-muted-foreground md:ml-auto md:w-auto">
                    PERÚ · VENTA Y ALQUILER
                </span>
            </PublicContainer>
        </footer>
    );
}
