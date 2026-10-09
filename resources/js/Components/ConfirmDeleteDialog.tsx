import { Button } from '@/Components/ui/button';
import { AlertTriangle } from 'lucide-react';
import { useEffect, useRef } from 'react';

interface ConfirmDeleteDialogProps {
    open: boolean;
    title?: string;
    message: string;
    confirmLabel?: string;
    processing?: boolean;
    processingLabel?: string;
    variant?: "primary" | "danger";
    onConfirm: () => void;
    onCancel: () => void;
}

export default function ConfirmDeleteDialog({
    open,
    title = 'Konfirmasi Hapus',
    message,
    confirmLabel = 'Ya, Hapus',
    processing = false,
    processingLabel = 'Menghapus...',
    variant = 'danger',
    onConfirm,
    onCancel,
}: ConfirmDeleteDialogProps) {
    const cancelRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (open) {
            cancelRef.current?.focus();
        }
    }, [open]);

    useEffect(() => {
        if (!open) return;
        const handler = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && !processing) onCancel();
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [open, onCancel, processing]);

    if (!open) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            onClick={(event) => {
                if (event.target === event.currentTarget && !processing) onCancel();
            }}
        >
            <div
                className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-xl"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="confirm-delete-title"
                aria-describedby="confirm-delete-desc"
            >
                <div className="flex items-start gap-3">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                        <AlertTriangle size={18} />
                    </span>
                    <div>
                        <h2
                            id="confirm-delete-title"
                            className="font-semibold text-slate-900"
                        >
                            {title}
                        </h2>
                        <p
                            id="confirm-delete-desc"
                            className="mt-1 text-sm text-slate-600"
                        >
                            {message}
                        </p>
                    </div>
                </div>
                <div className="mt-5 flex justify-end gap-3">
                    <Button
                        ref={cancelRef}
                        type="button"
                        variant="secondary"
                        onClick={onCancel}
                        disabled={processing}
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        variant={variant}
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {processing ? processingLabel : confirmLabel}
                    </Button>
                </div>
            </div>
        </div>
    );
}
