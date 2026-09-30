import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type Props = {
    currentTeam?: { slug: string } | null;
};

export default function Forbidden({ currentTeam }: Props) {
    return (
        <>
            <Head title="Access denied" />

            <div className="flex flex-1 items-center justify-center p-6">
                <section className="grid max-w-lg gap-4 text-center">
                    <p className="text-sm font-medium text-muted-foreground">
                        Error 403
                    </p>
                    <h1 className="text-2xl font-semibold">Access denied</h1>
                    <p className="text-muted-foreground">
                        You do not have permission to view or change this
                        document.
                    </p>
                    <Button asChild className="justify-self-center">
                        <Link
                            href={
                                currentTeam ? dashboard(currentTeam.slug) : '/'
                            }
                        >
                            <ArrowLeft /> Back to dashboard
                        </Link>
                    </Button>
                </section>
            </div>
        </>
    );
}
