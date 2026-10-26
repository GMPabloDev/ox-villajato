import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function CreateProperty() {
    return (
        <>
            <Head title="Publicar propiedad" />

            <div className="p-4">
                <Heading
                    title="Publicar propiedad"
                    description="Completa los datos y adjunta las fotos."
                />
            </div>
        </>
    );
}
