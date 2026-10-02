import { BarChart3, ShieldCheck } from 'lucide-react';
import { ReactNode } from 'react';

import { usePage } from '@inertiajs/react';
export default function AuthSidePanel({ title, description, detail }: { title: string; description: string; detail: ReactNode }) {
    const { branding } = usePage().props;
    const brand = branding as { app_name: string; app_logo: string | null; primary_color: string; footer_text: string };
    return (
        <section className="hidden flex-col justify-between bg-brand p-10 text-white lg:flex">
            <div>
                {brand.app_logo ? <img src={`/storage/${brand.app_logo}`} alt={brand.app_name} className="size-11 object-contain rounded-xl bg-white/10 p-1" /> : <div className="flex size-11 items-center justify-center rounded-xl bg-white/15"><BarChart3 aria-hidden="true" size={24} /></div>}
                <p className="mt-8 text-sm font-semibold tracking-wide text-white/80 uppercase">{brand.app_name}</p>
                <h1 className="mt-3 max-w-sm text-3xl font-bold leading-tight">{title}</h1>
                <p className="mt-4 max-w-sm text-sm leading-6 text-white/60">{description}</p>
            </div>
            <div className="flex items-start gap-3 text-sm leading-6 text-white/60"><ShieldCheck aria-hidden="true" className="mt-0.5 shrink-0" size={18} />{detail}</div>
        </section>
    );
}
