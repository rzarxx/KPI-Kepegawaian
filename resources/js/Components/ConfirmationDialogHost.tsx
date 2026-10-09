import ConfirmDeleteDialog from '@/Components/ConfirmDeleteDialog';
import { confirmationEventName, type PendingConfirmation } from '@/Utils/confirmation';
import { useEffect, useState } from 'react';

export default function ConfirmationDialogHost() {
    const [pending, setPending] = useState<PendingConfirmation | null>(null);

    useEffect(() => {
        const handle = (event: Event) => setPending((event as CustomEvent<PendingConfirmation>).detail);
        window.addEventListener(confirmationEventName, handle);

        return () => window.removeEventListener(confirmationEventName, handle);
    }, []);

    const close = (confirmed: boolean) => {
        pending?.resolve(confirmed);
        setPending(null);
    };

    return <ConfirmDeleteDialog
        confirmLabel={pending?.confirmLabel}
        message={pending?.message ?? ''}
        onCancel={() => close(false)}
        onConfirm={() => close(true)}
        open={pending !== null}
        title={pending?.title ?? 'Konfirmasi Tindakan'}
        variant={pending?.variant}
    />;
}
