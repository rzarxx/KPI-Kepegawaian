import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { id } from 'date-fns/locale';
import { AlertTriangle, ArrowRight, ChartNoAxesCombined, ClipboardCheck, UsersRound } from 'lucide-react';
import { useState } from 'react';
import { CartesianGrid, Cell, Legend, Line, LineChart, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

type Option = { id: number; name: string; branch_id?: number; division_id?: number };
type DistributionItem = { name: string; value: number };
type Props = {
    metrics: { total: number; active: number; attention: number; average: number };
    distributions: { byDivision: DistributionItem[]; byBranch: DistributionItem[]; byStatus: DistributionItem[] };
    trend: { name: string; score: number | string }[];
    priorities: { id: number; name: string; number: string; reasons: { rule: string; value: number; type: string }[] }[];
    activities: { id: number; employee: string; score: number | string; criteria?: string; date: string }[];
    filters: { period_id?: number; branch_id?: number; division_id?: number; sub_division_id?: number };
    filterOptions: { periods: Option[]; branches: Option[]; divisions: Option[]; subDivisions: Option[] };
};

const PIE_COLORS = ['#16A34A', '#2563EB', '#D97706', '#64748B', '#DC2626', '#7C3AED', '#0891B2', '#CA8A04'];

export default function Dashboard({ metrics, distributions, trend, priorities, activities, filters, filterOptions }: Props) {
    const [values, setValues] = useState({
        period_id: String(filters.period_id ?? ''),
        branch_id: String(filters.branch_id ?? ''),
        division_id: String(filters.division_id ?? ''),
        sub_division_id: String(filters.sub_division_id ?? ''),
    });
    const divisions = filterOptions.divisions.filter((option) => !values.branch_id || option.branch_id === Number(values.branch_id));
    const subDivisions = filterOptions.subDivisions.filter((option) => !values.division_id || option.division_id === Number(values.division_id));
    const applyFilters = () => router.get(route('dashboard'), values, { preserveState: true, replace: true });

    return (
        <AuthenticatedLayout header={<div className="flex flex-col justify-between gap-4 xl:flex-row xl:items-end"><div><p className="text-sm text-slate-500">Beranda</p><h1 className="text-[26px] font-bold text-slate-900">Ringkasan</h1></div><div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-5"><FilterSelect ariaLabel="Periode" value={values.period_id} options={filterOptions.periods} placeholder="Semua periode" onChange={(value) => setValues({ ...values, period_id: value })} /><FilterSelect ariaLabel="Cabang" value={values.branch_id} options={filterOptions.branches} placeholder="Semua cabang" onChange={(value) => setValues({ ...values, branch_id: value, division_id: '', sub_division_id: '' })} /><FilterSelect ariaLabel="Divisi" value={values.division_id} options={divisions} placeholder="Semua divisi" onChange={(value) => setValues({ ...values, division_id: value, sub_division_id: '' })} /><FilterSelect ariaLabel="Sub Divisi" value={values.sub_division_id} options={subDivisions} placeholder="Semua sub divisi" onChange={(value) => setValues({ ...values, sub_division_id: value })} /><button className="min-h-10 rounded-lg bg-brand px-4 text-sm font-semibold text-white hover:bg-brand-dark" onClick={applyFilters} type="button">Terapkan</button></div></div>}>
            <Head title="Beranda" />
            <div className="mx-auto max-w-[1600px] space-y-6 p-4 sm:p-6 lg:p-8">
                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Metric icon={<UsersRound size={20} />} label="Total anggota" value={metrics.total} tone="green" />
                    <Metric icon={<ClipboardCheck size={20} />} label="Anggota aktif" value={metrics.active} tone="blue" />
                    <Metric icon={<AlertTriangle size={20} />} label="Perlu perhatian" value={metrics.attention} tone="amber" />
                    <Metric icon={<ChartNoAxesCombined size={20} />} label="Rata-rata penilaian" value={Number(metrics.average).toFixed(2)} tone="slate" />
                </section>
                <section className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    <DistributionChart title="Sebaran per Divisi" data={distributions.byDivision} />
                    <DistributionChart title="Sebaran per Cabang" data={distributions.byBranch} />
                    <DistributionChart title="Sebaran per Status" data={distributions.byStatus} />
                </section>
                <section className="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div><h2 className="font-semibold text-slate-900">Tren Penilaian</h2><p className="mt-1 text-sm text-slate-500">Perkembangan nilai rata-rata dari waktu ke waktu.</p></div>
                        <div className="mt-4 h-64">
                            {trend.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={trend}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#E2E8F0" />
                                        <XAxis dataKey="name" tick={{ fill: '#64748B', fontSize: 12 }} />
                                        <YAxis domain={[0, 100]} tick={{ fill: '#64748B', fontSize: 12 }} />
                                        <Tooltip contentStyle={{ borderColor: '#E2E8F0', borderRadius: 10 }} />
                                        <Line type="monotone" dataKey="score" stroke="#16A34A" strokeWidth={2} dot={{ fill: '#16A34A', r: 4 }} name="Nilai" />
                                    </LineChart>
                                </ResponsiveContainer>
                            ) : <Empty text="Belum ada data penilaian yang dapat ditampilkan." />}
                        </div>
                    </div>
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="font-semibold text-slate-900">Perlu Perhatian</h2>
                        <p className="mt-1 text-sm text-slate-500">Anggota yang memerlukan tindak lanjut berdasarkan aturan penilaian.</p>
                        <div className="mt-4 space-y-3">{priorities.length ? priorities.map((item) => <Link className="block rounded-lg border border-slate-200 p-3 hover:border-amber-300 hover:bg-amber-50" href={route('employees.show', item.id)} key={item.id}><div className="flex items-center justify-between gap-3"><div><p className="text-sm font-semibold text-slate-900">{item.name}</p><p className="text-xs text-slate-500">{item.number}</p></div><ArrowRight className="text-slate-400" size={16} /></div><p className="mt-2 text-xs text-amber-800">{item.reasons.map((reason) => reason.rule).join(' · ')}</p></Link>) : <Empty text="Tidak ada anggota yang memerlukan perhatian saat ini." />}</div>
                    </div>
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="font-semibold text-slate-900">Aktivitas penilaian terbaru</h2>
                    <div className="mt-4 divide-y divide-slate-100">{activities.length ? activities.map((activity) => <div className="flex flex-col justify-between gap-2 py-3 sm:flex-row sm:items-center" key={activity.id}><div><p className="text-sm font-semibold text-slate-800">{activity.employee}</p><p className="text-xs text-slate-500">{activity.criteria || 'Belum memiliki kriteria'} · {formatDistanceToNow(new Date(activity.date), { addSuffix: true, locale: id })}</p></div><span className="w-fit rounded-full bg-brand-subtle px-3 py-1 text-sm font-semibold text-brand-dark">{Number(activity.score).toFixed(2)}</span></div>) : <Empty text="Belum ada aktivitas penilaian." />}</div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function DistributionChart({ title, data }: { title: string; data: DistributionItem[] }) {
    const hasData = data.length > 0 && data.some((item) => item.value > 0);
    const total = data.reduce((sum, item) => sum + item.value, 0);

    const renderLegend = ({ payload }: { payload?: { value: string; color: string }[] }) => (
        <ul className="mt-2 flex flex-col gap-1 overflow-hidden px-1">
            {(payload ?? []).map((entry, index) => {
                const item = data[index];
                const pct = total > 0 ? ((item?.value ?? 0) / total * 100).toFixed(0) : '0';
                return (
                    <li key={`legend-${index}`} className="flex items-center gap-2 min-w-0">
                        <span className="inline-block size-2.5 shrink-0 rounded-full" style={{ backgroundColor: entry.color }} />
                        <span className="truncate text-xs text-slate-600" title={`${entry.value} — ${item?.value ?? 0} orang (${pct}%)`}>
                            {entry.value}
                        </span>
                        <span className="ml-auto shrink-0 text-xs font-semibold text-slate-700">{pct}%</span>
                    </li>
                );
            })}
        </ul>
    );

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="font-semibold text-slate-900">{title}</h2>
            <div className="mt-4 h-72">
                {hasData ? (
                    <ResponsiveContainer width="100%" height="100%">
                        <PieChart>
                            <Pie
                                data={data}
                                dataKey="value"
                                nameKey="name"
                                cx="50%"
                                cy="42%"
                                outerRadius={75}
                                innerRadius={32}
                                label={false}
                                labelLine={false}
                                strokeWidth={1.5}
                                stroke="#fff"
                            >
                                {data.map((_entry, index) => (
                                    <Cell key={`cell-${index}`} fill={PIE_COLORS[index % PIE_COLORS.length]} />
                                ))}
                            </Pie>
                            <Tooltip
                                formatter={(value) => [String(value) + ' orang', 'Jumlah']}
                                contentStyle={{ borderColor: '#E2E8F0', borderRadius: 10, fontSize: 12 }}
                                cursor={{ fill: 'transparent' }}
                            />
                            <Legend
                                content={renderLegend as never}
                                verticalAlign="bottom"
                                height={36}
                            />
                        </PieChart>
                    </ResponsiveContainer>
                ) : (
                    <Empty text="Belum ada data untuk ditampilkan." />
                )}
            </div>
        </div>
    );
}

function Metric({ icon, label, value, tone }: { icon: React.ReactNode; label: string; value: string | number; tone: 'green' | 'blue' | 'amber' | 'slate' }) {
    const colors = { green: 'bg-brand-subtle text-brand', blue: 'bg-blue-50 text-blue-700', amber: 'bg-amber-50 text-amber-700', slate: 'bg-slate-100 text-slate-700' };
    return <article className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><span className={'flex size-10 items-center justify-center rounded-lg ' + colors[tone]}>{icon}</span><p className="mt-4 text-sm text-slate-500">{label}</p><p className="mt-1 text-2xl font-bold text-slate-900">{value}</p></article>;
}
function FilterSelect({ ariaLabel, value, options, placeholder, onChange }: { ariaLabel: string; value: string; options: Option[]; placeholder: string; onChange: (value: string) => void }) { return <select aria-label={ariaLabel} className="min-h-10 rounded-lg border-slate-300 text-sm focus:border-brand focus:ring-brand" value={value} onChange={(event) => onChange(event.target.value)}><option value="">{placeholder}</option>{options.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}</select>; }
function Empty({ text }: { text: string }) { return <div className="flex h-full min-h-28 items-center justify-center rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">{text}</div>; }