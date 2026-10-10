import { confirmAction } from '@/Utils/confirmation';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, CheckCircle2, Pencil, Scale, Send, X } from 'lucide-react';
import { useState } from 'react';

type Adjustment = {
    id: number;
    adjusted_score: string;
    reason: string | null;
    adjusted_by: string;
    difference: number;
};

type EvaluationItem = {
    id: number;
    employee_name: string;
    employee_number: string;
    original_score: string;
    criterion_name: string | null;
    criterion_color: string | null;
    status: string;
    adjustment: Adjustment | null;
};

type SessionData = {
    id: number;
    name: string;
    description: string | null;
    status: string;
    period_name: string;
    calibrated_at: string | null;
};

type Criterion = {
    id: number;
    name: string;
    min_score: string;
    max_score: string;
    color_semantic: string;
};

const statusLabels: Record<string, string> = { DRAFT: 'Draf', IN_REVIEW: 'Ditinjau', FINALIZED: 'Final', APPLIED: 'Diterapkan' };
const statusStyles: Record<string, string> = {
    DRAFT: 'bg-slate-100 text-slate-700',
    IN_REVIEW: 'bg-amber-50 text-amber-800',
    FINALIZED: 'bg-blue-50 text-blue-700',
    APPLIED: 'bg-brand-subtle text-brand-dark',
};

