import { Form, Head } from '@inertiajs/react';
import { create, store } from '@/actions/App/Http/Controllers/AgentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function CreateAgent() {
    return (
        <>
            <Head title="Crear agente" />

            <div className="space-y-8 p-4">
                <Heading
                    title="Crear agente"
                    description="El agente recibirá un enlace por correo para establecer su contraseña."
                />

                <Form
                    {...store.form()}
                    resetOnSuccess={['name', 'email']}
                    className="max-w-xl space-y-6"
                    options={{ preserveScroll: true }}
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Correo</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                Crear cuenta
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateAgent.layout = {
    breadcrumbs: [
        {
            title: 'Crear agente',
            href: create(),
        },
    ],
};
