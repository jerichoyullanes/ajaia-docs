type SaveStatusValue = 'Saved' | 'Unsaved changes' | 'Saving…' | 'Error';

type Props = {
    status: SaveStatusValue;
};

export default function SaveStatus({ status }: Props) {
    return (
        <p
            className="text-sm text-muted-foreground"
            role="status"
            aria-live="polite"
        >
            {status}
        </p>
    );
}

export type { SaveStatusValue };
