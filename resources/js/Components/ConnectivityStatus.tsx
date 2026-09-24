import { WifiOff } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function ConnectivityStatus() {
    const [online, setOnline] = useState(() => navigator.onLine);

    useEffect(() => {
        const connected = () => setOnline(true);
        const disconnected = () => setOnline(false);
        window.addEventListener('online', connected);
        window.addEventListener('offline', disconnected);
        return () => {
            window.removeEventListener('online', connected);
            window.removeEventListener('offline', disconnected);
        };
    }, []);

    if (online) return null;

    return <div className="fixed left-1/2 top-3 z-[60] flex -translate-x-1/2 items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-lg" role="status"><WifiOff size={16} />Anda sedang offline. Data pribadi tidak disimpan di cache.</div>;
}
