import Modal from '@/Components/Modal';
import SecurePasswordInput from '@/Components/SecurePasswordInput';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { DialogTitle } from '@headlessui/react';
import { Head, router, useForm } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Power, ShieldCheck, Trash2, Users } from 'lucide-react';
import { FormEvent, useRef, useState } from 'react';

type Scope = {
    id?: number;
    label?: string;
    branch_id: string;
    division_id: string;
    sub_division_id: string;
};
type UserScope = Omit<Scope, 'branch_id' | 'division_id' | 'sub_division_id'> & {
    branch_id?: number | null;
    division_id?: number | null;
    sub_division_id?: number | null;
};
type UserRow = { id: number; name: string; email: string; is_active: boolean; roles: string[]; scopes: UserScope[] };
type Option = { id: number | string; name: string; branch_id?: number; division_id?: number };
type AccessForm = { name: string; role: string; scopes: Scope[] };
type CreateForm = AccessForm & { email: string; password: string };

const emptyScope = (): Scope => ({ branch_id: '', division_id: '', sub_division_id: '' });
const singleScopeRoles = ['Branch Head', 'Division Head', 'Sub Division Head'];

export default function Index({
    users,
    roles,
    branches,
    divisions,
    subDivisions,
    canImpersonate,
    canManageRoles,
}: {
    users: UserRow[];
    roles: string[];
    branches: Option[];
    divisions: Option[];
    subDivisions: Option[];
    canImpersonate: boolean;
    canManageRoles: boolean;
}) {
    const create = useForm<CreateForm>({ name: '', email: '', password: '', role: '', scopes: [] });
    const access = useForm<AccessForm>({ name: '', role: '', scopes: [] });
    const impersonation = useForm({ reason: '' });
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [target, setTarget] = useState<UserRow | null>(null);
    const [toggling, setToggling] = useState<number | null>(null);
    const [openMenu, setOpenMenu] = useState<number | null>(null);

    const beginEdit = (user: UserRow) => {
        setEditing(user);
        setOpenMenu(null);
        access.clearErrors();
        access.setData({
            name: user.name,
            role: user.roles[0] ?? '',
            scopes: user.scopes.map((scope) => ({
                branch_id: String(scope.branch_id ?? ''),
                division_id: String(scope.division_id ?? ''),
                sub_division_id: String(scope.sub_division_id ?? ''),
            })),
        });
    };

    const toggleUser = (user: UserRow) => {
        setOpenMenu(null);
        router.post(route('users.toggle', user.id), {}, {
            preserveScroll: true,
            onStart: () => setToggling(user.id),
            onFinish: () => setToggling(null),
        });
    };

    const beginImpersonate = (user: UserRow) => {
        setOpenMenu(null);
        setTarget(user);
        impersonation.reset();
        impersonation.clearErrors();
    };

    return (
        <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Pengaturan</p><h1 className="text-[26px] font-bold text-slate-900">Kelola Pengguna</h1></div>}>
            <Head title="Kelola Pengguna" />
            <div className="mx-auto grid max-w-[1400px] gap-6 p-4 sm:p-6 lg:grid-cols-[1fr_420px] lg:p-8">
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-2 border-b border-slate-100 p-5 font-semibold text-slate-900"><Users size={18} />Daftar Pengguna</div>
                    <div className="divide-y divide-slate-100">
                        {users.length ? users.map((user) => (
                            <div className="flex items-center justify-between gap-3 p-4" key={user.id}>
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <p className="truncate font-medium text-slate-800">{user.name}</p>
                                        <span className={`shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold ${user.is_active ? 'bg-brand-subtle text-brand' : 'bg-slate-100 text-slate-500'}`}>
                                            {user.is_active ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </div>
                                    <p className="mt-0.5 text-sm text-slate-500">{user.email}</p>
                                    <p className="mt-0.5 text-xs text-slate-400">{user.roles.join(', ') || 'Belum memiliki peran'} · {user.scopes.map((scope) => scope.label).join('; ') || 'Seluruh organisasi'}</p>
                                </div>
                                <div className="relative shrink-0">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="size-9 !p-0"
                                        aria-label="Opsi pengguna"
                                        onClick={() => setOpenMenu(openMenu === user.id ? null : user.id)}
                                    >
                                        <MoreHorizontal size={18} />
                                    </Button>
                                    {openMenu === user.id && (
                                        <ActionMenu
                                            user={user}
                                            canManageRoles={canManageRoles}
                                            canImpersonate={canImpersonate}
                                            toggling={toggling === user.id}
                                            onEdit={() => beginEdit(user)}
                                            onToggle={() => toggleUser(user)}
                                            onImpersonate={() => beginImpersonate(user)}
                                            onClose={() => setOpenMenu(null)}
                                        />
                                    )}
                                </div>
                            </div>
                        )) : <div className="p-8 text-center text-sm text-slate-500">Belum ada pengguna terdaftar.</div>}
                    </div>
                </section>
                <aside className="h-fit rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="flex items-center gap-2 font-semibold text-slate-900"><Plus size={18} />Tambah Pengguna Baru</h2>
                    <p className="mt-1 text-sm text-slate-500">Buat akun untuk anggota yang akan menggunakan sistem ini.</p>
                    <form className="mt-5" onSubmit={(event: FormEvent) => { event.preventDefault(); create.post(route('users.store'), { preserveScroll: true, onSuccess: () => create.reset() }); }}>
                        <Field label="Nama lengkap" value={create.data.name} error={create.errors.name} onChange={(value) => create.setData('name', value)} />
                        <Field label="Alamat email" value={create.data.email} error={create.errors.email} onChange={(value) => create.setData('email', value)} type="email" />
                        <div className="mt-4">
                            <SecurePasswordInput
                                id="create-password"
                                label="Kata sandi"
                                name="password"
                                value={create.data.password}
                                autoComplete="new-password"
                                error={create.errors.password}
                                onChange={(value) => create.setData('password', value)}
                            />
                        </div>
                        <Select label="Peran" value={create.data.role} error={create.errors.role} onChange={(value) => changeRole(value, create.data.scopes, (scopes) => create.setData('scopes', scopes), (role) => create.setData('role', role))} options={roles.map((role) => ({ id: role, name: role }))} />
                        <ScopeEditor rows={create.data.scopes} single={singleScopeRoles.includes(create.data.role)} errors={create.errors} onChange={(scopes) => create.setData('scopes', scopes)} branches={branches} divisions={divisions} subDivisions={subDivisions} />
                        <Button className="mt-5 w-full" type="submit" disabled={create.processing}>{create.processing ? 'Menyimpan...' : 'Simpan Pengguna'}</Button>
                    </form>
                </aside>
            </div>
            <EditAccessDialog user={editing} form={access} close={() => setEditing(null)} roles={roles} branches={branches} divisions={divisions} subDivisions={subDivisions} />
            <ImpersonationDialog user={target} form={impersonation} close={() => setTarget(null)} />
        </AuthenticatedLayout>
    );
}

