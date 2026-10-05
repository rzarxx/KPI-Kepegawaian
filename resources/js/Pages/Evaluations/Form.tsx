import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Calculator, CalendarDays, CheckCircle2, ClipboardCheck, LockKeyhole, Send } from 'lucide-react';

type Score = { component_id: number; raw_score: string; note?: string };
type Component = { id: number; name: string; default_weight: string; is_auto_calculated: boolean };
type Period = { id: number; name: string; start_date: string; end_date: string };
type Evaluation = { id: number; status: 'DRAFT' | 'SUBMITTED' | 'APPROVED' | 'FINALIZED' | 'CLOSED'; notes?: string; total_score: string; scores: Score[] };
type SelfAssessment = { id: number; status: string; total_score: string; notes?: string; scores: Score[] } | null;
type Abilities = { edit: boolean; submit: boolean; approve: boolean; finalize: boolean; close: boolean };

export default function Form({
    employee,
    period,
    periods,
    components,
    evaluation,
    selfAssessment,
    abilities,
}: {
    employee: { id: number; full_name: string; employee_number: string };
    period: Period | null;
    periods: Period[];
    components: Component[];
    evaluation: Evaluation | null;
    selfAssessment?: SelfAssessment;
    abilities: Abilities;
}) {
    const existing = new Map((evaluation?.scores ?? []).map((score) => [score.component_id, score]));
    const selfScores = new Map((selfAssessment?.scores ?? []).map((score) => [score.component_id, score]));
    const form = useForm({
        period_id: period?.id?.toString() ?? '',
        notes: evaluation?.notes ?? '',
        scores: components.map((component) => existing.get(component.id) ?? { component_id: component.id, raw_score: '', note: '' }),
    });
    const total = form.data.scores.reduce((sum, score, index) => sum + (Number(score.raw_score) || 0) * Number(components[index].default_weight) / 100, 0);
    const editable = !evaluation || abilities.edit;
    const transition = transitionFor(evaluation, abilities);
    const scoreError = (index: number) => Object.entries(form.errors)
        .find(([field]) => field === 'scores.' + index + '.raw_score')?.[1];

    const save = () => form.post(route('evaluations.store', employee.id), { preserveScroll: true });
    const move = () => {
        if (!evaluation || !transition) return;
        if (['FINALIZED', 'CLOSED'].includes(transition.status) && !window.confirm(transition.confirmation)) return;
        router.post(route('evaluations.transition', evaluation.id), { status: transition.status }, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header={<div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><p className="text-sm text-slate-500">Penilaian</p><h1 className="text-[26px] font-bold text-slate-900">Penilaian Pejuang</h1></div>{evaluation && <span className={'w-fit rounded-full px-3 py-1.5 text-sm font-semibold ' + statusStyles[evaluation.status]}>{statusLabels[evaluation.status]}</span>}</div>}>
            <Head title="Penilaian Pejuang" />
            <div className="mx-auto grid max-w-[1200px] gap-6 p-4 sm:p-6 lg:grid-cols-[1fr_280px] lg:p-8">
                <form className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" onSubmit={(event) => { event.preventDefault(); save(); }}>
                    <div className="flex flex-col gap-1 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 className="font-semibold text-slate-900">{employee.full_name}</h2>
                            <p className="mt-0.5 text-sm text-slate-500">{employee.employee_number}</p>
                        </div>
                        {period && (
                            <div className="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                <CalendarDays size={15} className="shrink-0 text-brand" />
                                <span className="font-medium">{period.name}</span>
                                <span className="text-slate-400">·</span>
                                <span className="text-xs text-slate-500">{period.start_date.slice(0, 10)} s/d {period.end_date.slice(0, 10)}</span>
                            </div>
                        )}
                    </div>

                    {!period && (
                        <div className="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            Periode penilaian tidak ditemukan. Kembali ke halaman pejuang dan pilih periode yang tersedia.
                        </div>
                    )}

                    {selfAssessment && selfAssessment.status === 'SUBMITTED' && (
                        <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                            <div className="flex items-center gap-2">
                                <ClipboardCheck size={16} className="shrink-0" />
                                <span className="font-semibold">Penilaian Diri Tersedia</span>
                            </div>
                            <p className="mt-1 text-blue-700">
                                Pejuang telah mengisi penilaian diri dengan total skor{' '}
                                <span className="font-bold">{Number(selfAssessment.total_score).toFixed(2)}</span>.
                                Nilai penilaian diri ditampilkan sebagai referensi di setiap komponen.
                            </p>
                        </div>
                    )}

                    {!editable && <div className="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600"><LockKeyhole className="mt-0.5 shrink-0" size={17} /><p>Penilaian ini sudah diajukan dan tidak dapat diubah. Silakan gunakan opsi yang tersedia untuk melanjutkan proses.</p></div>}
                    <div className="mt-6 space-y-4">{components.map((component, index) => { const selfScore = selfScores.get(component.id); return <div className="grid gap-3 border-b border-slate-100 pb-4 sm:grid-cols-[1fr_150px]" key={component.id}><div><p className="font-medium text-slate-800">{component.name}</p><p className="text-xs text-slate-500">Bobot {component.default_weight}% {component.is_auto_calculated ? '· dihitung otomatis' : ''}</p>{selfScore && <p className="mt-1 flex items-center gap-1.5 text-xs text-blue-600"><ClipboardCheck size={12} />Penilaian diri: <span className="font-semibold">{selfScore.raw_score}</span>{selfScore.note && <span className="text-blue-500">· {selfScore.note}</span>}</p>}</div><input aria-label={'Nilai ' + component.name} className="h-10 rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand disabled:bg-slate-50 disabled:text-slate-500" disabled={!editable || component.is_auto_calculated} max="100" min="0" onChange={(event) => { const scores = [...form.data.scores]; scores[index] = { ...scores[index], raw_score: event.target.value }; form.setData('scores', scores); }} placeholder={component.is_auto_calculated ? 'Otomatis' : '0-100'} type="number" value={form.data.scores[index].raw_score} />{scoreError(index) && <p className="text-xs text-red-600 sm:col-start-2">{scoreError(index)}</p>}</div>; })}</div>
                    {form.errors.scores && <p className="mt-3 text-sm text-red-600">{form.errors.scores}</p>}
                    {form.errors.period_id && <p className="mt-3 text-sm text-red-600">{form.errors.period_id}</p>}
                    <textarea className="mt-5 min-h-24 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand disabled:bg-slate-50" disabled={!editable} onChange={(event) => form.setData('notes', event.target.value)} placeholder="Catatan tambahan (opsional)" value={form.data.notes} />
                    <div className="mt-5 flex flex-wrap justify-end gap-3">{editable && period && <Button disabled={form.processing} type="submit" variant="secondary">Simpan Draf</Button>}{transition && <Button disabled={form.processing} onClick={move} type="button">{transition.icon}{transition.label}</Button>}</div>
                </form>
                <aside className="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="flex items-center gap-2 font-semibold text-slate-900"><Calculator size={18} />Ringkasan Nilai</h2>
                    <p className="mt-5 text-sm text-slate-500">{evaluation ? 'Total tersimpan' : 'Perkiraan sementara'}</p>
                    <p className="mt-1 text-3xl font-bold text-slate-900">{evaluation ? Number(evaluation.total_score).toFixed(2) : total.toFixed(2)}</p>
                    <p className="mt-4 text-xs leading-5 text-slate-500">Nilai akhir dihitung ulang secara otomatis oleh sistem setiap kali draf disimpan.</p>
                    {period && (
                        <div className="mt-4 border-t border-slate-100 pt-4">
                            <p className="text-xs font-semibold text-slate-500">PERIODE</p>
                            <p className="mt-1 text-sm font-medium text-slate-800">{period.name}</p>
                            <p className="text-xs text-slate-500">{period.start_date.slice(0, 10)} s/d {period.end_date.slice(0, 10)}</p>
                        </div>
                    )}
                    {selfAssessment && (
                        <div className="mt-4 border-t border-slate-100 pt-4">
                            <p className="text-xs font-semibold text-slate-500">PENILAIAN DIRI</p>
                            <p className="mt-1 text-2xl font-bold text-blue-600">{Number(selfAssessment.total_score).toFixed(2)}</p>
                            <p className="mt-1 text-xs text-slate-500">Skor penilaian diri oleh pejuang</p>
                            {selfAssessment.notes && (
                                <p className="mt-2 rounded bg-blue-50 p-2 text-xs text-blue-700">{selfAssessment.notes}</p>
                            )}
                        </div>
                    )}
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}

function transitionFor(evaluation: Evaluation | null, abilities: Abilities) {
    if (!evaluation) return null;
    if (evaluation.status === 'DRAFT' && abilities.submit) return { status: 'SUBMITTED', label: 'Ajukan Penilaian', confirmation: '', icon: <Send size={16} /> };
    if (evaluation.status === 'SUBMITTED' && abilities.approve) return { status: 'APPROVED', label: 'Setujui Penilaian', confirmation: '', icon: <CheckCircle2 size={16} /> };
    if (evaluation.status === 'APPROVED' && abilities.finalize) return { status: 'FINALIZED', label: 'Finalisasi', confirmation: 'Setelah difinalisasi, nilai tidak dapat diubah lagi. Lanjutkan?', icon: <CheckCircle2 size={16} /> };
    if (evaluation.status === 'FINALIZED' && abilities.close) return { status: 'CLOSED', label: 'Tutup Penilaian', confirmation: 'Penilaian yang ditutup tidak dapat dibuka kembali. Lanjutkan?', icon: <LockKeyhole size={16} /> };
    return null;
}
const statusLabels = { DRAFT: 'Draf', SUBMITTED: 'Diajukan', APPROVED: 'Disetujui', FINALIZED: 'Final', CLOSED: 'Ditutup' };
const statusStyles = { DRAFT: 'bg-slate-100 text-slate-700', SUBMITTED: 'bg-blue-50 text-blue-700', APPROVED: 'bg-amber-50 text-amber-800', FINALIZED: 'bg-brand-subtle text-brand-dark', CLOSED: 'bg-slate-900 text-white' };