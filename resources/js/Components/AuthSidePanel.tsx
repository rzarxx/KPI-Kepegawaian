import { BarChart3, ShieldCheck } from 'lucide-react';
import { ReactNode } from 'react';

export default function AuthSidePanel({ title, description, detail }: { title: string; description: string; detail: ReactNode }) {
    return (
        <section className="hidden flex-col justify-between bg-brand p-10 text-white lg:flex">
            <div>
                <div className="flex size-11 items-center justify-center rounded-xl bg-white/15"><BarChart3 aria-hidden="true" size={24} /></div>
                <p className="mt-8 text-sm font-semibold tracking-wide text-green-100">KPI KEPEGAWAIAN</p>
                <h1 className="mt-3 max-w-sm text-3xl font-bold leading-tight">{title}</h1>
                <p className="mt-4 max-w-sm text-sm leading-6 text-green-50">{description}</p>
            </div>
            <div className="flex items-start gap-3 text-sm leading-6 text-green-50"><ShieldCheck aria-hidden="true" className="mt-0.5 shrink-0" size={18} />{detail}</div>
        </section>
    );
}
