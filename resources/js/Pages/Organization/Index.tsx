import { Button } from '@/Components/ui/button';
import ConfirmDeleteDialog from '@/Components/ConfirmDeleteDialog';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Archive, BriefcaseBusiness, Building2, GitBranch, Layers3, Pencil, Plus, Trash2 } from 'lucide-react';
import { ReactNode, useState } from 'react';

type Unit = { id: number; code: string; name: string; is_active: boolean; branch_id?: number | null; division_id?: number | null; level?: string | null };
type Form = { code: string; name: string; is_active: boolean; branch_id: string; division_id: string; level: string };
type SectionProps = { title: string; icon: ReactNode; type: string; items: Unit[]; choices?: Unit[]; choiceLabel?: string; hasLevel?: boolean; canManage: boolean };

export default function Organization({ branches, divisions, subDivisions, positions, canManage }: { branches: Unit[]; divisions: Unit[]; subDivisions: Unit[]; positions: Unit[]; canManage: boolean }) {
    return <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Pengaturan</p><h1 className="text-[26px] font-bold text-slate-900">Struktur Organisasi</h1></div>}><Head title="Struktur Organisasi" /><div className="mx-auto max-w-[1400px] space-y-6 p-4 sm:p-6 lg:p-8"><p className="max-w-3xl text-sm leading-6 text-slate-600">Kelola cabang, divisi, sub divisi, dan jabatan. Penonaktifan mempertahankan riwayat organisasi; data tidak dihapus.</p><div className="grid gap-6 xl:grid-cols-2"><Section title="Cabang" icon={<Building2 size={18} />} type="cabang" items={branches} canManage={canManage} /><Section title="Divisi" icon={<Layers3 size={18} />} type="divisi" items={divisions} choices={branches} choiceLabel="Cabang" canManage={canManage} /><Section title="Sub Divisi" icon={<GitBranch size={18} />} type="sub-divisi" items={subDivisions} choices={divisions} choiceLabel="Divisi" canManage={canManage} /><Section title="Jabatan" icon={<BriefcaseBusiness size={18} />} type="jabatan" items={positions} hasLevel canManage={canManage} /></div></div></AuthenticatedLayout>;
}

