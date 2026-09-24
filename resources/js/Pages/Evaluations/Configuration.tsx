import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { BellRing, CheckCircle2, ClipboardList, Pencil, Plus, SlidersHorizontal, Target, X } from 'lucide-react';
import { useState } from 'react';

type Period = { id: number; name: string; status: 'DRAFT' | 'ACTIVE' | 'REVIEW' | 'FINALIZED' | 'CLOSED'; start_date: string; end_date: string; is_active: boolean };
type Rule = { rule_type: string; min_value: string | number; max_value: string | number | null; score_value: string | number };
type Component = { id: number; code: string; name: string; description?: string; default_weight: string; measurement_type: string; scoring_method: string; is_auto_calculated: boolean; is_active: boolean; sort_order: number; rules: Rule[] };
type Criterion = { id: number; name: string; min_score: string; max_score: string; color_semantic: string; sort_order: number };
type AttentionRule = { id: number; name: string; rule_type: string; minimum_severity: string; operator: string; threshold: string; is_active: boolean };

export default function Configuration({
    periods,
    components,
    criteria,
    attentionRules,
    canManage,
    canManageSettings,
}: {
    periods: Period[];
    components: Component[];
    criteria: Criterion[];
    attentionRules: AttentionRule[];
    canManage: boolean;
    canManageSettings: boolean;
}) {
    const [editingPeriod, setEditingPeriod] = useState<Period | null>(null);
    const [editingComponent, setEditingComponent] = useState<Component | null>(null);
    const [editingCriterion, setEditingCriterion] = useState<Criterion | null>(null);
    const [editingAttention, setEditingAttention] = useState<AttentionRule | null>(null);

    return (
        <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Penilaian</p><h1 className="text-[26px] font-bold text-slate-900">Konfigurasi Penilaian</h1></div>}>
            <Head title="Konfigurasi Penilaian" />
            <div className="mx-auto max-w-[1400px] space-y-6 p-4 sm:p-6 lg:p-8">
                <Panel title="Periode Penilaian" icon={<ClipboardList size={18} />}>
                    <div className="divide-y divide-slate-100">{periods.length ? periods.map((period) => <div className="flex flex-col justify-between gap-3 py-3 text-sm sm:flex-row sm:items-center" key={period.id}><div><p className="font-medium text-slate-800">{period.name}</p><p className="text-slate-500">{dateOnly(period.start_date)} sampai {dateOnly(period.end_date)}</p></div><div className="flex flex-wrap items-center gap-2"><span className={'rounded-full px-2 py-1 text-xs font-semibold ' + periodStyles[period.status]}>{periodLabels[period.status]}</span>{canManage && period.status === 'DRAFT' && <IconButton label="Ubah periode" onClick={() => setEditingPeriod(period)}><Pencil size={15} /></IconButton>}{canManage && nextPeriod[period.status] && <Button type="button" variant="secondary" onClick={() => transitionPeriod(period)}><CheckCircle2 size={15} />{periodAction[period.status]}</Button>}</div></div>) : <Empty label="periode penilaian" />}</div>
                    {canManage && <PeriodForm initial={editingPeriod} key={editingPeriod?.id ?? 'new-period'} onDone={() => setEditingPeriod(null)} />}
                </Panel>
                <div className="grid gap-6 xl:grid-cols-2">
                    <Panel title="Komponen dan Bobot" icon={<SlidersHorizontal size={18} />}>
                        <div className="space-y-3">{components.length ? components.map((component) => <div className="rounded-lg bg-slate-50 p-3" key={component.id}><div className="flex items-start justify-between gap-3 text-sm"><div><span className="font-semibold text-slate-800">{component.name}</span><p className="mt-1 text-xs text-slate-500">{component.measurement_type === 'TENURE' ? component.rules.length + ' aturan masa kerja' : 'Input manual'} · {component.is_active ? 'Aktif' : 'Nonaktif'}</p></div><div className="flex items-center gap-2"><span className="text-green-700">{component.default_weight}%</span>{canManage && <IconButton label="Ubah komponen" onClick={() => setEditingComponent(component)}><Pencil size={15} /></IconButton>}</div></div></div>) : <Empty label="komponen penilaian" />}</div>
                        {canManage && <ComponentForm initial={editingComponent} key={editingComponent?.id ?? 'new-component'} onDone={() => setEditingComponent(null)} />}
                    </Panel>
                    <Panel title="Kriteria Hasil" icon={<Target size={18} />}>
                        <div className="space-y-3">{criteria.length ? criteria.map((criterion) => <div className="flex items-center justify-between gap-3 border-b border-slate-100 pb-3 text-sm" key={criterion.id}><div><span className="font-medium text-slate-800">{criterion.name}</span><p className="text-xs text-slate-500">{criterion.min_score}–{criterion.max_score}</p></div>{canManage && <IconButton label="Ubah kriteria" onClick={() => setEditingCriterion(criterion)}><Pencil size={15} /></IconButton>}</div>) : <Empty label="kriteria hasil" />}</div>
                        {canManage && <CriterionForm initial={editingCriterion} key={editingCriterion?.id ?? 'new-criterion'} onDone={() => setEditingCriterion(null)} />}
                    </Panel>
                </div>
                <Panel title="Aturan Perlu Perhatian" icon={<BellRing size={18} />}>
                    <div className="grid gap-3 md:grid-cols-2">{attentionRules.length ? attentionRules.map((rule) => <div className="flex items-start justify-between gap-3 rounded-lg border border-slate-200 p-4" key={rule.id}><div><p className="text-sm font-semibold text-slate-800">{rule.name}</p><p className="mt-1 text-xs text-slate-500">{attentionDescriptions[rule.rule_type] || rule.rule_type} {rule.operator} {rule.threshold} · {rule.is_active ? 'Aktif' : 'Nonaktif'}</p></div>{canManageSettings && <IconButton label="Ubah aturan perhatian" onClick={() => setEditingAttention(rule)}><Pencil size={15} /></IconButton>}</div>) : <Empty label="aturan perhatian" />}</div>
                    {canManageSettings && <AttentionRuleForm initial={editingAttention} key={editingAttention?.id ?? 'new-attention'} onDone={() => setEditingAttention(null)} />}
                </Panel>
            </div>
        </AuthenticatedLayout>
    );
}

