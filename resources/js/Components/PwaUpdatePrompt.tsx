import { useEffect, useState } from 'react';

export default function PwaUpdatePrompt() {
    const [waitingWorker, setWaitingWorker] = useState<ServiceWorker | null>(null);
    const [dirtyForm, setDirtyForm] = useState(false);

    useEffect(() => {
        const onControllerChange = () => window.location.reload();
        navigator.serviceWorker?.addEventListener('controllerchange', onControllerChange);
        navigator.serviceWorker?.getRegistration().then((registration) => {
            if (!registration) return;
            if (registration.waiting) setWaitingWorker(registration.waiting);
            registration.addEventListener('updatefound', () => {
                const installing = registration.installing;
                if (!installing) return;
                installing.addEventListener('statechange', () => {
                    if (installing.state === 'installed' && navigator.serviceWorker.controller) setWaitingWorker(registration.waiting);
                });
            });
        });
        const markDirty = (event: Event) => {
            if ((event.target as HTMLElement | null)?.closest('form')) setDirtyForm(true);
        };
        const markClean = () => setDirtyForm(false);
        document.addEventListener('input', markDirty, true);
        document.addEventListener('change', markDirty, true);
        document.addEventListener('submit', markClean, true);
        document.addEventListener('inertia:finish', markClean);
        return () => {
            navigator.serviceWorker?.removeEventListener('controllerchange', onControllerChange);
            document.removeEventListener('input', markDirty, true);
            document.removeEventListener('change', markDirty, true);
            document.removeEventListener('submit', markClean, true);
            document.removeEventListener('inertia:finish', markClean);
        };
    }, []);

    if (!waitingWorker) return null;
    const update = () => {
        if (dirtyForm && !window.confirm('Ada perubahan formulir yang belum disimpan. Muat ulang dan abaikan perubahan tersebut?')) return;
        waitingWorker.postMessage({ type: 'SKIP_WAITING' });
    };
    return <div className="fixed bottom-4 left-4 right-4 z-50 mx-auto flex max-w-lg items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-lg"><span>{dirtyForm ? 'Pembaruan tersedia. Simpan formulir sebelum memuat ulang.' : 'Pembaruan aplikasi tersedia.'}</span><button type="button" className="min-h-10 rounded-lg bg-green-600 px-3 font-semibold text-white hover:bg-green-700" onClick={update}>Muat ulang</button></div>;
}
