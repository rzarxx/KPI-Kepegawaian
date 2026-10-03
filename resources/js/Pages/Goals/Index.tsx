import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowDown, CheckCircle2, Crosshair, Pencil, Plus, Target, X } from 'lucide-react';
import { useState } from 'react';

type GoalItem = {
    id: number;
    title: string;
    description: string | null;
    level: string;
    parent_title: string | null;
    parent_level: string | null;
    period_name: string;
    target_value: string | null;
    target_unit: string | null;
    actual_value: string | null;
    achievement_percentage: string | null;
    status: string;
    children_count: number;
};

type Period = { id: number; name: string };
type ParentGoal = { id: number; title: string; level: string; period_id: number };

const levelLabels: Record<string, string> = {
    COMPANY: 'Perusahaan',
    BRANCH: 'Cabang',
    DIVISION: 'Divisi',
    INDIVIDUAL: 'Individu',
};

const levelStyles: Record<string, string> = {
    COMPANY: 'bg-purple-50 text-purple-700',
    BRANCH: 'bg-blue-50 text-blue-700',
    DIVISION: 'bg-amber-50 text-amber-700',
    INDIVIDUAL: 'bg-slate-100 text-slate-700',
};

const statusLabels: Record<string, string> = {
    DRAFT: 'Draf',
    ACTIVE: 'Aktif',
    COMPLETED: 'Selesai',
    CANCELLED: 'Dibatalkan',
};

const statusStyles: Record<string, string> = {
    DRAFT: 'bg-slate-100 text-slate-700',
    ACTIVE: 'bg-brand-subtle text-brand-dark',
    COMPLETED: 'bg-green-50 text-green-700',
    CANCELLED: 'bg-red-50 text-red-600',
};