function ActionMenu({ user, canManageRoles, canImpersonate, toggling, onEdit, onToggle, onImpersonate, onClose }: {
    user: UserRow; canManageRoles: boolean; canImpersonate: boolean; toggling: boolean;
    onEdit: () => void; onToggle: () => void; onImpersonate: () => void; onClose: () => void;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const isSuperAdmin = user.roles.includes('Super Admin');

    return (
        <>
            <div className="fixed inset-0 z-40" onClick={onClose} />
            <div ref={ref} className="absolute right-0 top-full z-50 mt-1 w-48 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                {canManageRoles && !isSuperAdmin && (
                    <button type="button" className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50" onClick={onEdit}>
                        <Pencil size={15} />Ubah akses
                    </button>
                )}
                {!isSuperAdmin && (
                    <button type="button" disabled={toggling} className={`flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm ${user.is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-brand hover:bg-brand-subtle'}`} onClick={onToggle}>
                        <Power size={15} />{user.is_active ? 'Nonaktifkan' : 'Aktifkan kembali'}
                    </button>
                )}
                {isSuperAdmin && (
                    <p className="px-3 py-2 text-xs text-slate-400 italic">Akun Super Admin tidak dapat diubah.</p>
                )}
                {canImpersonate && user.is_active && !isSuperAdmin && (
                    <button type="button" className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-blue-700 hover:bg-blue-50" onClick={onImpersonate}>
                        <ShieldCheck size={15} />Masuk sementara
                    </button>
                )}
            </div>
        </>
    );
}

function EditAccessDialog({ user, form, close, roles, branches, divisions, subDivisions }: {
    user: UserRow | null; form: ReturnType<typeof useForm<AccessForm>>; close: () => void;
    roles: string[]; branches: Option[]; divisions: Option[]; subDivisions: Option[];
}) {
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (user) form.put(route('users.update', user.id), { preserveScroll: true, onSuccess: close });
    };
    return (
        <Modal show={user !== null} maxWidth="lg" onClose={close}>
            <form className="p-6" onSubmit={submit}>
                <DialogTitle className="text-lg font-semibold text-slate-900">Ubah Akses — {user?.name}</DialogTitle>
                <p className="mt-2 text-sm text-slate-500">Sesuaikan peran dan cakupan organisasi pengguna ini.</p>
                <Field label="Nama" value={form.data.name} error={form.errors.name} onChange={(value) => form.setData('name', value)} />
                <Select label="Peran" value={form.data.role} error={form.errors.role} onChange={(value) => changeRole(value, form.data.scopes, (scopes) => form.setData('scopes', scopes), (role) => form.setData('role', role))} options={roles.map((role) => ({ id: role, name: role }))} />
                <ScopeEditor rows={form.data.scopes} single={singleScopeRoles.includes(form.data.role)} errors={form.errors} onChange={(scopes) => form.setData('scopes', scopes)} branches={branches} divisions={divisions} subDivisions={subDivisions} />
                <div className="mt-6 flex justify-end gap-3"><Button type="button" variant="secondary" onClick={close}>Batal</Button><Button disabled={form.processing} type="submit">{form.processing ? 'Menyimpan...' : 'Simpan Perubahan'}</Button></div>
            </form>
        </Modal>
    );
}

