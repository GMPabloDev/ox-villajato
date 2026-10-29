export default function Heading({
    title,
    description,
    variant = 'default',
    level = 'h2',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
    level?: 'h1' | 'h2';
}) {
    const HeadingTag = level;

    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <HeadingTag
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold tracking-tight'
                }
            >
                {title}
            </HeadingTag>
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
        </header>
    );
}
