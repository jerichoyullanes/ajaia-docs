import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    destroy,
    show,
    store,
} from '@/actions/App/Http/Controllers/DocumentController';
import { dashboard } from '@/routes';
import type { DashboardInvitation } from '@/types';

type DocumentCan = {
    update: boolean;
    share: boolean;
    delete: boolean;
};

type OwnedDocument = {
    id: number;
    title: string;
    updatedAt: string | null;
    can: DocumentCan;
};

type SharedDocument = OwnedDocument & {
    ownerName: string;
    permission: 'view' | 'edit';
};

type Props = {
    owned: OwnedDocument[];
    shared: SharedDocument[];
    pendingInvitations?: DashboardInvitation[];
};

export default function Dashboard({
    owned,
    shared,
    pendingInvitations = [],
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />

            <div className="flex flex-1 flex-col gap-8 p-4">
                <section className="space-y-4" aria-labelledby="owned-heading">
                    <div className="flex items-center justify-between gap-4">
                        <h2
                            id="owned-heading"
                            className="text-lg font-semibold"
                        >
                            My Documents
                        </h2>
                        <Button asChild>
                            <Link href={store()} method="post" as="button">
                                <Plus /> New document
                            </Link>
                        </Button>
                    </div>

                    {owned.length > 0 ? (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {owned.map((document) => (
                                <Card
                                    key={document.id}
                                    data-test="owned-document"
                                    className="gap-3 py-4"
                                >
                                    <CardContent className="flex items-center justify-between gap-3">
                                        <div className="flex min-w-0 items-center gap-3">
                                            <Link
                                                href={show(document.id)}
                                                className="truncate font-medium hover:underline"
                                            >
                                                {document.title}
                                            </Link>
                                            <Badge variant="secondary">
                                                Owner
                                            </Badge>
                                        </div>
                                        {document.can.delete ? (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Delete ${document.title}`}
                                                onClick={() =>
                                                    router.delete(
                                                        destroy.url(
                                                            document.id,
                                                        ),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        ) : null}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="py-2 text-sm text-muted-foreground">
                                You don't own any documents yet.
                            </CardContent>
                        </Card>
                    )}
                </section>

                <section className="space-y-4" aria-labelledby="shared-heading">
                    <div>
                        <h2
                            id="shared-heading"
                            className="text-lg font-semibold"
                        >
                            Shared With Me
                        </h2>
                    </div>

                    {shared.length > 0 ? (
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {shared.map((document) => (
                                <Card
                                    key={document.id}
                                    data-test="shared-document"
                                    className="gap-3 py-4"
                                >
                                    <CardContent className="flex items-center justify-between gap-3">
                                        <div className="min-w-0">
                                            <Link
                                                href={show(document.id)}
                                                className="block truncate font-medium hover:underline"
                                            >
                                                {document.title}
                                            </Link>
                                            <p className="truncate text-sm text-muted-foreground">
                                                Shared by {document.ownerName}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {document.permission === 'edit'
                                                ? 'Can edit'
                                                : 'View only'}
                                        </Badge>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="py-2 text-sm text-muted-foreground">
                                No documents have been shared with you.
                            </CardContent>
                        </Card>
                    )}
                </section>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
