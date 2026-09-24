import { PropsWithChildren } from 'react';

export default function Guest({
    children,
    className = 'max-w-md',
}: PropsWithChildren<{ className?: string }>) {
    return (
        <main className="flex min-h-screen items-center justify-center bg-slate-50 p-4 sm:p-6">
            <div className={`w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ${className}`}>
                {children}
            </div>
        </main>
    );
}
