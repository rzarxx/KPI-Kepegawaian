import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Calculator, CalendarDays, ClipboardCheck, LockKeyhole, Send } from 'lucide-react';

type Score = { component_id: number; raw_score: string; note?: string };
type Component = { id: number; name: string; default_weight: string; is_auto_calculated: boolean };
type Period = { id: number; name: string; start_date: string; end_date: string };
type Evaluation = { id: number; status: string; notes?: string; total_score: string; scores: Score[] };
type Abilities = { edit: boolean; submit: boolean };

export default function Form({
    employee,
    period,
    components,
    evaluation,
    abilities,
}: {
    employee: { id: number; full_name: string; employee_number: string };
    period: Period;
    components: Component[];
    evaluation: Evaluation | null;
    abilities: Abilities;
}) {
    const existing = new Map((evaluation?.scores ?? []).map((s) => [s.component_id, s]));
    const form = useForm({
        notes: evaluation?.notes ?? '',
        scores: components.map((c) => existing.get(c.id) ?? { component_id: c.id, raw_score: '', note: '' }),
    });
    const total = form.data.scores.reduce(
        (sum, score, i) => sum + ((Number(score.raw_score) || 0) * Number(components[i].default_weight)) / 100,
        0,
    );
    const editable = abilities.edit;
    const scoreError = (index: number) =>
        Object.entries(form.errors).find(([field]) => field === 'scores.' + index + '.raw_score')?.[1];

    const save = () => form.post(route('self-assessment.store', period.id), { preserveScroll: true });
    const submit = () => {
        if (!evaluation || !window.confirm('Setelah diajukan, penilaian diri tidak dapat diubah lagi. Lanjutkan?')) return;
        router.post(route('self-assessment.submit', evaluation.id), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm text-slate-500">Penilaian</p>
                        <h1 className="text-[26px] font-bold text-slate-900">Penilaian Diri</h1>
                    </div>
                    {evaluation && (
                        <span className={'w-fit rounded-full px-3 py-1.5 text-sm font-semibold ' + statusStyles[evaluation.status]}>
                            {statusLabels[evaluation.status]}
                        </span>
                    )}
                </div>
            }
        >
            <Head title="Penilaian Diri" />
            <div className="mx-auto grid max-w-[1200px] gap-6 p-4 sm:p-6 lg:grid-cols-[1fr_280px] lg:p-8">
                <form
                    className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
                    onSubmit={(e) => {
                        e.preventDefault();
                        save();
                    }}
                >
                    <div className="flex flex-col gap-1 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-brand-subtle">
                                <ClipboardCheck size={20} className="text-brand" />
                            </div>
                            <div>
                                <h2 className="font-semibold text-slate-900">{employee.full_name}</h2>
                                <p className="mt-0.5 text-sm text-slate-500">{employee.employee_number}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            <CalendarDays size={15} className="shrink-0 text-brand" />
                            <span className="font-medium">{period.name}</span>
                            <span className="text-slate-400">·</span>
                            <span className="text-xs text-slate-500">
                                {period.start_date} s/d {period.end_date}
                            </span>
                        </div>
                    </div>

                    <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        <p className="font-semibold">Petunjuk Penilaian Diri</p>
                        <ul className="mt-1 list-inside list-disc space-y-0.5 text-blue-700">
                            <li>Berikan nilai 0–100 untuk setiap komponen berdasarkan evaluasi diri Anda</li>
                            <li>Penilaian diri bersifat subyektif dan digunakan sebagai referensi oleh atasan</li>
                            <li>Setelah diajukan, penilaian tidak dapat diubah lagi</li>
                        </ul>
                    </div>

                    {!editable && (
                        <div className="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                            <LockKeyhole className="mt-0.5 shrink-0" size={17} />
                            <p>Penilaian diri ini sudah diajukan dan tidak dapat diubah lagi.</p>
                        </div>
                    )}

                    <div className="mt-6 space-y-4">
                        {components.map((component, index) => (
                            <div className="grid gap-3 border-b border-slate-100 pb-4 sm:grid-cols-[1fr_150px]" key={component.id}>
                                <div>
                                    <p className="font-medium text-slate-800">{component.name}</p>
                                    <p className="text-xs text-slate-500">Bobot {component.default_weight}%</p>
                                </div>
                                <div>
                                    <input
                                        aria-label={'Nilai ' + component.name}
                                        className="h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand disabled:bg-slate-50 disabled:text-slate-500"
                                        disabled={!editable}
                                        max="100"
                                        min="0"
                                        onChange={(e) => {
                                            const scores = [...form.data.scores];
                                            scores[index] = { ...scores[index], raw_score: e.target.value };
                                            form.setData('scores', scores);
                                        }}
                                        placeholder="0-100"
                                        type="number"
                                        value={form.data.scores[index].raw_score}
                                    />
                                    {scoreError(index) && <p className="mt-1 text-xs text-red-600">{scoreError(index)}</p>}
                                </div>
                                {editable && (
                                    <textarea
                                        className="col-span-full min-h-16 rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand"
                                        onChange={(e) => {
                                            const scores = [...form.data.scores];
                                            scores[index] = { ...scores[index], note: e.target.value };
                                            form.setData('scores', scores);
                                        }}
                                        placeholder="Alasan/catatan untuk komponen ini (opsional)"
                                        value={form.data.scores[index].note ?? ''}
                                    />
                                )}
                            </div>
                        ))}
                    </div>

                    {form.errors.scores && <p className="mt-3 text-sm text-red-600">{form.errors.scores}</p>}

                    <textarea
                        className="mt-5 min-h-24 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand disabled:bg-slate-50"
                        disabled={!editable}
                        onChange={(e) => form.setData('notes', e.target.value)}
                        placeholder="Catatan tambahan (opsional)"
                        value={form.data.notes}
                    />

                    <div className="mt-5 flex flex-wrap justify-end gap-3">
                        {editable && (
                            <Button disabled={form.processing} type="submit" variant="secondary">
                                Simpan Draf
                            </Button>
                        )}
                        {abilities.submit && (
                            <Button disabled={form.processing} onClick={submit} type="button">
                                <Send size={16} />
                                Ajukan Penilaian Diri
                            </Button>
                        )}
                    </div>
                </form>

                <aside className="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                        <Calculator size={18} />
                        Ringkasan Nilai
                    </h2>
                    <p className="mt-5 text-sm text-slate-500">{evaluation ? 'Total tersimpan' : 'Perkiraan sementara'}</p>
                    <p className="mt-1 text-3xl font-bold text-slate-900">
                        {evaluation ? Number(evaluation.total_score).toFixed(2) : total.toFixed(2)}
                    </p>
                    <p className="mt-4 text-xs leading-5 text-slate-500">
                        Nilai ini merupakan hasil penilaian diri Anda sendiri. Atasan akan memberikan penilaian terpisah.
                    </p>
                    <div className="mt-4 border-t border-slate-100 pt-4">
                        <p className="text-xs font-semibold text-slate-500">PERIODE</p>
                        <p className="mt-1 text-sm font-medium text-slate-800">{period.name}</p>
                        <p className="text-xs text-slate-500">
                            {period.start_date} s/d {period.end_date}
                        </p>
                    </div>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}

const statusLabels: Record<string, string> = { DRAFT: 'Draf', SUBMITTED: 'Diajukan' };
const statusStyles: Record<string, string> = { DRAFT: 'bg-slate-100 text-slate-700', SUBMITTED: 'bg-blue-50 text-blue-700' };
