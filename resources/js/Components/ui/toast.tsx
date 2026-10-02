import { CircleAlert, CircleCheck, Info, X } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

type ToastType = 'success' | 'error' | 'info';
type Toast = { id: number; type: ToastType; message: string };

let toastId = 0;
let addToast: ((type: ToastType, message: string) => void) | null = null;

export function toast(type: ToastType, message: string) {
    addToast?.(type, message);
}

export function Toaster() {
    const [toasts, setToasts] = useState<Toast[]>([]);

    const add = useCallback((type: ToastType, message: string) => {
        const id = ++toastId;
        setToasts((prev) => [...prev, { id, type, message }]);
        setTimeout(() => setToasts((prev) => prev.filter((t) => t.id !== id)), 4500);
    }, []);

    const remove = useCallback((id: number) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    }, []);

    useEffect(() => {
        addToast = add;
        return () => { addToast = null; };
    }, [add]);

    if (toasts.length === 0) return null;

    return (
        <div className="pointer-events-none fixed bottom-4 right-4 z-[100] flex flex-col items-end gap-2">
            {toasts.map((t) => (
                <ToastItem key={t.id} toast={t} onDismiss={remove} />
            ))}
        </div>
    );
}

const icons: Record<ToastType, React.ReactNode> = {
    success: <CircleCheck className="mt-0.5 shrink-0 text-brand" size={18} />,
    error: <CircleAlert className="mt-0.5 shrink-0 text-red-600" size={18} />,
    info: <Info className="mt-0.5 shrink-0 text-blue-600" size={18} />,
};

const styles: Record<ToastType, string> = {
    success: 'border-brand-soft bg-brand-subtle text-green-900',
    error: 'border-red-200 bg-red-50 text-red-900',
    info: 'border-blue-200 bg-blue-50 text-blue-900',
};

function ToastItem({ toast: t, onDismiss }: { toast: Toast; onDismiss: (id: number) => void }) {
    const [entering, setEntering] = useState(true);

    useEffect(() => {
        const frame = requestAnimationFrame(() => setEntering(false));
        return () => cancelAnimationFrame(frame);
    }, []);

    return (
        <div
            className={`pointer-events-auto flex max-w-sm items-start gap-3 rounded-xl border p-4 text-sm shadow-lg transition-all duration-300 ${styles[t.type]} ${entering ? 'translate-x-4 opacity-0' : 'translate-x-0 opacity-100'}`}
            role={t.type === 'error' ? 'alert' : 'status'}
        >
            {icons[t.type]}
            <p className="flex-1 leading-snug">{t.message}</p>
            <button
                type="button"
                className="shrink-0 rounded p-0.5 opacity-50 transition hover:opacity-100"
                onClick={() => onDismiss(t.id)}
                aria-label="Tutup"
            >
                <X size={14} />
            </button>
        </div>
    );
}