function PeriodForm({ initial, onDone }: { initial: Period | null; onDone: () => void }) {
    const form = useForm({ name: initial?.name ?? '', start_date: dateOnly(initial?.start_date), end_date: dateOnly(initial?.end_date), status: 'DRAFT', is_active: initial?.is_active ?? false });
    const submit = () => initial ? form.put(route('evaluation-periods.update', initial.id), { preserveScroll: true, onSuccess: onDone }) : form.post(route('evaluation-periods.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    return <Editor title={initial ? 'Ubah periode' : 'Tambah periode'} editing={Boolean(initial)} onCancel={onDone}><form className="grid gap-3 sm:grid-cols-2" onSubmit={(event) => { event.preventDefault(); submit(); }}><Input label="Nama periode" value={form.data.name} error={form.errors.name} onChange={(value) => form.setData('name', value)} /><Input label="Mulai" type="date" value={form.data.start_date} error={form.errors.start_date} onChange={(value) => form.setData('start_date', value)} /><Input label="Selesai" type="date" value={form.data.end_date} error={form.errors.end_date} onChange={(value) => form.setData('end_date', value)} /><SubmitButton editing={Boolean(initial)} processing={form.processing} /></form></Editor>;
}

function ComponentForm({ initial, onDone }: { initial: Component | null; onDone: () => void }) {
    const form = useForm({
        code: initial?.code ?? '', name: initial?.name ?? '', description: initial?.description ?? '',
        default_weight: initial?.default_weight ?? '', measurement_type: initial?.measurement_type ?? 'MANUAL',
        scoring_method: initial?.scoring_method ?? 'PERCENTAGE', is_auto_calculated: initial?.is_auto_calculated ?? false,
        is_active: initial?.is_active ?? true, sort_order: initial?.sort_order ?? 0,
        rules: (initial?.rules ?? []).map((rule) => ({ rule_type: 'TENURE_MONTHS', min_value: String(rule.min_value), max_value: rule.max_value === null ? '' : String(rule.max_value), score_value: String(rule.score_value) })),
    });
    const tenure = form.data.measurement_type === 'TENURE';
    const addRule = () => form.setData('rules', [...form.data.rules, { rule_type: 'TENURE_MONTHS', min_value: '', max_value: '', score_value: '' }]);
    const setRule = (index: number, key: 'min_value' | 'max_value' | 'score_value', value: string) => form.setData('rules', form.data.rules.map((rule, item) => item === index ? { ...rule, [key]: value } : rule));
    const ruleError = (index: number, field: 'min_value' | 'max_value' | 'score_value') => Object.entries(form.errors)
        .find(([key]) => key === 'rules.' + index + '.' + field)?.[1];
    const submit = () => initial ? form.put(route('evaluation-components.update', initial.id), { preserveScroll: true, onSuccess: onDone }) : form.post(route('evaluation-components.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    return <Editor title={initial ? 'Ubah komponen' : 'Tambah komponen'} editing={Boolean(initial)} onCancel={onDone}><form className="grid gap-3 sm:grid-cols-2" onSubmit={(event) => { event.preventDefault(); submit(); }}><Input label="Kode" value={form.data.code} error={form.errors.code} onChange={(value) => form.setData('code', value)} /><Input label="Nama komponen" value={form.data.name} error={form.errors.name} onChange={(value) => form.setData('name', value)} /><Input label="Bobot (%)" type="number" value={form.data.default_weight} error={form.errors.default_weight} onChange={(value) => form.setData('default_weight', value)} /><label className="text-sm font-medium text-slate-700">Jenis pengukuran<select value={form.data.measurement_type} onChange={(event) => { const value = event.target.value; form.setData('measurement_type', value); form.setData('scoring_method', value === 'TENURE' ? 'RULE' : 'PERCENTAGE'); form.setData('is_auto_calculated', value === 'TENURE'); if (value !== 'TENURE') form.setData('rules', []); }} className={inputClass}><option value="MANUAL">Manual</option><option value="TENURE">Masa kerja otomatis</option></select></label><label className="flex min-h-10 items-center gap-2 text-sm text-slate-700"><input checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} type="checkbox" />Komponen aktif</label>{tenure && <div className="sm:col-span-2 rounded-lg bg-slate-50 p-3"><p className="text-sm font-semibold text-slate-800">Aturan masa kerja (bulan)</p>{form.data.rules.map((rule, index) => <div className="mt-3 grid gap-2 sm:grid-cols-4" key={index}><Input label="Minimum" type="number" value={rule.min_value} error={ruleError(index, 'min_value')} onChange={(value) => setRule(index, 'min_value', value)} /><Input label="Maksimum" type="number" value={rule.max_value} error={ruleError(index, 'max_value')} onChange={(value) => setRule(index, 'max_value', value)} optional /><Input label="Nilai" type="number" value={rule.score_value} error={ruleError(index, 'score_value')} onChange={(value) => setRule(index, 'score_value', value)} /><Button type="button" variant="secondary" onClick={() => form.setData('rules', form.data.rules.filter((_, item) => item !== index))}>Hapus</Button></div>)}<Button className="mt-3" type="button" variant="secondary" onClick={addRule}><Plus size={16} />Tambah Aturan</Button>{form.errors.rules && <p className="mt-2 text-xs text-red-600">{form.errors.rules}</p>}</div>}<SubmitButton editing={Boolean(initial)} processing={form.processing} /></form></Editor>;
}

function CriterionForm({ initial, onDone }: { initial: Criterion | null; onDone: () => void }) {
    const form = useForm({ name: initial?.name ?? '', min_score: initial?.min_score ?? '', max_score: initial?.max_score ?? '', color_semantic: initial?.color_semantic ?? 'success', sort_order: initial?.sort_order ?? 0 });
    const submit = () => initial ? form.put(route('evaluation-criteria.update', initial.id), { preserveScroll: true, onSuccess: onDone }) : form.post(route('evaluation-criteria.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    return <Editor title={initial ? 'Ubah kriteria' : 'Tambah kriteria'} editing={Boolean(initial)} onCancel={onDone}><form className="grid gap-3 sm:grid-cols-2" onSubmit={(event) => { event.preventDefault(); submit(); }}><Input label="Nama kriteria" value={form.data.name} error={form.errors.name} onChange={(value) => form.setData('name', value)} /><Input label="Nilai minimum" type="number" value={form.data.min_score} error={form.errors.min_score} onChange={(value) => form.setData('min_score', value)} /><Input label="Nilai maksimum" type="number" value={form.data.max_score} error={form.errors.max_score} onChange={(value) => form.setData('max_score', value)} /><label className="text-sm font-medium text-slate-700">Warna status<select value={form.data.color_semantic} onChange={(event) => form.setData('color_semantic', event.target.value)} className={inputClass}><option value="success">Sukses</option><option value="warning">Perlu perhatian</option><option value="danger">Bahaya</option><option value="info">Informasi</option><option value="neutral">Netral</option></select></label><SubmitButton editing={Boolean(initial)} processing={form.processing} /></form></Editor>;
}

function AttentionRuleForm({ initial, onDone }: { initial: AttentionRule | null; onDone: () => void }) {
    const form = useForm({ name: initial?.name ?? '', rule_type: initial?.rule_type ?? 'INCIDENT_SEVERITY_COUNT', minimum_severity: initial?.minimum_severity ?? 'HIGH', operator: initial?.operator ?? '>=', threshold: initial?.threshold ?? '1', is_active: initial?.is_active ?? true });
    const submit = () => initial ? form.put(route('attention-rules.update', initial.id), { preserveScroll: true, onSuccess: onDone }) : form.post(route('attention-rules.store'), { preserveScroll: true, onSuccess: () => form.reset() });
    return <Editor title={initial ? 'Ubah aturan perhatian' : 'Tambah aturan perhatian'} editing={Boolean(initial)} onCancel={onDone}><form className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" onSubmit={(event) => { event.preventDefault(); submit(); }}><Input label="Nama aturan" value={form.data.name} error={form.errors.name} onChange={(value) => form.setData('name', value)} /><label className="text-sm font-medium text-slate-700">Sumber aturan<select className={inputClass} value={form.data.rule_type} onChange={(event) => form.setData('rule_type', event.target.value)}><option value="INCIDENT_SEVERITY_COUNT">Jumlah catatan masalah</option><option value="EVALUATION_BELOW">Nilai total</option><option value="ATTENDANCE_BELOW">Nilai absensi</option></select></label><label className="text-sm font-medium text-slate-700">Tingkat minimum<select className={inputClass} value={form.data.minimum_severity} onChange={(event) => form.setData('minimum_severity', event.target.value)}><option value="LOW">Rendah</option><option value="MEDIUM">Sedang</option><option value="HIGH">Tinggi</option><option value="CRITICAL">Kritis</option></select></label><label className="text-sm font-medium text-slate-700">Operator<select className={inputClass} value={form.data.operator} onChange={(event) => form.setData('operator', event.target.value)}><option value="<">Kurang dari</option><option value="<=">Kurang dari atau sama</option><option value=">">Lebih dari</option><option value=">=">Lebih dari atau sama</option><option value="=">Sama dengan</option></select></label><Input label="Batas nilai" type="number" value={form.data.threshold} error={form.errors.threshold} onChange={(value) => form.setData('threshold', value)} /><label className="flex min-h-10 items-center gap-2 text-sm text-slate-700"><input checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} type="checkbox" />Aturan aktif</label><SubmitButton editing={Boolean(initial)} processing={form.processing} /></form></Editor>;
}

function transitionPeriod(period: Period) {
    const next = nextPeriod[period.status];
    if (!next || !window.confirm('Ubah status periode menjadi ' + periodLabels[next] + '?')) return;
    router.post(route('evaluation-periods.transition', period.id), { status: next }, { preserveScroll: true });
}
function Panel({ title, icon, children }: { title: string; icon: React.ReactNode; children: React.ReactNode }) { return <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="flex items-center gap-2 font-semibold text-slate-900">{icon}{title}</h2><div className="mt-4">{children}</div></section>; }
function Editor({ title, editing, onCancel, children }: { title: string; editing: boolean; onCancel: () => void; children: React.ReactNode }) { return <div className="mt-5 border-t border-slate-100 pt-5"><div className="mb-3 flex items-center justify-between"><p className="text-sm font-semibold text-slate-800">{title}</p>{editing && <IconButton label="Batal mengubah" onClick={onCancel}><X size={15} /></IconButton>}</div>{children}</div>; }
function IconButton({ label, onClick, children }: { label: string; onClick: () => void; children: React.ReactNode }) { return <button aria-label={label} className="flex size-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-white" onClick={onClick} type="button">{children}</button>; }
function SubmitButton({ editing, processing }: { editing: boolean; processing: boolean }) { return <div className="flex items-end"><Button disabled={processing} type="submit">{editing ? <Pencil size={16} /> : <Plus size={16} />}{processing ? 'Menyimpan...' : editing ? 'Simpan Perubahan' : 'Tambah'}</Button></div>; }
function Empty({ label }: { label: string }) { return <p className="py-6 text-sm text-slate-500">Belum ada {label}.</p>; }
function Input({ label, value, error, onChange, type = 'text', optional = false }: { label: string; value: string | number; error?: string; onChange: (value: string) => void; type?: string; optional?: boolean }) { return <label className="text-sm font-medium text-slate-700">{label}<input className={inputClass} type={type} value={value} onChange={(event) => onChange(event.target.value)} required={!optional} />{error && <span className="mt-1 block text-xs text-red-600">{error}</span>}</label>; }
function dateOnly(value?: string) { return value ? value.slice(0, 10) : ''; }
const inputClass = 'mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600';
const periodLabels = { DRAFT: 'Draf', ACTIVE: 'Aktif', REVIEW: 'Ditinjau', FINALIZED: 'Final', CLOSED: 'Ditutup' };
const periodStyles = { DRAFT: 'bg-slate-100 text-slate-700', ACTIVE: 'bg-green-50 text-green-800', REVIEW: 'bg-amber-50 text-amber-800', FINALIZED: 'bg-blue-50 text-blue-700', CLOSED: 'bg-slate-900 text-white' };
const nextPeriod: Record<Period['status'], Period['status'] | null> = { DRAFT: 'ACTIVE', ACTIVE: 'REVIEW', REVIEW: 'FINALIZED', FINALIZED: 'CLOSED', CLOSED: null };
const periodAction: Record<Period['status'], string> = { DRAFT: 'Aktifkan', ACTIVE: 'Mulai Peninjauan', REVIEW: 'Finalisasi', FINALIZED: 'Tutup', CLOSED: '' };
const attentionDescriptions: Record<string, string> = { INCIDENT_SEVERITY_COUNT: 'Jumlah catatan masalah', EVALUATION_BELOW: 'Nilai total', ATTENDANCE_BELOW: 'Nilai absensi' };
