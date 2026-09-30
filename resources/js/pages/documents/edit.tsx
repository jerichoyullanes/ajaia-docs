import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    show,
    update,
} from '@/actions/App/Http/Controllers/DocumentController';
import { dashboard } from '@/routes';

type JsonValue =
    | string
    | number
    | boolean
    | null
    | JsonValue[]
    | { [key: string]: JsonValue };

type DocumentContent = {
    type: 'doc';
    content: JsonValue[];
};

type Document = {
    id: number;
    title: string;
    content: DocumentContent;
};

type Props = {
    document: Document;
    can: {
        update: boolean;
        share: boolean;
        delete: boolean;
    };
};

export default function DocumentsEdit({ document, can }: Props) {
    const form = useForm({ title: document.title });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            content: document.content,
        }));
        form.put(update.url(document.id), { preserveScroll: true });
    }

    return (
        <>
            <Head title={document.title} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold">{document.title}</h1>

                <form onSubmit={submit} className="max-w-xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            name="title"
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.currentTarget.value)
                            }
                            readOnly={!can.update}
                            required
                            maxLength={255}
                            aria-invalid={Boolean(form.errors.title)}
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <Button
                        type="submit"
                        disabled={!can.update || form.processing}
                    >
                        {form.processing ? 'Saving…' : 'Save'}
                    </Button>
                </form>
            </div>
        </>
    );
}

DocumentsEdit.layout = (props: {
    currentTeam?: { slug: string } | null;
    document: Document;
}) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: props.document.title,
            href: show(props.document.id),
        },
    ],
});
