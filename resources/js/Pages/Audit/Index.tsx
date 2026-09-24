import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Search, ShieldCheck } from 'lucide-react';
import { FormEvent, useState } from 'react';

type AuditRow = {
    id: number;
    actor: string;
    action: string;
    subject?: string | null;
    ipAddress?: string | null;
    createdAt?: string | null;
};

type PageLink = { url: string | null; label: string; active: boolean };

const pageLabel = (label: string) => label.includes('Previous') ? 'Sebelumnya' : label.includes('Next') ? 'Berikutnya' : label;

export default function Index({ logs, filters }: { logs: { data: AuditRow[]; links: PageLink[] }; filters: { action?: string } }) {
    const [action, setAction] = useState(filters.action ?? '');

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('audit.index'), action ? { action } : {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Keamanan</p><h1 className="text-[26px] font-bold text-slate-900">Audit Aktivitas</h1></div>}>
            <Head title="Audit Aktivitas" />
            <div className="mx-auto max-w-[1400px] space-y-5 p-4 sm:p-6 lg:p-8">
                <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                    <p className="flex items-center gap-2 font-semibold"><ShieldCheck size={18} />Catatan hanya-baca</p>
                    <p className="mt-1 text-blue-800">Halaman ini menampilkan jejak tindakan tingkat organisasi tanpa membuka isi perubahan yang dapat memuat data sensitif.</p>
                </div>
                <form className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row" onSubmit={submit}>
                    <label className="flex-1 text-sm font-medium text-slate-700">
                        Cari jenis tindakan
                        <input className="mt-1 h-10 w-full rounded-lg border-slate-300 text-sm focus:border-green-600 focus:ring-green-600" onChange={(event) => setAction(event.target.value)} placeholder="Contoh: auth.login" value={action} />
                    </label>
                    <button className="flex min-h-10 items-center justify-center gap-2 self-end rounded-lg bg-green-600 px-4 text-sm font-semibold text-white hover:bg-green-700" type="submit"><Search size={16} />Cari</button>
                </form>
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                            <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Waktu</th><th className="px-4 py-3">Pelaku</th><th className="px-4 py-3">Tindakan</th><th className="px-4 py-3">Objek</th><th className="px-4 py-3">Alamat IP</th></tr></thead>
                            <tbody className="divide-y divide-slate-100">
                                {logs.data.map((log) => <tr key={log.id}><td className="whitespace-nowrap px-4 py-3 text-slate-600">{log.createdAt ? format(new Date(log.createdAt), 'dd MMM yyyy, HH:mm', { locale: id }) : '-'}</td><td className="px-4 py-3 font-medium text-slate-800">{log.actor}</td><td className="px-4 py-3"><code className="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{log.action}</code></td><td className="px-4 py-3 text-slate-600">{log.subject ?? '-'}</td><td className="px-4 py-3 text-slate-600">{log.ipAddress ?? '-'}</td></tr>)}
                            </tbody>
                        </table>
                    </div>
                    {!logs.data.length && <p className="p-8 text-center text-sm text-slate-500">Belum ada aktivitas yang sesuai dengan pencarian.</p>}
                    {logs.links.length > 3 && <nav className="flex flex-wrap gap-2 border-t border-slate-100 p-4">{logs.links.map((link, index) => link.url ? <Link className={`rounded-lg px-3 py-2 text-sm ${link.active ? 'bg-green-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'}`} href={link.url} key={index}>{pageLabel(link.label)}</Link> : <span className="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" key={index}>{pageLabel(link.label)}</span>)}</nav>}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