function Section({ title, icon, type, items, choices = [], choiceLabel, hasLevel, canManage }: SectionProps) {
    const form = useForm<Form>({ code: '', name: '', is_active: true, branch_id: '', division_id: '', level: '' });
    const [editing, setEditing] = useState<Unit | null>(null);
    const [pendingDelete, setPendingDelete] = useState<Unit | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [showArchived, setShowArchived] = useState(false);
    const scopeKey = type === 'divisi' ? 'branch_id' : 'division_id';
    const visibleItems = showArchived ? items.filter((item) => !item.is_active) : items.filter((item) => item.is_active);
    const submit = (event: React.FormEvent) => { event.preventDefault(); const options = { onSuccess: () => { form.reset(); setEditing(null); } }; if (editing) { form.put(route('organization.update', [type, editing.id]), options); return; } form.post(route('organization.store', type), options); };
    const edit = (unit: Unit) => { setEditing(unit); form.setData({ code: unit.code, name: unit.name, is_active: unit.is_active, branch_id: String(unit.branch_id ?? ''), division_id: String(unit.division_id ?? ''), level: unit.level ?? '' }); };
    const destroy = (verificationCode?: string) => {
        if (!pendingDelete) return;

        setDeleting(true);
        router.delete(route('organization.destroy', [type, pendingDelete.id]), {
            data: { verification_code: verificationCode },
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    };

    return (
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between gap-3">
                <h2 className="flex items-center gap-2 text-base font-semibold text-slate-900">{icon}{title}</h2>
                <Button type="button" variant="secondary" onClick={() => setShowArchived((current) => !current)}>
                    <Archive size={16} />
                    {showArchived ? 'Sembunyikan Arsip' : 'Lihat Arsip'}
                </Button>
            </div>
            <div className="mt-4 divide-y divide-slate-100">
                {visibleItems.length ? visibleItems.map((item) => (
                    <div className="flex items-center justify-between gap-3 py-3 text-sm" key={item.id}>
                        <div>
                            <p className="font-medium text-slate-800">{item.name}</p>
                            <p className="text-xs text-slate-500">{item.code}{item.level ? ` - ${item.level}` : ''}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className={item.is_active ? 'rounded-full bg-brand-subtle px-2 py-1 text-xs font-medium text-brand-dark' : 'rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600'}>
                                {item.is_active ? 'Aktif' : 'Diarsipkan'}
                            </span>
                            {canManage && item.is_active && <Button type="button" variant="ghost" onClick={() => edit(item)}><Pencil size={16} />Ubah</Button>}
                            {canManage && item.is_active && <button className="flex size-8 items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50" onClick={() => setPendingDelete(item)} title="Hapus" type="button"><Trash2 size={14} /></button>}
                        </div>
                    </div>
                )) : <p className="py-6 text-sm text-slate-500">{showArchived ? `Belum ada arsip ${title.toLowerCase()}.` : `Belum ada data aktif ${title.toLowerCase()}.`}</p>}
            </div>
            {showArchived && <p className="mt-3 text-xs leading-5 text-slate-500">Arsip hanya-baca untuk menjaga riwayat penempatan dan tidak dapat dihapus permanen.</p>}
            {canManage && <form onSubmit={submit} className="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2">
                <p className="sm:col-span-2 text-sm font-semibold text-slate-800">{editing ? `Ubah ${title.toLowerCase()}` : `Tambah ${title.toLowerCase()}`}</p>
                <Input label="Kode" value={form.data.code} error={form.errors.code} onChange={value => form.setData('code', value)} />
                <Input label="Nama" value={form.data.name} error={form.errors.name} onChange={value => form.setData('name', value)} />
                {choiceLabel && <Select label={choiceLabel} value={form.data[scopeKey]} error={form.errors[scopeKey]} onChange={value => form.setData(scopeKey, value)} options={choices} />}
                {hasLevel && <Input label="Level" value={form.data.level} error={form.errors.level} onChange={value => form.setData('level', value)} />}
                <label className="flex min-h-10 items-center gap-2 text-sm text-slate-700"><input type="checkbox" checked={form.data.is_active} onChange={event => form.setData('is_active', event.target.checked)} />Aktif</label>
                <div className="flex items-end gap-2">
                    <Button disabled={form.processing} type="submit"><Plus size={16} />{editing ? 'Simpan' : 'Tambah'}</Button>
                    {editing && <Button type="button" variant="secondary" onClick={() => { setEditing(null); form.reset(); }}>Batal</Button>}
                </div>
            </form>}
            {pendingDelete && <ConfirmDeleteDialog key={pendingDelete.id} open message={`Hapus ${title.toLowerCase()} "${pendingDelete.name}"? Unit yang masih memiliki karyawan, struktur turunan, atau riwayat akan diarsipkan agar data tetap aman.`} verificationCode={pendingDelete.code} processing={deleting} onConfirm={destroy} onCancel={() => setPendingDelete(null)} />}
        </section>
    );
}

function Input({ label, value, error, onChange }: { label: string; value: string; error?: string; onChange: (value: string) => void }) { return <label className="text-sm font-medium text-slate-700">{label}<input className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand" value={value} onChange={event => onChange(event.target.value)} required />{error && <span className="mt-1 block text-xs text-red-600">{error}</span>}</label>; }
function Select({ label, value, error, onChange, options }: { label: string; value: string; error?: string; onChange: (value: string) => void; options: Unit[] }) { return <label className="text-sm font-medium text-slate-700">{label}<select className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand" value={value} onChange={event => onChange(event.target.value)} required><option value="">Pilih {label.toLowerCase()}</option>{options.filter(option => option.is_active).map(option => <option value={option.id} key={option.id}>{option.name}</option>)}</select>{error && <span className="mt-1 block text-xs text-red-600">{error}</span>}</label>; }
