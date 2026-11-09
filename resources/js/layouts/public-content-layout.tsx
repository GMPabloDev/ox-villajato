import PublicContainer from '@/components/public-container';

export default function PublicContentLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <PublicContainer className="py-10 lg:py-14">{children}</PublicContainer>
    );
}
