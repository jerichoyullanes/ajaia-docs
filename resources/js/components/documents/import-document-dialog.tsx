import { Form } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { importDocument } from '@/actions/App/Http/Controllers/DocumentController';

export default function ImportDocumentDialog() {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline">
                    <Upload /> Import file
                </Button>
            </DialogTrigger>

            <DialogContent>
                <Form
                    key={String(open)}
                    {...importDocument.form()}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Import a document</DialogTitle>
                                <DialogDescription>
                                    Import a TXT or Markdown file up to 1 MB.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="import-file">File</Label>
                                <Input
                                    id="import-file"
                                    name="file"
                                    type="file"
                                    accept=".txt,.md"
                                    required
                                />
                                <InputError message={errors.file} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Importing…' : 'Import'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
