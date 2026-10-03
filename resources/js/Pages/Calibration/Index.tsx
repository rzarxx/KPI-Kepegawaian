import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Scale, X } from 'lucide-react';
import { useState } from 'react';

type Session = {
    id: number;
    name: string;
    period_name: string;
    status: string;
    scope_type: string | null;
    adjustments_count: number;
    creator_name: string;
    calibrated_at: string | null;
    created_at: string;
};

type Period = { id: number; name: string };

const statusLabels: Record<string, string> = {
    DRAFT: 'Draf',
    IN_REVIEW: 'Ditinjau',
    FINALIZED: 'Final',
    APPLIED: 'Diterapkan',
};

const statusStyles: Record<string, string> = {
    DRAFT: 'bg-slate-100 text-slate-700',
    IN_REVIEW: 'bg-amber-50 text-amber-800',
    FINALIZED: 'bg-blue-50 text-blue-700',
    APPLIED: 'bg-brand-subtle text-brand-dark',
};

export default function Index({
    sessions,
    periods,
    canManage,
}: {
    sessions: { data: Session[]; links: any[] };
    periods: Period[];
    canManage: boolean;
}) {
    const [showForm, setShowForm] = useState(false);
    const form = useForm({
        period_id: '',
        name: '',
        description: '',
        scope_type: 'ALL',
    });

    const submit = () =>
        form.post(route('calibration.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setShowForm(false);
            },
        });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm text-slate-500">Penilaian</p>
                        <h1 className="text-[26px] font-bold text-slate-900">Kalibrasi Nilai</h1>
                    </div>
                    {canManage && (
                        <Button onClick={() => setShowForm(!showForm)} type="button">
                            {showForm ? <X size={16} /> : <Plus size={16} />}
                            {showForm ? 'Batal' : 'Buat Sesi Kalibrasi'}
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Kalibrasi Nilai" />
            <div className="mx-auto max-w-[1200px] space-y-6 p-4 sm:p-6 lg:p-8">
                {showForm && (
                    <section className="rounded-xl border border-brand/30 bg-white p-5 shadow-sm">
                        <h2 className="text-sm font-semibold text-slate-800">Buat Sesi Kalibrasi Baru</h2>
                        <form
                            className="mt-4 grid gap-3 sm:grid-cols-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                submit();
                            }}
                        >
                            <label className="text-sm font-medium text-slate-700">
                                Nama sesi
                                <input
                                    className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    required
                                    value={form.data.name}
                                />
                                {form.errors.name && <span className="mt-1 block text-xs text-red-600">{form.errors.name}</span>}
                            </label>
                            <label className="text-sm font-medium text-slate-700">
                                Periode
                                <select
                                    className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                                    onChange={(e) => form.setData('period_id', e.target.value)}
                                    required
                                    value={form.data.period_id}
                                >
                                    <option value="">Pilih periode...</option>
                                    {periods.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.period_id && (
                                    <span className="mt-1 block text-xs text-red-600">{form.errors.period_id}</span>
                                )}
                            </label>
                            <label className="text-sm font-medium text-slate-700">
                                Lingkup
                                <select
                                    className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                                    onChange={(e) => form.setData('scope_type', e.target.value)}
                                    value={form.data.scope_type}
                                >
                                    <option value="ALL">Semua</option>
                                    <option value="BRANCH">Per cabang</option>
                                    <option value="DIVISION">Per divisi</option>
                                </select>
                            </label>
                            <label className="sm:col-span-2 text-sm font-medium text-slate-700">
                                Deskripsi (opsional)
                                <textarea
                                    className="mt-1 min-h-16 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                                    onChange={(e) => form.setData('description', e.target.value)}
                                    value={form.data.description}
                                />
                            </label>
                            <div className="sm:col-span-2">
                                <Button disabled={form.processing} type="submit">
                                    <Plus size={16} />
                                    Buat Sesi
                                </Button>
                            </div>
                        </form>
                    </section>
                )}

                <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-2 border-b border-slate-100 p-5">
                        <Scale size={18} className="text-brand" />
                        <h2 className="font-semibold text-slate-900">Sesi Kalibrasi</h2>
                    </div>

                    {sessions.data.length > 0 ? (
                        <div className="divide-y divide-slate-100">
                            {sessions.data.map((session) => (
                                <div
                                    className="flex flex-col justify-between gap-3 p-5 sm:flex-row sm:items-center"
                                    key={session.id}
                                >
                                    <div>
                                        <p className="font-semibold text-slate-900">{session.name}</p>
                                        <p className="mt-1 text-sm text-slate-500">
                                            {session.period_name} · {session.adjustments_count} penyesuaian ·{' '}
                                            {session.creator_name}
                                        </p>
                                        <p className="text-xs text-slate-400">Dibuat {session.created_at}</p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <span
                                            className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyles[session.status] ?? 'bg-slate-100 text-slate-600'}`}
                                        >
                                            {statusLabels[session.status] ?? session.status}
                                        </span>
                                        <Button asChild variant="secondary">
                                            <Link href={route('calibration.show', session.id)}>Buka</Link>
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="px-4 py-8 text-center">
                            <Scale className="mx-auto text-slate-300" size={28} />
                            <p className="mt-3 text-sm font-semibold text-slate-700">Belum ada sesi kalibrasi</p>
                            <p className="mt-1 text-xs text-slate-500">
                                Buat sesi kalibrasi untuk menyesuaikan nilai penilaian secara kolektif.
                            </p>
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
