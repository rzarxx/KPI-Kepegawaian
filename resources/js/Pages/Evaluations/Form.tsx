import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Calculator, CheckCircle2, LockKeyhole, Send } from 'lucide-react';

type Score = { component_id: number; raw_score: string; note?: string };
type Component = { id: number; name: string; default_weight: string; is_auto_calculated: boolean };
type Evaluation = { id: number; status: 'DRAFT' | 'SUBMITTED' | 'APPROVED' | 'FINALIZED' | 'CLOSED'; notes?: string; total_score: string; scores: Score[] };
type Abilities = { edit: boolean; submit: boolean; approve: boolean; finalize: boolean; close: boolean };

export default function Form({
    employee,
    period,
    components,
    evaluation,
    abilities,
}: {
    employee: { id: number; full_name: string; employee_number: string };
    period: { id: number; name: string; start_date: string; end_date: string };
    components: Component[];
    evaluation: Evaluation | null;
    abilities: Abilities;
}) {
    const existing = new Map((evaluation?.scores ?? []).map((score) => [score.component_id, score]));
    const form = useForm({
        notes: evaluation?.notes ?? '',
        scores: components.map((component) => existing.get(component.id) ?? { component_id: component.id, raw_score: '', note: '' }),
    });
    const total = form.data.scores.reduce((sum, score, index) => sum + (Number(score.raw_score) || 0) * Number(components[index].default_weight) / 100, 0);
    const editable = !evaluation || abilities.edit;
    const transition = transitionFor(evaluation, abilities);
    const scoreError = (index: number) => Object.entries(form.errors)
        .find(([field]) => field === 'scores.' + index + '.raw_score')?.[1];
    const save = () => form.post(route('evaluations.store', [employee.id, period.id]), { preserveScroll: true });
    const move = () => {
        if (!evaluation || !transition) return;
        if (['FINALIZED', 'CLOSED'].includes(transition.status) && !window.confirm(transition.confirmation)) return;
        router.post(route('evaluations.transition', evaluation.id), { status: transition.status }, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header={<div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><p className="text-sm text-slate-500">Penilaian</p><h1 className="text-[26px] font-bold text-slate-900">Penilaian Karyawan</h1></div>{evaluation && <span className={'w-fit rounded-full px-3 py-1.5 text-sm font-semibold ' + statusStyles[evaluation.status]}>{statusLabels[evaluation.status]}</span>}</div>}>
            <Head title="Penilaian Karyawan" />
            <div className="mx-auto grid max-w-[1200px] gap-6 p-4 sm:p-6 lg:grid-cols-[1fr_280px] lg:p-8">
                <form className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" onSubmit={(event) => { event.preventDefault(); save(); }}>
                    <h2 className="font-semibold text-slate-900">{employee.full_name}</h2>
                    <p className="mt-1 text-sm text-slate-500">{employee.employee_number} · {period.name}</p>
                    {!editable && <div className="mt-5 flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600"><LockKeyhole className="mt-0.5 shrink-0" size={17} /><p>Nilai tidak dapat diubah setelah penilaian diajukan. Gunakan tindakan workflow yang tersedia untuk melanjutkan proses.</p></div>}
                    <div className="mt-6 space-y-4">{components.map((component, index) => <div className="grid gap-3 border-b border-slate-100 pb-4 sm:grid-cols-[1fr_150px]" key={component.id}><div><p className="font-medium text-slate-800">{component.name}</p><p className="text-xs text-slate-500">Bobot {component.default_weight}% {component.is_auto_calculated ? '· dihitung otomatis' : ''}</p></div><input aria-label={'Nilai ' + component.name} className="h-10 rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600 disabled:bg-slate-50 disabled:text-slate-500" disabled={!editable || component.is_auto_calculated} max="100" min="0" onChange={(event) => { const scores = [...form.data.scores]; scores[index] = { ...scores[index], raw_score: event.target.value }; form.setData('scores', scores); }} placeholder={component.is_auto_calculated ? 'Otomatis' : '0-100'} type="number" value={form.data.scores[index].raw_score} />{scoreError(index) && <p className="text-xs text-red-600 sm:col-start-2">{scoreError(index)}</p>}</div>)}</div>
                    {form.errors.scores && <p className="mt-3 text-sm text-red-600">{form.errors.scores}</p>}
                    <textarea className="mt-5 min-h-24 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600 disabled:bg-slate-50" disabled={!editable} onChange={(event) => form.setData('notes', event.target.value)} placeholder="Catatan penilaian (opsional)" value={form.data.notes} />
                    <div className="mt-5 flex flex-wrap justify-end gap-3">{editable && <Button disabled={form.processing} type="submit" variant="secondary">Simpan Draf</Button>}{transition && <Button disabled={form.processing} onClick={move} type="button">{transition.icon}{transition.label}</Button>}</div>
                </form>
                <aside className="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="flex items-center gap-2 font-semibold text-slate-900"><Calculator size={18} />Ringkasan</h2>
                    <p className="mt-5 text-sm text-slate-500">{evaluation ? 'Total tersimpan' : 'Total sementara manual'}</p>
                    <p className="mt-1 text-3xl font-bold text-slate-900">{evaluation ? Number(evaluation.total_score).toFixed(2) : total.toFixed(2)}</p>
                    <p className="mt-4 text-xs leading-5 text-slate-500">Total akhir, aturan otomatis, dan kriteria selalu dihitung ulang oleh server saat draf disimpan.</p>
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}

function transitionFor(evaluation: Evaluation | null, abilities: Abilities) {
    if (!evaluation) return null;
    if (evaluation.status === 'DRAFT' && abilities.submit) return { status: 'SUBMITTED', label: 'Ajukan Penilaian', confirmation: '', icon: <Send size={16} /> };
    if (evaluation.status === 'SUBMITTED' && abilities.approve) return { status: 'APPROVED', label: 'Setujui Penilaian', confirmation: '', icon: <CheckCircle2 size={16} /> };
    if (evaluation.status === 'APPROVED' && abilities.finalize) return { status: 'FINALIZED', label: 'Finalisasi Penilaian', confirmation: 'Finalisasi akan mengunci hasil penilaian. Lanjutkan?', icon: <CheckCircle2 size={16} /> };
    if (evaluation.status === 'FINALIZED' && abilities.close) return { status: 'CLOSED', label: 'Tutup Penilaian', confirmation: 'Penilaian yang ditutup tidak dapat diproses kembali. Lanjutkan?', icon: <LockKeyhole size={16} /> };
    return null;
}
const statusLabels = { DRAFT: 'Draf', SUBMITTED: 'Diajukan', APPROVED: 'Disetujui', FINALIZED: 'Final', CLOSED: 'Ditutup' };
const statusStyles = { DRAFT: 'bg-slate-100 text-slate-700', SUBMITTED: 'bg-blue-50 text-blue-700', APPROVED: 'bg-amber-50 text-amber-800', FINALIZED: 'bg-green-50 text-green-800', CLOSED: 'bg-slate-900 text-white' };
