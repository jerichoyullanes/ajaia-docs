import type { Editor, JSONContent } from '@tiptap/core';
import { Head, router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import RichEditor from '@/components/documents/rich-editor';
import ShareDocumentDialog from '@/components/documents/share-document-dialog';
import SaveStatus, {
    type SaveStatusValue,
} from '@/components/documents/save-status';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    show,
    update,
} from '@/actions/App/Http/Controllers/DocumentController';
import { dashboard } from '@/routes';

type Document = {
    id: number;
    title: string;
    content: JSONContent;
};

type Props = {
    document: Document;
    shares: {
        id: number;
        name: string;
        email: string;
        permission: 'view' | 'edit';
    }[];
    can: {
        update: boolean;
        share: boolean;
        delete: boolean;
    };
};

export default function DocumentsEdit({ document, shares, can }: Props) {
    const [title, setTitle] = useState(document.title);
    const [editor, setEditor] = useState<Editor | null>(null);
    const [saveStatus, setSaveStatus] = useState<SaveStatusValue>('Saved');
    const [titleError, setTitleError] = useState<string | undefined>();

    const saveDocument = useCallback(() => {
        if (!can.update || !editor) {
            return;
        }

        router.put(
            update.url(document.id),
            {
                title,
                content: editor.getJSON(),
            },
            {
                preserveScroll: true,
                onStart: () => {
                    setTitleError(undefined);
                    setSaveStatus('Saving…');
                },
                onSuccess: () => {
                    setTitleError(undefined);
                    setSaveStatus('Saved');
                },
                onError: (errors) => {
                    setTitleError(errors.title);
                    setSaveStatus('Error');
                    toast.error(
                        errors.title ??
                            errors.content ??
                            'Unable to save the document.',
                    );
                },
                onHttpException: () => {
                    setSaveStatus('Error');
                    toast.error('Unable to save the document.');
                },
                onNetworkError: () => {
                    setSaveStatus('Error');
                    toast.error('Unable to save the document.');
                },
            },
        );
    }, [can.update, document.id, editor, title]);

    useEffect(() => {
        function handleKeyDown(event: KeyboardEvent) {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === 's'
            ) {
                event.preventDefault();
                saveDocument();
            }
        }

        window.addEventListener('keydown', handleKeyDown);

        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [saveDocument]);

    return (
        <>
            <Head title={document.title} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <h1 className="sr-only">{document.title}</h1>

                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            name="title"
                            value={title}
                            onChange={(event) => {
                                setTitle(event.currentTarget.value);
                                setTitleError(undefined);
                                setSaveStatus('Unsaved changes');
                            }}
                            readOnly={!can.update}
                            required
                            maxLength={255}
                            aria-invalid={
                                titleError !== undefined ||
                                saveStatus === 'Error'
                            }
                        />
                        <InputError message={titleError} />
                    </div>

                    <div className="flex items-center gap-4">
                        {!can.update ? (
                            <Badge variant="secondary">View only</Badge>
                        ) : null}
                        {can.share ? (
                            <ShareDocumentDialog
                                documentId={document.id}
                                shares={shares}
                            />
                        ) : null}
                        <SaveStatus status={saveStatus} />
                        <Button
                            type="button"
                            disabled={
                                !can.update ||
                                !editor ||
                                saveStatus === 'Saving…'
                            }
                            onClick={saveDocument}
                        >
                            {saveStatus === 'Saving…' ? 'Saving…' : 'Save'}
                        </Button>
                    </div>
                </div>

                <RichEditor
                    content={document.content}
                    editable={can.update}
                    onChange={() => setSaveStatus('Unsaved changes')}
                    onEditorReady={setEditor}
                />
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
