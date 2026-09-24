import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { zodResolver } from '@hookform/resolvers/zod';
import { Head, Link, router, useForm as useInertiaForm } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { AlertTriangle, ArrowLeft, CheckCircle2, Clock3, Plus, ShieldCheck } from 'lucide-react';
import { z } from 'zod';

type Category = { code: string; name: string };
type Incident = {
    id: number;
    title: string;
    category: string;
    category_name: string;
    severity: 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';
    description: string;
    occurred_at: string;
    status: 'OPEN' | 'UNDER_REVIEW' | 'RESOLVED' | 'CLOSED';
    resolution?: string;
    reporter?: string;
    resolver?: string;
};

const schema = z.object({
    category: z.string().min(1, 'Pilih kategori masalah.'),
    title: z.string().min(3, 'Judul minimal 3 karakter.').max(255),
    severity: z.enum(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
    description: z.string().min(10, 'Uraian minimal 10 karakter.').max(5000),
    occurred_at: z.string().min(1, 'Tanggal kejadian wajib diisi.'),
});
type IncidentValues = z.infer<typeof schema>;

export default function Incidents({
    employee,
    incidents,
    categories,
    canCreate,
    canResolve,
}: {
    employee: { id: number; full_name: string; employee_number: string };
    incidents: Incident[];
    categories: Category[];
    canCreate: boolean;
    canResolve: boolean;
}) {
    return (
        <AuthenticatedLayout header={<div className="flex items-center gap-3"><Button asChild variant="ghost"><Link aria-label="Kembali ke detail karyawan" href={route('employees.show', employee.id)}><ArrowLeft size={18} /></Link></Button><div><p className="text-sm text-slate-500">Karyawan Bermasalah</p><h1 className="text-[26px] font-bold text-slate-900">Catatan Masalah</h1></div></div>}>
            <Head title="Catatan Masalah" />
            <div className="mx-auto max-w-5xl space-y-6 p-4 sm:p-6 lg:p-8">
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p className="font-semibold text-slate-900">{employee.full_name}</p>
                    <p className="text-sm text-slate-500">{employee.employee_number}</p>
                    {canCreate && <IncidentForm employeeId={employee.id} categories={categories} />}
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="flex items-center gap-2 font-semibold text-slate-900"><AlertTriangle size={18} />Riwayat Catatan</h2>
                    <div className="mt-4 space-y-3">
                        {incidents.length ? incidents.map((incident) => <IncidentCard incident={incident} canResolve={canResolve} key={incident.id} />) : <div className="rounded-lg border border-dashed border-slate-200 py-10 text-center"><ShieldCheck className="mx-auto text-green-600" size={28} /><p className="mt-3 text-sm font-medium text-slate-700">Belum ada catatan masalah</p><p className="mt-1 text-sm text-slate-500">Riwayat masalah dan tindak lanjut akan tampil di sini.</p></div>}
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function IncidentForm({ employeeId, categories }: { employeeId: number; categories: Category[] }) {
    const { register, handleSubmit, reset, setError, formState: { errors, isSubmitting } } = useForm<IncidentValues>({
        resolver: zodResolver(schema),
        defaultValues: { category: '', title: '', severity: 'MEDIUM', description: '', occurred_at: '' },
    });

    const submit = (values: IncidentValues) => router.post(route('employees.incidents.store', employeeId), values, {
        preserveScroll: true,
        onSuccess: () => reset(),
        onError: (serverErrors) => Object.entries(serverErrors).forEach(([field, message]) => setError(field as keyof IncidentValues, { message })),
    });

    return (
        <form className="mt-5 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2" onSubmit={handleSubmit(submit)}>
            <Field label="Kategori" error={errors.category?.message}><select className={input} {...register('category')}><option value="">Pilih kategori</option>{categories.map((category) => <option value={category.code} key={category.code}>{category.name}</option>)}</select></Field>
            <Field label="Tingkat keparahan" error={errors.severity?.message}><select className={input} {...register('severity')}><option value="LOW">Rendah</option><option value="MEDIUM">Sedang</option><option value="HIGH">Tinggi</option><option value="CRITICAL">Kritis</option></select></Field>
            <Field label="Judul masalah" error={errors.title?.message}><input className={input} {...register('title')} /></Field>
            <Field label="Tanggal kejadian" error={errors.occurred_at?.message}><input className={input} type="date" {...register('occurred_at')} /></Field>
            <div className="sm:col-span-2"><Field label="Uraian masalah" error={errors.description?.message}><textarea className={input + ' min-h-24 py-2'} {...register('description')} /></Field></div>
            <div className="sm:col-span-2"><Button disabled={isSubmitting || categories.length === 0} type="submit"><Plus size={16} />{isSubmitting ? 'Menyimpan...' : 'Tambah Catatan'}</Button>{categories.length === 0 && <p className="mt-2 text-xs text-red-600">Kategori masalah belum tersedia. Hubungi administrator.</p>}</div>
        </form>
    );
}

function IncidentCard({ incident, canResolve }: { incident: Incident; canResolve: boolean }) {
    const status = statusLabels[incident.status];
    return (
        <article className="rounded-lg border border-slate-200 p-4">
            <div className="flex flex-col justify-between gap-3 sm:flex-row">
                <div>
                    <div className="flex flex-wrap items-center gap-2"><p className="font-semibold text-slate-900">{incident.title}</p><span className={'rounded-full px-2 py-1 text-xs font-semibold ' + severityClasses[incident.severity]}>{severityLabels[incident.severity]}</span></div>
                    <p className="mt-1 text-xs font-medium uppercase tracking-wide text-slate-400">{incident.category_name}</p>
                    <p className="mt-3 text-sm leading-6 text-slate-600">{incident.description}</p>
                </div>
                <span className={'h-fit whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold ' + status.className}>{status.label}</span>
            </div>
            <p className="mt-3 flex items-center gap-1.5 text-xs text-slate-500"><Clock3 size={14} />{incident.occurred_at}{incident.reporter ? ' · Dicatat oleh ' + incident.reporter : ''}</p>
            {incident.resolution && <p className="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-800">Tindak lanjut: {incident.resolution}</p>}
            {canResolve && ['OPEN', 'UNDER_REVIEW'].includes(incident.status) && <ResolveForm incident={incident} />}
        </article>
    );
}

function ResolveForm({ incident }: { incident: Incident }) {
    const form = useInertiaForm({ status: incident.status === 'OPEN' ? 'UNDER_REVIEW' : 'RESOLVED', resolution: '' });
    return (
        <form className="mt-4 grid gap-2 border-t border-slate-100 pt-4 sm:grid-cols-[180px_1fr_auto]" onSubmit={(event) => { event.preventDefault(); form.post(route('employees.incidents.transition', incident.id), { preserveScroll: true }); }}>
            <select className={input} value={form.data.status} onChange={(event) => form.setData('status', event.target.value)}>
                {incident.status === 'OPEN' && <option value="UNDER_REVIEW">Mulai ditinjau</option>}
                <option value="RESOLVED">Selesaikan</option>
            </select>
            <input className={input} placeholder={form.data.status === 'RESOLVED' ? 'Tindak lanjut penyelesaian' : 'Catatan opsional'} value={form.data.resolution} onChange={(event) => form.setData('resolution', event.target.value)} />
            <Button disabled={form.processing} type="submit" variant="secondary"><CheckCircle2 size={16} />Perbarui</Button>
            {form.errors.resolution && <span className="text-xs text-red-600 sm:col-span-3">{form.errors.resolution}</span>}
        </form>
    );
}

const severityLabels = { LOW: 'Rendah', MEDIUM: 'Sedang', HIGH: 'Tinggi', CRITICAL: 'Kritis' };
const severityClasses = { LOW: 'bg-slate-100 text-slate-700', MEDIUM: 'bg-amber-50 text-amber-800', HIGH: 'bg-orange-50 text-orange-800', CRITICAL: 'bg-red-50 text-red-700' };
const statusLabels = {
    OPEN: { label: 'Terbuka', className: 'bg-red-50 text-red-700' },
    UNDER_REVIEW: { label: 'Ditinjau', className: 'bg-amber-50 text-amber-800' },
    RESOLVED: { label: 'Selesai', className: 'bg-green-50 text-green-800' },
    CLOSED: { label: 'Ditutup', className: 'bg-slate-100 text-slate-700' },
};
const input = 'mt-1 min-h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600';
function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) { return <label className="text-sm font-medium text-slate-700">{label}{children}{error && <span className="mt-1 block text-xs text-red-600">{error}</span>}</label>; }
