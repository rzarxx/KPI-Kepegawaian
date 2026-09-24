import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, ShieldCheck } from 'lucide-react';

type Incident = {
    id: number;
    employee_id: number;
    employee: string;
    division?: string;
    title: string;
    category: string;
    severity: 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';
    status: 'OPEN' | 'UNDER_REVIEW' | 'RESOLVED' | 'CLOSED';
    occurred_at: string;
    reporter?: string;
    resolution?: string;
};
type Paginated<T> = { data: T[]; links: { url?: string; label: string; active: boolean }[]; total: number };

export default function ProblemIndex({ incidents }: { incidents: Paginated<Incident> }) {
    return (
        <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Karyawan</p><h1 className="text-[26px] font-bold text-slate-900">Karyawan Bermasalah</h1></div>}>
            <Head title="Karyawan Bermasalah" />
            <div className="mx-auto max-w-6xl space-y-5 p-4 sm:p-6 lg:p-8">
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <p className="flex items-center gap-2 font-semibold"><AlertTriangle size={18} />Catatan privat dan terbatas sesuai cakupan organisasi</p>
                    <p className="mt-1 text-amber-800">Gunakan data ini untuk tindak lanjut yang adil, terukur, dan terdokumentasi.</p>
                </div>
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    {incidents.data.length ? <div className="divide-y divide-slate-100">{incidents.data.map((incident) => <article className="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center" key={incident.id}><div><div className="flex flex-wrap items-center gap-2"><p className="font-semibold text-slate-900">{incident.employee}</p><span className={'rounded-full px-2 py-1 text-xs font-semibold ' + severityClasses[incident.severity]}>{severityLabels[incident.severity]}</span><span className={'rounded-full px-2 py-1 text-xs font-semibold ' + statusClasses[incident.status]}>{statusLabels[incident.status]}</span></div><p className="mt-1 text-sm font-medium text-slate-700">{incident.title}</p><p className="mt-1 text-xs text-slate-500">{incident.division || 'Divisi belum ditentukan'} · {incident.occurred_at}{incident.reporter ? ` · Dicatat oleh ${incident.reporter}` : ''}</p>{incident.resolution && <p className="mt-2 text-sm text-slate-600"><span className="font-medium text-slate-700">Penyelesaian:</span> {incident.resolution}</p>}</div><Link className="inline-flex min-h-10 items-center gap-2 self-start rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" href={route('employees.incidents.index', incident.employee_id)}>Lihat riwayat<ArrowRight size={16} /></Link></article>)}</div> : <div className="py-16 text-center"><ShieldCheck className="mx-auto text-green-600" size={32} /><p className="mt-3 font-semibold text-slate-800">Tidak ada catatan masalah dalam cakupan Anda</p><p className="mt-1 text-sm text-slate-500">Catatan baru akan tampil otomatis di halaman ini.</p></div>}
                </section>
                {incidents.links.length > 3 && <nav className="flex flex-wrap justify-center gap-1" aria-label="Navigasi halaman">{incidents.links.map((link, index) => link.url ? <Link className={'rounded-lg border px-3 py-2 text-sm ' + (link.active ? 'border-green-600 bg-green-50 text-green-800' : 'border-slate-200 bg-white text-slate-600')} href={link.url} key={index}>{paginationLabel(link.label)}</Link> : <span className="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" key={index}>{paginationLabel(link.label)}</span>)}</nav>}
            </div>
        </AuthenticatedLayout>
    );
}

const severityLabels = { LOW: 'Rendah', MEDIUM: 'Sedang', HIGH: 'Tinggi', CRITICAL: 'Kritis' };
const severityClasses = { LOW: 'bg-slate-100 text-slate-700', MEDIUM: 'bg-amber-50 text-amber-800', HIGH: 'bg-orange-50 text-orange-800', CRITICAL: 'bg-red-50 text-red-700' };
const statusLabels = { OPEN: 'Terbuka', UNDER_REVIEW: 'Ditinjau', RESOLVED: 'Selesai', CLOSED: 'Ditutup' };
const statusClasses = { OPEN: 'bg-red-50 text-red-700', UNDER_REVIEW: 'bg-amber-50 text-amber-800', RESOLVED: 'bg-green-50 text-green-800', CLOSED: 'bg-slate-100 text-slate-700' };
function paginationLabel(label: string) { return label.replace('&laquo;', '‹').replace('&raquo;', '›'); }
