import PublicFooter from '@/components/public-footer';
import PublicHeader from '@/components/public-header';

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <PublicHeader />
            <main className="flex-1">{children}</main>
            <PublicFooter />
        </div>
    );
}