export default function Show({
    session,
    evaluations,
    criteria,
    canManage,
    isEditable,
}: {
    session: SessionData;
    evaluations: EvaluationItem[];
    criteria: Criterion[];
    canManage: boolean;
    isEditable: boolean;
}) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const transition = async (status: string) => {
        const label = statusLabels[status] ?? status;
        if (!await confirmAction({ title: 'Ubah Status Kalibrasi', message: `Ubah status sesi kalibrasi menjadi ${label}?`, confirmLabel: 'Ya, Ubah' })) return;
        router.post(route('calibration.transition', session.id), { status }, { preserveScroll: true });
    };

    const applyCalibration = async () => {
        if (!await confirmAction({ title: 'Terapkan Kalibrasi', message: 'Terapkan semua penyesuaian ke penilaian asli? Tindakan ini tidak dapat dibatalkan.', confirmLabel: 'Ya, Terapkan', variant: 'danger' })) return;
        router.post(route('calibration.apply', session.id), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm text-slate-500">Kalibrasi</p>
                        <h1 className="text-[26px] font-bold text-slate-900">{session.name}</h1>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <span className={`rounded-full px-3 py-1.5 text-sm font-semibold ${statusStyles[session.status]}`}>
                            {statusLabels[session.status]}
                        </span>
                        {canManage && session.status === 'DRAFT' && (
                            <Button onClick={() => transition('IN_REVIEW')} type="button" variant="secondary">
                                <Send size={16} />
                                Ajukan Peninjauan
                            </Button>
                        )}
                        {canManage && session.status === 'IN_REVIEW' && (
                            <Button onClick={() => transition('FINALIZED')} type="button" variant="secondary">
                                <CheckCircle2 size={16} />
                                Finalisasi
                            </Button>
                        )}
                        {canManage && session.status === 'FINALIZED' && (
                            <Button onClick={applyCalibration} type="button">
                                <Check size={16} />
                                Terapkan ke Penilaian
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Kalibrasi: ${session.name}`} />
            <div className="mx-auto max-w-[1400px] space-y-6 p-4 sm:p-6 lg:p-8">
                {/* Info */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <InfoCard label="Periode" value={session.period_name} />
                    <InfoCard label="Status" value={statusLabels[session.status]} />
                    <InfoCard
                        label="Jumlah Penyesuaian"
                        value={`${evaluations.filter((e) => e.adjustment).length} dari ${evaluations.length}`}
                    />
                </div>

                {session.description && (
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-sm text-slate-600">{session.description}</p>
                    </div>
                )}

                {/* Score distribution */}
                {criteria.length > 0 && (
                    <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                            <Scale size={18} />
                            Distribusi Nilai
                        </h2>
                        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            {criteria.map((c) => {
                                const count = evaluations.filter((e) => {
                                    const score = e.adjustment
                                        ? Number(e.adjustment.adjusted_score)
                                        : Number(e.original_score);
                                    return score >= Number(c.min_score) && score <= Number(c.max_score);
                                }).length;
                                return (
                                    <div
                                        key={c.id}
                                        className={`rounded-lg border p-3 ${semanticBg[c.color_semantic] ?? 'border-slate-200 bg-slate-50'}`}
                                    >
                                        <p className="text-sm font-semibold">{c.name}</p>
                                        <p className="text-xs text-slate-500">
                                            {c.min_score}–{c.max_score}
                                        </p>
                                        <p className="mt-2 text-2xl font-bold">{count}</p>
                                        <p className="text-xs text-slate-500">karyawan</p>
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                )}

                {/* Evaluations table */}
                <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 p-5">
                        <h2 className="font-semibold text-slate-900">Daftar Penilaian</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                    <th className="px-5 py-3">Karyawan</th>
                                    <th className="px-5 py-3 text-right">Nilai Asli</th>
                                    <th className="px-5 py-3">Kriteria</th>
                                    <th className="px-5 py-3 text-right">Nilai Disesuaikan</th>
                                    <th className="px-5 py-3 text-right">Selisih</th>
                                    <th className="px-5 py-3">Alasan</th>
                                    {isEditable && canManage && <th className="px-5 py-3" />}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {evaluations.map((item) => (
                                    <tr key={item.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-3">
                                            <p className="font-medium text-slate-800">{item.employee_name}</p>
                                            <p className="text-xs text-slate-500">{item.employee_number}</p>
                                        </td>
                                        <td className="px-5 py-3 text-right font-medium text-slate-900">
                                            {Number(item.original_score).toFixed(2)}
                                        </td>
                                        <td className="px-5 py-3">
                                            {item.criterion_name && (
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-semibold ${semanticBadge[item.criterion_color ?? 'neutral']}`}
                                                >
                                                    {item.criterion_name}
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-5 py-3 text-right font-medium">
                                            {editingId === item.id ? (
                                                <AdjustmentForm
                                                    sessionId={session.id}
                                                    evaluationId={item.id}
                                                    originalScore={item.original_score}
                                                    currentAdjustment={item.adjustment}
                                                    onDone={() => setEditingId(null)}
                                                />
                                            ) : item.adjustment ? (
                                                <span className="text-brand">{Number(item.adjustment.adjusted_score).toFixed(2)}</span>
                                            ) : (
                                                <span className="text-slate-400">—</span>
                                            )}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            {item.adjustment && (
                                                <span
                                                    className={
                                                        item.adjustment.difference > 0
                                                            ? 'text-green-600'
                                                            : item.adjustment.difference < 0
                                                              ? 'text-red-600'
                                                              : 'text-slate-500'
                                                    }
                                                >
                                                    {item.adjustment.difference > 0 ? '+' : ''}
                                                    {item.adjustment.difference.toFixed(2)}
                                                </span>
                                            )}
                                        </td>
                                        <td className="max-w-[200px] truncate px-5 py-3 text-xs text-slate-500">
                                            {item.adjustment?.reason ?? '—'}
                                        </td>
                                        {isEditable && canManage && editingId !== item.id && (
                                            <td className="px-5 py-3">
                                                <button
                                                    className="flex size-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-white"
                                                    onClick={() => setEditingId(item.id)}
                                                    type="button"
                                                >
                                                    <Pencil size={14} />
                                                </button>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function AdjustmentForm({
    sessionId,
    evaluationId,
    originalScore,
    currentAdjustment,
    onDone,
}: {
    sessionId: number;
    evaluationId: number;
    originalScore: string;
    currentAdjustment: Adjustment | null;
    onDone: () => void;
}) {
    const form = useForm({
        evaluation_id: evaluationId,
        adjusted_score: currentAdjustment?.adjusted_score ?? originalScore,
        reason: currentAdjustment?.reason ?? '',
    });

    const submit = () =>
        form.post(route('calibration.adjust', sessionId), { preserveScroll: true, onSuccess: onDone });

    return (
        <div className="flex items-center gap-2">
            <input
                className="h-8 w-20 rounded border-slate-300 text-right text-sm focus:border-brand focus:ring-brand"
                max="100"
                min="0"
                onChange={(e) => form.setData('adjusted_score', e.target.value)}
                type="number"
                value={form.data.adjusted_score}
            />
            <input
                className="h-8 w-32 rounded border-slate-300 text-sm focus:border-brand focus:ring-brand"
                onChange={(e) => form.setData('reason', e.target.value)}
                placeholder="Alasan..."
                value={form.data.reason}
            />
            <button
                className="flex size-7 items-center justify-center rounded bg-brand text-white hover:opacity-90"
                disabled={form.processing}
                onClick={submit}
                type="button"
            >
                <Check size={14} />
            </button>
            <button
                className="flex size-7 items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-white"
                onClick={onDone}
                type="button"
            >
                <X size={14} />
            </button>
        </div>
    );
}

function InfoCard({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-1 text-lg font-bold text-slate-900">{value}</p>
        </div>
    );
}

const semanticBg: Record<string, string> = {
    success: 'border-green-200 bg-green-50',
    warning: 'border-amber-200 bg-amber-50',
    danger: 'border-red-200 bg-red-50',
    info: 'border-blue-200 bg-blue-50',
    neutral: 'border-slate-200 bg-slate-50',
};

const semanticBadge: Record<string, string> = {
    success: 'bg-green-50 text-green-700',
    warning: 'bg-amber-50 text-amber-700',
    danger: 'bg-red-50 text-red-700',
    info: 'bg-blue-50 text-blue-700',
    neutral: 'bg-slate-100 text-slate-700',
};
