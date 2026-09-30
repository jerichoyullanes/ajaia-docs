import { Head } from '@inertiajs/react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
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
                    <div>
                        <h2
                            id="owned-heading"
                            className="text-lg font-semibold"
                        >
                            My Documents
                        </h2>
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
                                        <p className="truncate font-medium">
                                            {document.title}
                                        </p>
                                        <Badge variant="secondary">Owner</Badge>
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
                                            <p className="truncate font-medium">
                                                {document.title}
                                            </p>
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
