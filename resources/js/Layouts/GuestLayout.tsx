import { PropsWithChildren } from 'react';

import { usePage } from '@inertiajs/react';
export default function Guest({
    children,
    className = 'max-w-md',
}: PropsWithChildren<{ className?: string }>) {
    const { branding } = usePage().props;
    const brand = branding as { app_name: string; app_logo: string | null; primary_color: string; footer_text: string };
    const accentStyle = brand?.primary_color ? { '--brand-color': brand.primary_color } as React.CSSProperties : undefined;
    return (
        <div style={accentStyle} className="flex min-h-screen flex-col bg-slate-50">
            <main className="flex flex-1 items-center justify-center p-4 sm:p-6">
            <div className={`w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ${className}`}>
                {children}
            </div>
        </main>
            {brand.footer_text && (
                <footer className="border-t border-slate-200 bg-white py-4 text-center text-sm text-slate-500">
                    {brand.footer_text}
                </footer>
            )}
        </div>
    );
}
