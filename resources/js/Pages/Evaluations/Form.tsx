import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { confirmAction } from '@/Utils/confirmation';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Calculator, CalendarDays, CheckCircle2, LoaderCircle, LockKeyhole, RotateCcw, Save } from 'lucide-react';
import { useState } from 'react';

type Score = { component_id: number; contribution_score: string; note: string };
type StoredScore = { component_id: number; weighted_score: string; note?: string };
type Component = { id: number; name: string; default_weight: string; is_auto_calculated: boolean };
type Period = { id: number; name: string; start_date: string; end_date: string };
type Evaluation = { id: number; status: string; notes?: string; total_score: string; scores: StoredScore[] };
type Abilities = { edit: boolean; complete: boolean; reopen: boolean };
type EvaluationFormData = {
    period_id: string;
    intent: 'draft' | 'complete';
    notes: string;
    scores: Score[];
};

export default function Form({
    employee,
    period,
    components,
    evaluation,
    abilities,
}: {
    employee: { id: number; full_name: string; employee_number: string };
    period: Period | null;
    components: Component[];
    evaluation: Evaluation | null;
    abilities: Abilities;
}) {
    const existing = new Map((evaluation?.scores ?? []).map((score) => [score.component_id, score]));
    const form = useForm<EvaluationFormData>({
        period_id: period?.id?.toString() ?? '',
        intent: 'draft' as 'draft' | 'complete',
        notes: evaluation?.notes ?? '',
        scores: components.map((component) => {
            const stored = existing.get(component.id);

            return {
                component_id: component.id,
                contribution_score: component.is_auto_calculated ? '' : (stored?.weighted_score ?? ''),
                note: stored?.note ?? '',
            };
        }),
    });
    const [clientErrors, setClientErrors] = useState<Record<number, string>>({});
    const reopen = useForm({ status: 'DRAFT', reason: '' });
    const editable = abilities.edit && period !== null;
    const completed = evaluation !== null && ['FINALIZED', 'CLOSED'].includes(evaluation.status);
    const legacy = evaluation !== null && ['SUBMITTED', 'APPROVED'].includes(evaluation.status);
    const total = components.reduce((sum, component, index) => {
        if (component.is_auto_calculated) {
            return sum + (Number(existing.get(component.id)?.weighted_score) || 0);
        }

        return sum + (Number(form.data.scores[index]?.contribution_score) || 0);
    }, 0);
    const maximumTotal = components.reduce((sum, component) => sum + Number(component.default_weight), 0);

    const submit = async (intent: 'draft' | 'complete') => {
        const invalid: Record<number, string> = {};
        components.forEach((component, index) => {
            if (component.is_auto_calculated) return;

            const rawValue = form.data.scores[index]?.contribution_score ?? '';
            const value = Number(rawValue);
            const maximum = Number(component.default_weight);
            if (rawValue === '' || !Number.isFinite(value) || value < 0 || value > maximum) {
                invalid[index] = `Nilai ${component.name} harus berada pada rentang 0 sampai ${formatNumber(maximum)}.`;
            }
        });
        setClientErrors(invalid);
        if (Object.keys(invalid).length > 0) return;

        if (intent === 'complete') {
            const confirmed = await confirmAction({
                title: 'Selesaikan Penilaian',
                message: 'Setelah selesai, nilai akan dikunci dan langsung masuk laporan. Pastikan seluruh isian sudah benar.',
                confirmLabel: 'Ya, Selesaikan',
            });
            if (!confirmed) return;
        }

        form.transform((data) => ({ ...data, intent }));
        form.post(route('evaluations.store', employee.id), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={(
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm text-slate-500">Penilaian Kasubdiv</p>
                        <h1 className="text-[26px] font-bold text-slate-900">Nilai Kinerja Pejuang</h1>
                    </div>
                    <StatusBadge evaluation={evaluation} />
                </div>
            )}
        >
            <Head title="Penilaian Pejuang" />
            <div className="mx-auto max-w-[1120px] p-4 sm:p-6 lg:p-8">
                <Link className="mb-4 inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-brand-dark hover:underline" href={route('evaluations.index', period ? { period_id: period.id } : {})}>
                    <ArrowLeft size={16} /> Kembali ke daftar penilaian
                </Link>

                <div className="grid gap-6 lg:grid-cols-[1fr_280px]">
                    <form className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" onSubmit={(event) => event.preventDefault()}>
                        <div className="flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="font-semibold text-slate-900">{employee.full_name}</h2>
                                <p className="mt-0.5 text-sm text-slate-500">{employee.employee_number}</p>
                            </div>
                            {period && (
                                <div className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                    <CalendarDays className="shrink-0 text-brand" size={16} />
                                    <span className="font-medium">{period.name}</span>
                                </div>
                            )}
                        </div>

                        {!period && <Message tone="danger">Tidak ada periode aktif. Hubungi HR untuk mengaktifkan periode penilaian.</Message>}
                        {completed && <Message tone="success">Penilaian ini sudah selesai dan terkunci.</Message>}
                        {legacy && <Message tone="warning">Penilaian ini berasal dari workflow lama dan tersedia sebagai riwayat baca.</Message>}

                        <div className="mt-5 space-y-4">
                            {components.map((component, index) => (
                                <section className="rounded-xl border border-slate-200 p-4" key={component.id}>
                                    <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                                        <div>
                                            <h3 className="font-semibold text-slate-900">{component.name}</h3>
                                            <p className="text-xs text-slate-500">Bobot {Number(component.default_weight)}%</p>
                                        </div>
                                        {component.is_auto_calculated ? (
                                            <div className="text-right">
                                                <span className="inline-flex w-fit items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                                    <Calculator size={13} /> Otomatis
                                                </span>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    {existing.get(component.id)
                                                        ? `Kontribusi ${formatNumber(Number(existing.get(component.id)?.weighted_score))} dari ${formatNumber(Number(component.default_weight))}`
                                                        : 'Dihitung oleh sistem saat disimpan'}
                                                </p>
                                            </div>
                                        ) : (
                                            <label className="text-right text-sm font-medium text-slate-700">
                                                <span className="block">Nilai {component.name} maksimal {formatNumber(Number(component.default_weight))}</span>
                                                <input
                                                    aria-label={`Nilai ${component.name}`}
                                                    aria-describedby={`kontribusi-${component.id}`}
                                                    className="mt-1 h-10 w-28 rounded-lg border-slate-300 text-right focus:border-brand focus:ring-brand"
                                                    disabled={!editable || form.processing}
                                                    inputMode="decimal"
                                                    max={component.default_weight}
                                                    min="0"
                                                    onChange={(event) => {
                                                        form.setData(`scores.${index}.contribution_score`, event.target.value);
                                                        setClientErrors((errors) => {
                                                            const next = { ...errors };
                                                            delete next[index];

                                                            return next;
                                                        });
                                                    }}
                                                    required
                                                    step="0.01"
                                                    type="number"
                                                    value={form.data.scores[index].contribution_score}
                                                />
                                                <span className="mt-1 block text-xs font-normal text-slate-500" id={`kontribusi-${component.id}`}>
                                                    Kontribusi {formatNumber(Number(form.data.scores[index].contribution_score) || 0)} dari {formatNumber(Number(component.default_weight))}
                                                </span>
                                            </label>
                                        )}
                                    </div>
                                    {!component.is_auto_calculated && (
                                        <textarea
                                            aria-label={`Catatan ${component.name}`}
                                            className="mt-3 min-h-20 w-full rounded-lg border-slate-300 text-sm focus:border-brand focus:ring-brand"
                                            disabled={!editable || form.processing}
                                            onChange={(event) => form.setData(`scores.${index}.note`, event.target.value)}
                                            placeholder="Catatan pendukung (opsional)"
                                            value={form.data.scores[index].note ?? ''}
                                        />
                                    )}
                                    {(clientErrors[index] || form.errors[`scores.${index}.contribution_score`]) && (
                                        <p className="mt-1 text-xs text-red-600">{clientErrors[index] || form.errors[`scores.${index}.contribution_score`]}</p>
                                    )}
                                </section>
                            ))}
                        </div>

                        <label className="mt-5 block text-sm font-semibold text-slate-700">
                            Catatan umum
                            <textarea
                                className="mt-2 min-h-28 w-full rounded-lg border-slate-300 text-sm focus:border-brand focus:ring-brand"
                                disabled={!editable || form.processing}
                                maxLength={5000}
                                onChange={(event) => form.setData('notes', event.target.value)}
                                placeholder="Ringkasan hasil penilaian (opsional)"
                                value={form.data.notes}
                            />
                        </label>

                        {Object.values(form.errors).length > 0 && (
                            <p className="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">Periksa kembali isian penilaian yang belum valid.</p>
                        )}

                        {editable && (
                            <div className="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                                <Button disabled={form.processing} onClick={() => submit('draft')} type="button" variant="secondary">
                                    {form.processing ? <LoaderCircle className="animate-spin" size={16} /> : <Save size={16} />}
                                    {form.processing ? 'Menyimpan...' : 'Simpan Draf'}
                                </Button>
                                <Button disabled={form.processing || !abilities.complete} onClick={() => submit('complete')} type="button">
                                    {form.processing ? <LoaderCircle className="animate-spin" size={16} /> : <CheckCircle2 size={16} />}
                                    {form.processing ? 'Menyimpan...' : 'Selesaikan Penilaian'}
                                </Button>
                            </div>
                        )}
                    </form>

                    <aside className="space-y-4">
                        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm text-slate-500">Perkiraan nilai akhir</p>
                            <p className="mt-1 text-3xl font-bold text-slate-900">{total.toFixed(2)}</p>
                            <p className="mt-1 text-xs font-medium text-slate-600">Maksimal {formatNumber(maximumTotal)}</p>
                            <p className="mt-3 text-xs leading-5 text-slate-500">Nilai resmi dihitung kembali oleh server ketika disimpan.</p>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                            <div className="flex items-center gap-2 font-semibold text-slate-800"><LockKeyhole size={16} /> Alur ringkas</div>
                            <p className="mt-2 leading-6">Simpan sebagai draf jika belum selesai. Pilih Selesaikan agar hasil dikunci dan langsung masuk laporan.</p>
                        </div>
                        {abilities.reopen && evaluation && (
                            <form className="rounded-xl border border-amber-200 bg-amber-50 p-5" onSubmit={(event) => { event.preventDefault(); reopen.post(route('evaluations.transition', evaluation.id), { preserveScroll: true }); }}>
                                <div className="flex items-center gap-2 font-semibold text-amber-900"><RotateCcw size={16} /> Buka kembali</div>
                                <p className="mt-2 text-xs leading-5 text-amber-800">Gunakan hanya untuk koreksi. Alasan akan dicatat pada audit.</p>
                                <textarea
                                    className="mt-3 min-h-24 w-full rounded-lg border-amber-300 text-sm focus:border-amber-600 focus:ring-amber-600"
                                    maxLength={1000}
                                    minLength={10}
                                    onChange={(event) => reopen.setData('reason', event.target.value)}
                                    placeholder="Jelaskan alasan pembukaan kembali"
                                    required
                                    value={reopen.data.reason}
                                />
                                {reopen.errors.reason && <p className="mt-1 text-xs text-red-600">{reopen.errors.reason}</p>}
                                <Button className="mt-3 w-full" disabled={reopen.processing} type="submit" variant="secondary">Buka sebagai Draf</Button>
                            </form>
                        )}
                    </aside>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function formatNumber(value: number): string {
    return Number.isInteger(value) ? String(value) : value.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
}

function StatusBadge({ evaluation }: { evaluation: Evaluation | null }) {
    const completed = evaluation && ['FINALIZED', 'CLOSED'].includes(evaluation.status);
    const label = evaluation === null ? 'Belum Dinilai' : evaluation.status === 'DRAFT' ? 'Draf' : completed ? 'Selesai' : 'Riwayat';
    const style = evaluation === null ? 'bg-slate-100 text-slate-700' : evaluation.status === 'DRAFT' ? 'bg-amber-100 text-amber-800' : completed ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800';

    return <span className={`w-fit rounded-full px-3 py-1.5 text-sm font-semibold ${style}`}>{label}</span>;
}

function Message({ children, tone }: { children: React.ReactNode; tone: 'danger' | 'success' | 'warning' }) {
    const styles = {
        danger: 'border-red-200 bg-red-50 text-red-700',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        warning: 'border-amber-200 bg-amber-50 text-amber-800',
    };

    return <div className={`mt-4 rounded-lg border px-4 py-3 text-sm ${styles[tone]}`}>{children}</div>;
}
