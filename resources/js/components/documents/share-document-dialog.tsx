import { Form, router } from '@inertiajs/react';
import { Share2, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    destroy,
    store,
} from '@/actions/App/Http/Controllers/DocumentShareController';

type Share = {
    id: number;
    name: string;
    email: string;
    permission: 'view' | 'edit';
};

type Props = {
    documentId: number;
    shares: Share[];
};

export default function ShareDocumentDialog({ documentId, shares }: Props) {
    const [open, setOpen] = useState(false);
    const [permission, setPermission] = useState<'view' | 'edit'>('edit');

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="outline">
                    <Share2 /> Share
                </Button>
            </DialogTrigger>

            <DialogContent>
                <Form
                    key={String(open)}
                    {...store.form(documentId)}
                    className="space-y-6"
                    resetOnSuccess
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Share document</DialogTitle>
                                <DialogDescription>
                                    Add a person by email and choose their
                                    access level.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="share-email">
                                        Email address
                                    </Label>
                                    <Input
                                        id="share-email"
                                        name="email"
                                        type="email"
                                        placeholder="colleague@example.com"
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="share-permission">
                                        Permission
                                    </Label>
                                    <Select
                                        name="permission"
                                        value={permission}
                                        onValueChange={(
                                            value: 'view' | 'edit',
                                        ) => setPermission(value)}
                                    >
                                        <SelectTrigger
                                            id="share-permission"
                                            className="w-full"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="edit">
                                                Can edit
                                            </SelectItem>
                                            <SelectItem value="view">
                                                View only
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.permission} />
                                </div>
                            </div>

                            <Button type="submit" disabled={processing}>
                                {processing ? 'Sharing…' : 'Add'}
                            </Button>
                        </>
                    )}
                </Form>

                <section
                    className="grid gap-3"
                    aria-labelledby="shares-heading"
                >
                    <h3 id="shares-heading" className="text-sm font-medium">
                        People with access
                    </h3>

                    {shares.length > 0 ? (
                        <ul className="grid gap-2">
                            {shares.map((share) => (
                                <li
                                    key={share.id}
                                    className="flex items-center justify-between gap-3 rounded-md border p-3"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {share.name}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {share.email}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <Badge variant="secondary">
                                            {share.permission === 'view'
                                                ? 'View only'
                                                : 'Can edit'}
                                        </Badge>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remove access for ${share.email}`}
                                            onClick={() =>
                                                router.delete(
                                                    destroy.url([
                                                        documentId,
                                                        share.id,
                                                    ]),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            <Trash2 />
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            This document has not been shared yet.
                        </p>
                    )}

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="secondary">Done</Button>
                        </DialogClose>
                    </DialogFooter>
                </section>
            </DialogContent>
        </Dialog>
    );
}
