export type ConfirmationOptions = {
    title?: string;
    message: string;
    confirmLabel?: string;
    variant?: 'primary' | 'danger';
};

export type PendingConfirmation = ConfirmationOptions & { resolve: (confirmed: boolean) => void };

export const confirmationEventName = 'kpi:confirm-action';

export function confirmAction(options: ConfirmationOptions): Promise<boolean> {
    return new Promise((resolve) => window.dispatchEvent(new CustomEvent<PendingConfirmation>(confirmationEventName, {
        detail: { ...options, resolve },
    })));
}
