import { confirmAction } from '@/Utils/confirmation';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { ArrowDown, CheckCircle2, CircleAlert, Crosshair, Pencil, Plus, Target, Trash2, X } from 'lucide-react';
import { useState } from 'react';

type GoalLevel = 'COMPANY' | 'BRANCH' | 'DIVISION' | 'INDIVIDUAL';

type GoalItem = {
    id: number;
    title: string;
    description: string | null;
    level: GoalLevel;
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
type ParentGoal = { id: number; title: string; level: GoalLevel; period_id: number; target_id: number | null };
type TargetOption = {
    id: number;
    label: string;
    branch_id?: number;
    division_id?: number | null;
};
type GoalTargetOptions = {
    allowed_levels: GoalLevel[];
    branches: TargetOption[];
    divisions: TargetOption[];
    employees: TargetOption[];
};

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
    targetOptions,
    filters,
    canCreate,
    canUpdate,
}: {
    goals: { data: GoalItem[]; links: unknown[] };
    periods: Period[];
    parentGoals: ParentGoal[];
    targetOptions: GoalTargetOptions;
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
                {((showForm && canCreate) || (editingGoal && canUpdate)) && (
                    <GoalForm
                        initial={editingGoal}
                        periods={periods}
                        parentGoals={parentGoals}
                        targetOptions={targetOptions}
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
                                                    <button
                                                        className="flex size-8 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50"
                                                        onClick={async () => {
                                                            if (await confirmAction({ title: 'Hapus Target', message: 'Hapus target ini? Tindakan tidak dapat dibatalkan.', confirmLabel: 'Ya, Hapus', variant: 'danger' }))
                                                                router.delete(route('goals.destroy', goal.id), { preserveScroll: true });
                                                        }}
                                                        title="Hapus target"
                                                        type="button"
                                                    >
                                                        <Trash2 size={14} />
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
                                                        onClick={async () => {
                                                            if (await confirmAction({ title: 'Selesaikan Target', message: 'Tandai target ini sebagai selesai?', confirmLabel: 'Ya, Selesaikan' }))
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
    targetOptions,
    onDone,
}: {
    initial: GoalItem | null;
    periods: Period[];
    parentGoals: ParentGoal[];
    targetOptions: GoalTargetOptions;
    onDone: () => void;
}) {
    const defaultLevel = targetOptions.allowed_levels[0] ?? 'COMPANY';
    const form = useForm({
        parent_id: '',
        period_id: '',
        level: initial?.level ?? defaultLevel,
        goalable_id: '',
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

    const level = form.data.level as GoalLevel;
    const targetCollections: Record<Exclude<GoalLevel, 'COMPANY'>, TargetOption[]> = {
        BRANCH: targetOptions.branches,
        DIVISION: targetOptions.divisions,
        INDIVIDUAL: targetOptions.employees,
    };
    const selectedTarget = level === 'COMPANY'
        ? null
        : targetCollections[level].find((option) => option.id === Number(form.data.goalable_id)) ?? null;
    const levelHierarchy: Record<GoalLevel, number> = {
        COMPANY: 0,
        BRANCH: 1,
        DIVISION: 2,
        INDIVIDUAL: 3,
    };
    const filteredParents = parentGoals.filter((parent) => {
        if (!form.data.period_id || parent.period_id !== Number(form.data.period_id)) {
            return false;
        }

        if (levelHierarchy[parent.level] >= levelHierarchy[level]) {
            return false;
        }

        if (parent.level === 'COMPANY') {
            return true;
        }

        if (!selectedTarget) {
            return false;
        }

        if (parent.level === 'BRANCH') {
            return selectedTarget.branch_id === parent.target_id;
        }

        return parent.level === 'DIVISION'
            && level === 'INDIVIDUAL'
            && selectedTarget.division_id === parent.target_id;
    });
    const targetLabels: Record<Exclude<GoalLevel, 'COMPANY'>, string> = {
        BRANCH: 'Cabang',
        DIVISION: 'Divisi',
        INDIVIDUAL: 'Pejuang',
    };
    const visibleErrors = Array.from(new Set(Object.values(form.errors).filter(Boolean)));

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
                {visibleErrors.length > 0 && (
                    <div
                        aria-live="polite"
                        className="flex gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 sm:col-span-2 lg:col-span-3"
                        role="alert"
                    >
                        <CircleAlert className="mt-0.5 shrink-0" size={16} />
                        <div>
                            <p className="font-semibold">Target belum dapat disimpan.</p>
                            <ul className="mt-1 list-disc space-y-0.5 pl-4">
                                {visibleErrors.map((error) => <li key={error}>{error}</li>)}
                            </ul>
                        </div>
                    </div>
                )}

                <label className="text-sm font-medium text-slate-700">
                    Judul target
                    <input className={inputClass} onChange={(e) => form.setData('title', e.target.value)} required value={form.data.title} />
                    {form.errors.title && <span className="mt-1 block text-xs text-red-600">{form.errors.title}</span>}
                </label>

                {!isEditing && (
                    <>
                        <label className="text-sm font-medium text-slate-700">
                            Periode
                            <select
                                className={inputClass}
                                onChange={(e) => form.setData((data) => ({ ...data, period_id: e.target.value, parent_id: '' }))}
                                required
                                value={form.data.period_id}
                            >
                                <option value="">Pilih...</option>
                                {periods.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                            {form.errors.period_id && <span className="mt-1 block text-xs text-red-600">{form.errors.period_id}</span>}
                        </label>

                        <label className="text-sm font-medium text-slate-700">
                            Level
                            <select
                                className={inputClass}
                                onChange={(e) => form.setData((data) => ({
                                    ...data,
                                    level: e.target.value as GoalLevel,
                                    goalable_id: '',
                                    parent_id: '',
                                }))}
                                value={form.data.level}
                            >
                                {targetOptions.allowed_levels.map((allowedLevel) => (
                                    <option key={allowedLevel} value={allowedLevel}>{levelLabels[allowedLevel]}</option>
                                ))}
                            </select>
                            {form.errors.level && <span className="mt-1 block text-xs text-red-600">{form.errors.level}</span>}
                        </label>

                        {level === 'COMPANY' ? (
                            <div className="rounded-[9px] border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                                Target berlaku untuk seluruh perusahaan.
                            </div>
                        ) : (
                            <label className="text-sm font-medium text-slate-700">
                                {targetLabels[level]}
                                <select
                                    className={inputClass}
                                    onChange={(e) => form.setData((data) => ({ ...data, goalable_id: e.target.value, parent_id: '' }))}
                                    required
                                    value={form.data.goalable_id}
                                >
                                    <option value="">Pilih {targetLabels[level].toLowerCase()}...</option>
                                    {targetCollections[level].map((option) => (
                                        <option key={option.id} value={option.id}>{option.label}</option>
                                    ))}
                                </select>
                                {form.errors.goalable_id && <span className="mt-1 block text-xs text-red-600">{form.errors.goalable_id}</span>}
                            </label>
                        )}

                        <label className="text-sm font-medium text-slate-700">
                            Target induk (opsional)
                            <select className={inputClass} onChange={(e) => form.setData('parent_id', e.target.value)} value={form.data.parent_id}>
                                <option value="">Tidak ada</option>
                                {filteredParents.map((p) => (
                                    <option key={p.id} value={p.id}>[{levelLabels[p.level]}] {p.title}</option>
                                ))}
                            </select>
                            {form.errors.parent_id && <span className="mt-1 block text-xs text-red-600">{form.errors.parent_id}</span>}
                        </label>
                    </>
                )}

                <label className="text-sm font-medium text-slate-700">
                    Nilai target
                    <input className={inputClass} min="0" onChange={(e) => form.setData('target_value', e.target.value)} placeholder="100" type="number" value={form.data.target_value} />
                    {form.errors.target_value && <span className="mt-1 block text-xs text-red-600">{form.errors.target_value}</span>}
                </label>

                <label className="text-sm font-medium text-slate-700">
                    Satuan
                    <input className={inputClass} onChange={(e) => form.setData('target_unit', e.target.value)} placeholder="%, jumlah, Rp..." value={form.data.target_unit} />
                    {form.errors.target_unit && <span className="mt-1 block text-xs text-red-600">{form.errors.target_unit}</span>}
                </label>

                {isEditing && (
                    <label className="text-sm font-medium text-slate-700">
                        Nilai aktual
                        <input className={inputClass} min="0" onChange={(e) => form.setData('actual_value', e.target.value)} type="number" value={form.data.actual_value} />
                        {form.errors.actual_value && <span className="mt-1 block text-xs text-red-600">{form.errors.actual_value}</span>}
                    </label>
                )}

                <label className="sm:col-span-2 lg:col-span-3 text-sm font-medium text-slate-700">
                    Deskripsi (opsional)
                    <textarea
                        className="mt-1 min-h-16 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                        onChange={(e) => form.setData('description', e.target.value)}
                        value={form.data.description}
                    />
                    {form.errors.description && <span className="mt-1 block text-xs text-red-600">{form.errors.description}</span>}
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