export default function Index({
    goals,
    periods,
    parentGoals,
    filters,
    canCreate,
    canUpdate,
}: {
    goals: { data: GoalItem[]; links: any[] };
    periods: Period[];
    parentGoals: ParentGoal[];
    filters: { period_id: string | null; level: string | null };
    canCreate: boolean;
    canUpdate: boolean;
}) {
    const [showForm, setShowForm] = useState(false);
    const [editingGoal, setEditingGoal] = useState<GoalItem | null>(null);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm text-slate-500">KPI</p>
                        <h1 className="text-[26px] font-bold text-slate-900">Target & Sasaran</h1>
                    </div>
                    {canCreate && (
                        <Button onClick={() => { setShowForm(!showForm); setEditingGoal(null); }} type="button">
                            {showForm ? <X size={16} /> : <Plus size={16} />}
                            {showForm ? 'Batal' : 'Tambah Target'}
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Target & Sasaran" />
            <div className="mx-auto max-w-[1400px] space-y-6 p-4 sm:p-6 lg:p-8">
                {/* Filters */}
                <div className="flex flex-wrap gap-3">
                    <select
                        className="h-10 rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                        onChange={(e) => router.get(route('goals.index'), { period_id: e.target.value || undefined, level: filters.level || undefined }, { preserveState: true })}
                        value={filters.period_id ?? ''}
                    >
                        <option value="">Semua periode</option>
                        {periods.map((p) => (
                            <option key={p.id} value={p.id}>{p.name}</option>
                        ))}
                    </select>
                    <select
                        className="h-10 rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                        onChange={(e) => router.get(route('goals.index'), { period_id: filters.period_id || undefined, level: e.target.value || undefined }, { preserveState: true })}
                        value={filters.level ?? ''}
                    >
                        <option value="">Semua level</option>
                        {Object.entries(levelLabels).map(([k, v]) => (
                            <option key={k} value={k}>{v}</option>
                        ))}
                    </select>
                </div>

                {/* Create/Edit Form */}
                {(showForm || editingGoal) && canCreate && (
                    <GoalForm
                        initial={editingGoal}
                        periods={periods}
                        parentGoals={parentGoals}
                        onDone={() => { setShowForm(false); setEditingGoal(null); }}
                    />
                )}

                {/* Goals list */}
                <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-2 border-b border-slate-100 p-5">
                        <Crosshair size={18} className="text-brand" />
                        <h2 className="font-semibold text-slate-900">Daftar Target</h2>
                    </div>
                    {goals.data.length > 0 ? (
                        <div className="divide-y divide-slate-100">
                            {goals.data.map((goal) => (
                                <div className="p-5" key={goal.id}>
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${levelStyles[goal.level]}`}>
                                                    {levelLabels[goal.level]}
                                                </span>
                                                <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${statusStyles[goal.status]}`}>
                                                    {statusLabels[goal.status]}
                                                </span>
                                                {goal.parent_title && (
                                                    <span className="flex items-center gap-1 text-xs text-slate-400">
                                                        <ArrowDown size={12} />
                                                        dari: {goal.parent_title}
                                                    </span>
                                                )}
                                            </div>
                                            <p className="mt-2 font-semibold text-slate-900">{goal.title}</p>
                                            {goal.description && (
                                                <p className="mt-1 text-sm text-slate-500 line-clamp-2">{goal.description}</p>
                                            )}
                                            <div className="mt-2 flex flex-wrap gap-3 text-xs text-slate-500">
                                                <span>{goal.period_name}</span>
                                                {goal.children_count > 0 && (
                                                    <span className="text-brand">{goal.children_count} sub-target</span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex shrink-0 flex-col items-end gap-2 lg:min-w-[280px]">
                                            {goal.target_value !== null && (
                                                <div className="w-full">
                                                    <div className="flex items-baseline justify-between text-sm">
                                                        <span className="text-slate-500">
                                                            {goal.actual_value ?? '0'} / {goal.target_value} {goal.target_unit ?? ''}
                                                        </span>
                                                        <span className="font-bold text-brand">
                                                            {goal.achievement_percentage !== null
                                                                ? `${Number(goal.achievement_percentage).toFixed(1)}%`
                                                                : '—'}
                                                        </span>
                                                    </div>
                                                    <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                                        <div
                                                            className="h-full rounded-full bg-brand transition-all"
                                                            style={{
                                                                width: `${Math.min(Number(goal.achievement_percentage ?? 0), 100)}%`,
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                            )}
                                            <div className="flex gap-2">
                                                {canUpdate && ['DRAFT', 'ACTIVE'].includes(goal.status) && (
                                                    <button
                                                        className="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-white"
                                                        onClick={() => { setEditingGoal(goal); setShowForm(false); }}
                                                        type="button"
                                                    >
                                                        <Pencil size={14} />
                                                    </button>
                                                )}
                                                {canUpdate && goal.status === 'DRAFT' && (
                                                    <Button
                                                        onClick={() => router.post(route('goals.transition', goal.id), { status: 'ACTIVE' }, { preserveScroll: true })}
                                                        type="button"
                                                        variant="secondary"
                                                    >
                                                        <Target size={14} />
                                                        Aktifkan
                                                    </Button>
                                                )}
                                                {canUpdate && goal.status === 'ACTIVE' && (
                                                    <Button
                                                        onClick={() => {
                                                            if (window.confirm('Tandai target ini sebagai selesai?'))
                                                                router.post(route('goals.transition', goal.id), { status: 'COMPLETED' }, { preserveScroll: true });
                                                        }}
                                                        type="button"
                                                        variant="secondary"
                                                    >
                                                        <CheckCircle2 size={14} />
                                                        Selesai
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="px-4 py-8 text-center">
                            <Crosshair className="mx-auto text-slate-300" size={28} />
                            <p className="mt-3 text-sm font-semibold text-slate-700">Belum ada target</p>
                            <p className="mt-1 text-xs text-slate-500">
                                Buat target dari level perusahaan, lalu turunkan ke divisi dan individu.
                            </p>
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function GoalForm({
    initial,
    periods,
    parentGoals,
    onDone,
}: {
    initial: GoalItem | null;
    periods: Period[];
    parentGoals: ParentGoal[];
    onDone: () => void;
}) {
    const form = useForm({
        parent_id: '',
        period_id: '',
        level: initial?.level ?? 'COMPANY',
        title: initial?.title ?? '',
        description: initial?.description ?? '',
        target_value: initial?.target_value ?? '',
        target_unit: initial?.target_unit ?? '',
        actual_value: initial?.actual_value ?? '',
    });

    const isEditing = Boolean(initial);
    const inputClass = 'mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand';

    const submit = () => {
        if (isEditing && initial) {
            form.put(route('goals.update', initial.id), { preserveScroll: true, onSuccess: onDone });
        } else {
            form.post(route('goals.store'), { preserveScroll: true, onSuccess: () => { form.reset(); onDone(); } });
        }
    };

    const filteredParents = parentGoals.filter(
        (p) => !form.data.period_id || p.period_id === Number(form.data.period_id),
    );

    return (
        <section className="rounded-xl border border-brand/30 bg-white p-5 shadow-sm">
            <div className="mb-3 flex items-center justify-between">
                <h2 className="text-sm font-semibold text-slate-800">
                    {isEditing ? 'Ubah Target' : 'Tambah Target Baru'}
                </h2>
                <button
                    className="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-white"
                    onClick={onDone}
                    type="button"
                >
                    <X size={14} />
                </button>
            </div>
            <form
                className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                onSubmit={(e) => { e.preventDefault(); submit(); }}
            >
                <label className="text-sm font-medium text-slate-700">
                    Judul target
                    <input className={inputClass} onChange={(e) => form.setData('title', e.target.value)} required value={form.data.title} />
                    {form.errors.title && <span className="mt-1 block text-xs text-red-600">{form.errors.title}</span>}
                </label>

                {!isEditing && (
                    <>
                        <label className="text-sm font-medium text-slate-700">
                            Periode
                            <select className={inputClass} onChange={(e) => form.setData('period_id', e.target.value)} required value={form.data.period_id}>
                                <option value="">Pilih...</option>
                                {periods.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                            {form.errors.period_id && <span className="mt-1 block text-xs text-red-600">{form.errors.period_id}</span>}
                        </label>

                        <label className="text-sm font-medium text-slate-700">
                            Level
                            <select className={inputClass} onChange={(e) => form.setData('level', e.target.value)} value={form.data.level}>
                                {Object.entries(levelLabels).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            {form.errors.level && <span className="mt-1 block text-xs text-red-600">{form.errors.level}</span>}
                        </label>

                        <label className="text-sm font-medium text-slate-700">
                            Target induk (opsional)
                            <select className={inputClass} onChange={(e) => form.setData('parent_id', e.target.value)} value={form.data.parent_id}>
                                <option value="">Tidak ada</option>
                                {filteredParents.map((p) => (
                                    <option key={p.id} value={p.id}>[{levelLabels[p.level]}] {p.title}</option>
                                ))}
                            </select>
                        </label>
                    </>
                )}

                <label className="text-sm font-medium text-slate-700">
                    Nilai target
                    <input className={inputClass} min="0" onChange={(e) => form.setData('target_value', e.target.value)} placeholder="100" type="number" value={form.data.target_value} />
                </label>

                <label className="text-sm font-medium text-slate-700">
                    Satuan
                    <input className={inputClass} onChange={(e) => form.setData('target_unit', e.target.value)} placeholder="%, jumlah, Rp..." value={form.data.target_unit} />
                </label>

                {isEditing && (
                    <label className="text-sm font-medium text-slate-700">
                        Nilai aktual
                        <input className={inputClass} min="0" onChange={(e) => form.setData('actual_value', e.target.value)} type="number" value={form.data.actual_value} />
                    </label>
                )}

                <label className="sm:col-span-2 lg:col-span-3 text-sm font-medium text-slate-700">
                    Deskripsi (opsional)
                    <textarea
                        className="mt-1 min-h-16 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                        onChange={(e) => form.setData('description', e.target.value)}
                        value={form.data.description}
                    />
                </label>

                <div>
                    <Button disabled={form.processing} type="submit">
                        {isEditing ? <Pencil size={16} /> : <Plus size={16} />}
                        {form.processing ? 'Menyimpan...' : isEditing ? 'Simpan Perubahan' : 'Tambah Target'}
                    </Button>
                </div>
            </form>
        </section>
    );
}
