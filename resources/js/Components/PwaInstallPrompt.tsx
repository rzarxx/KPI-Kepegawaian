import { useEffect, useState } from 'react';

type DeferredPrompt = Event & { prompt: () => Promise<void>; userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }> };

export default function PwaInstallPrompt() {
    const [deferredPrompt, setDeferredPrompt] = useState<DeferredPrompt | null>(null);
    useEffect(() => {
        const handler = (event: Event) => {
            event.preventDefault();
            const dismissedAt = Number(localStorage.getItem('pwa-install-dismissed-at') ?? 0);
            if (Date.now() - dismissedAt < 7 * 24 * 60 * 60 * 1000) return;
            setDeferredPrompt(event as DeferredPrompt);
        };
        window.addEventListener('beforeinstallprompt', handler);
        return () => window.removeEventListener('beforeinstallprompt', handler);
    }, []);
    if (!deferredPrompt) return null;
    return <div className="fixed bottom-4 right-4 z-50 flex max-w-sm items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-lg"><span>Instal aplikasi untuk akses lebih cepat.</span><button className="min-h-10 rounded-lg bg-green-600 px-3 font-semibold text-white" type="button" onClick={async () => { await deferredPrompt.prompt(); const choice = await deferredPrompt.userChoice; if (choice.outcome === 'dismissed') localStorage.setItem('pwa-install-dismissed-at', String(Date.now())); setDeferredPrompt(null); }}>Instal</button><button className="min-h-10 px-2 text-slate-600" type="button" onClick={() => { localStorage.setItem('pwa-install-dismissed-at', String(Date.now())); setDeferredPrompt(null); }}>Nanti</button></div>;
}
