import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, ClipboardList, Clock3, FilePenLine, Settings2, UsersRound } from 'lucide-react';
import { useMemo, useState } from 'react';

type Period = { id: number; name: string; start_date: string; end_date: string };
type TaskStatus = 'NOT_STARTED' | 'DRAFT' | 'COMPLETED';
type Task = {
    employee: { id: number; employee_number: string; full_name: string };
    organization: { branch: string | null; division: string | null; sub_division: string | null };
    evaluation_id: number | null;
    status: TaskStatus;
    score: string | null;
    evaluator: string | null;
    can_evaluate: boolean;
    can_reopen: boolean;
};

export default function Index({
    periods,
    activePeriod,
    tasks,
    summary,
    needsPlacement,
    canEvaluate,
    canConfigure,
}: {
    periods: Period[];
    activePeriod: Period | null;
    tasks: Task[];
    summary: { total: number; not_started: number; draft: number; completed: number; completion_percentage: number };
    needsPlacement: number;
    canEvaluate: boolean;
    canConfigure: boolean;
}) {
    const [status, setStatus] = useState<'ALL' | TaskStatus>('ALL');
    const visibleTasks = useMemo(() => status === 'ALL' ? tasks : tasks.filter((task) => task.status === status), [status, tasks]);

    return (
        <AuthenticatedLayout
            header={(
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm text-slate-500">Penilaian Karyawan</p>
                        <h1 className="text-[26px] font-bold text-slate-900">{canEvaluate ? 'Daftar Tugas Kasubdiv' : 'Monitoring Penilaian'}</h1>
                    </div>
                    {canConfigure && <Button asChild variant="secondary"><Link href={route('evaluations.configuration')}><Settings2 size={16} /> Konfigurasi</Link></Button>}
                </div>
            )}
        >
            <Head title="Penilaian" />
            <div className="mx-auto max-w-[1240px] space-y-6 p-4 sm:p-6 lg:p-8">
                <section className="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Periode aktif</p>
                        <p className="mt-1 font-semibold text-slate-900">{activePeriod?.name ?? 'Belum ada periode aktif'}</p>
                        {activePeriod && <p className="mt-1 text-sm text-slate-500">{activePeriod.start_date} sampai {activePeriod.end_date}</p>}
                    </div>
                    {periods.length > 1 && (
                        <select
                            aria-label="Pilih periode"
                            className="min-h-10 rounded-lg border-slate-300 text-sm focus:border-brand focus:ring-brand"
                            onChange={(event) => router.get(route('evaluations.index'), { period_id: event.target.value }, { preserveState: true })}
                            value={activePeriod?.id ?? ''}
                        >
                            {periods.map((period) => <option key={period.id} value={period.id}>{period.name}</option>)}
                        </select>
                    )}
                </section>

                {needsPlacement > 0 && (
                    <div className="flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <AlertTriangle className="mt-0.5 shrink-0" size={18} />
                        <div><strong>{needsPlacement} Karyawan perlu penempatan.</strong><p className="mt-1 text-amber-800">Tetapkan Sub Divisi aktif terlebih dahulu agar tanggung jawab penilai jelas.</p></div>
                    </div>
                )}

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Metric icon={<UsersRound size={18} />} label="Total Karyawan" value={summary.total} tone="slate" />
                    <Metric icon={<ClipboardList size={18} />} label="Belum Dinilai" value={summary.not_started} tone="slate" />
                    <Metric icon={<Clock3 size={18} />} label="Masih Draf" value={summary.draft} tone="amber" />
                    <Metric icon={<CheckCircle2 size={18} />} label="Selesai" value={`${summary.completed} (${summary.completion_percentage}%)`} tone="green" />
                </section>

                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col justify-between gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center">
                        <div><h2 className="font-semibold text-slate-900">Daftar Penilaian</h2><p className="mt-1 text-sm text-slate-500">Satu Karyawan hanya memiliki satu status pada periode aktif.</p></div>
                        <div className="flex flex-wrap gap-2">
                            {([['ALL', 'Semua'], ['NOT_STARTED', 'Belum Dinilai'], ['DRAFT', 'Draf'], ['COMPLETED', 'Selesai']] as const).map(([value, label]) => (
                                <button className={`min-h-9 rounded-lg px-3 text-xs font-semibold ${status === value ? 'bg-brand text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}`} key={value} onClick={() => setStatus(value)} type="button">{label}</button>
                            ))}
                        </div>
                    </div>

                    {!activePeriod ? (
                        <Empty title="Belum ada periode aktif" text="HR perlu mengaktifkan periode sebelum Kasubdiv dapat melakukan penilaian." />
                    ) : visibleTasks.length === 0 ? (
                        <Empty title="Tidak ada tugas pada filter ini" text="Pilih status lain atau periksa penempatan Karyawan." />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[820px] text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Karyawan</th><th className="px-4 py-3">Sub Divisi</th><th className="px-4 py-3">Penilai</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Nilai</th><th className="px-4 py-3 text-right">Tindakan</th></tr></thead>
                                <tbody className="divide-y divide-slate-100">
                                    {visibleTasks.map((task) => <TaskRow activePeriod={activePeriod} key={task.employee.id} task={task} />)}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function TaskRow({ task, activePeriod }: { task: Task; activePeriod: Period }) {
    const action = task.status === 'NOT_STARTED' ? 'Mulai Penilaian' : task.status === 'DRAFT' ? 'Lanjutkan Draf' : 'Lihat Hasil';
    const href = task.evaluation_id
        ? route('evaluations.show', task.evaluation_id)
        : route('evaluations.create', { employee: task.employee.id, period_id: activePeriod.id });

    return (
        <tr className="hover:bg-slate-50/70">
            <td className="px-4 py-4"><p className="font-semibold text-slate-900">{task.employee.full_name}</p><p className="mt-0.5 text-xs text-slate-500">{task.employee.employee_number}</p></td>
            <td className="px-4 py-4"><p className="text-slate-700">{task.organization.sub_division}</p><p className="mt-0.5 text-xs text-slate-500">{task.organization.division}</p></td>
            <td className="px-4 py-4 text-slate-600">{task.evaluator ?? 'Belum ditentukan'}</td>
            <td className="px-4 py-4"><StatusBadge status={task.status} /></td>
            <td className="px-4 py-4 font-semibold text-slate-800">{task.score ?? '-'}</td>
            <td className="px-4 py-4 text-right">
                {(task.can_evaluate || task.evaluation_id) && <Link className="inline-flex min-h-9 items-center gap-2 rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50" href={href}><FilePenLine size={14} /> {action}</Link>}
            </td>
        </tr>
    );
}

function StatusBadge({ status }: { status: TaskStatus }) {
    const labels = { NOT_STARTED: 'Belum Dinilai', DRAFT: 'Draf', COMPLETED: 'Selesai' };
    const styles = { NOT_STARTED: 'bg-slate-100 text-slate-700', DRAFT: 'bg-amber-100 text-amber-800', COMPLETED: 'bg-emerald-100 text-emerald-800' };
    return <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${styles[status]}`}>{labels[status]}</span>;
}

function Metric({ icon, label, value, tone }: { icon: React.ReactNode; label: string; value: string | number; tone: 'green' | 'amber' | 'slate' }) {
    const styles = { green: 'bg-emerald-50 text-emerald-700', amber: 'bg-amber-50 text-amber-700', slate: 'bg-slate-100 text-slate-700' };
    return <article className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><span className={`flex size-10 items-center justify-center rounded-lg ${styles[tone]}`}>{icon}</span><p className="mt-4 text-sm text-slate-500">{label}</p><p className="mt-1 text-2xl font-bold text-slate-900">{value}</p></article>;
}

function Empty({ title, text }: { title: string; text: string }) {
    return <div className="p-10 text-center"><ClipboardList className="mx-auto text-slate-300" size={34} /><p className="mt-3 font-semibold text-slate-800">{title}</p><p className="mt-1 text-sm text-slate-500">{text}</p></div>;
}