function ScopeEditor({ rows, single, errors, onChange, branches, divisions, subDivisions }: {
    rows: Scope[]; single: boolean; errors: Record<string, string>; onChange: (scopes: Scope[]) => void;
    branches: Option[]; divisions: Option[]; subDivisions: Option[];
}) {
    const update = (index: number, patch: Partial<Scope>) => onChange(rows.map((scope, idx) => idx === index ? { ...scope, ...patch } : scope));
    return (
        <div className="mt-4">
            <div className="flex items-center justify-between">
                <p className="text-sm font-semibold text-slate-700">Cakupan Organisasi</p>
                {!single && <Button type="button" variant="secondary" onClick={() => onChange([...rows, emptyScope()])}><Plus size={15} />Tambah</Button>}
            </div>
            {errors.scopes && <p className="mt-2 text-xs text-red-600">{errors.scopes}</p>}
            <div className="mt-3 space-y-3">
                {rows.map((scope, index) => {
                    const divisionOptions = divisions.filter((item) => !scope.branch_id || item.branch_id === Number(scope.branch_id));
                    const subDivisionOptions = subDivisions.filter((item) => !scope.division_id || item.division_id === Number(scope.division_id));
                    return (
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-3" key={index}>
                            <div className="grid gap-3 sm:grid-cols-3">
                                <Select label="Cabang" value={scope.branch_id} error={errors[`scopes.${index}.branch_id`]} onChange={(value) => update(index, { branch_id: value, division_id: '', sub_division_id: '' })} options={branches} compact />
                                <Select label="Divisi" value={scope.division_id} error={errors[`scopes.${index}.division_id`]} onChange={(value) => update(index, { division_id: value, sub_division_id: '' })} options={divisionOptions} compact />
                                <Select label="Sub Divisi" value={scope.sub_division_id} error={errors[`scopes.${index}.sub_division_id`]} onChange={(value) => update(index, { sub_division_id: value })} options={subDivisionOptions} compact />
                            </div>
                            {!single && rows.length > 1 && (
                                <Button className="mt-3" type="button" variant="ghost" onClick={() => onChange(rows.filter((_, item) => item !== index))}><Trash2 size={15} />Hapus cakupan</Button>
                            )}
                            {!single && !scope.branch_id && !scope.division_id && !scope.sub_division_id && <p className="mt-2 text-xs text-slate-500">Tidak memilih cakupan berarti dapat mengakses seluruh organisasi.</p>}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

function ImpersonationDialog({ user, form, close }: { user: UserRow | null; form: ReturnType<typeof useForm<{ reason: string }>>; close: () => void }) {
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (user) form.post(route('impersonation.start', user.id));
    };
    return (
        <Modal show={user !== null} maxWidth="md" onClose={close}>
            <form className="p-6" onSubmit={submit}>
                <DialogTitle className="text-lg font-semibold text-slate-900">Masuk sebagai {user?.name}</DialogTitle>
                <p className="mt-2 text-sm leading-6 text-slate-600">Anda akan melihat sistem dari sudut pandang pengguna ini. Hak akses Anda akan mengikuti peran dan cakupan mereka. Perubahan sensitif tidak diizinkan dan seluruh aktivitas akan tercatat.</p>
                <Field label="Alasan" value={form.data.reason} error={form.errors.reason} onChange={(value) => form.setData('reason', value)} />
                <div className="mt-6 flex justify-end gap-3"><Button type="button" variant="secondary" onClick={close}>Batal</Button><Button disabled={form.processing} type="submit">{form.processing ? 'Memproses...' : 'Mulai'}</Button></div>
            </form>
        </Modal>
    );
}

function changeRole(role: string, currentScopes: Scope[], setScopes: (scopes: Scope[]) => void, setRole: (role: string) => void) {
    setRole(role);
    if (role === 'Super Admin') return setScopes([]);
    const first = currentScopes[0] ?? emptyScope();
    setScopes(singleScopeRoles.includes(role) ? [first] : (currentScopes.length ? currentScopes : [first]));
}

function Field({ label, value, error, onChange, type = 'text' }: { label: string; value: string; error?: string; onChange: (value: string) => void; type?: string }) {
    return <label className="mt-4 block text-sm font-semibold text-slate-700">{label}<input className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand" type={type} value={value} onChange={(event) => onChange(event.target.value)} required />{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</label>;
}

function Select({ label, value, error, onChange, options, compact = false }: { label: string; value: string; error?: string; onChange: (value: string) => void; options: Option[]; compact?: boolean }) {
    return <label className={`${compact ? '' : 'mt-4'} block text-sm font-semibold text-slate-700`}>{label}<select className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand" value={value} onChange={(event) => onChange(event.target.value)}><option value="">Pilih {label.toLowerCase()}</option>{options.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}</select>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</label>;
}