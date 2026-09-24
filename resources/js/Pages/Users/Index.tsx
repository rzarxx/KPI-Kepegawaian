import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { DialogTitle } from '@headlessui/react';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, ShieldCheck, Trash2, Users } from 'lucide-react';
import { FormEvent, useState } from 'react';

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

    const beginEdit = (user: UserRow) => {
        setEditing(user);
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
        router.post(route('users.toggle', user.id), {}, {
            preserveScroll: true,
            onStart: () => setToggling(user.id),
            onFinish: () => setToggling(null),
        });
    };

    return (
        <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Pengaturan</p><h1 className="text-[26px] font-bold text-slate-900">Pengguna dan Akses</h1></div>}>
            <Head title="Pengguna dan Akses" />
            <div className="mx-auto grid max-w-[1400px] gap-6 p-4 sm:p-6 lg:grid-cols-[1fr_420px] lg:p-8">
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-2 border-b border-slate-100 p-5 font-semibold text-slate-900"><Users size={18} />Daftar Pengguna</div>
                    <div className="divide-y divide-slate-100">
                        {users.length ? users.map((user) => (
                            <div className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" key={user.id}>
                                <div>
                                    <p className="font-medium text-slate-800">{user.name}</p>
                                    <p className="text-sm text-slate-500">{user.email} · {user.roles.join(', ') || 'Tanpa role'}</p>
                                    <p className="mt-1 text-xs text-slate-400">{user.scopes.map((scope) => scope.label).join('; ') || 'Belum memiliki cakupan organisasi'}</p>
                                </div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <Button disabled={toggling === user.id} type="button" variant={user.is_active ? 'secondary' : 'danger'} onClick={() => toggleUser(user)}>
                                        {user.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                    </Button>
                                    {canManageRoles && <Button type="button" variant="ghost" onClick={() => beginEdit(user)}><Pencil size={16} />Ubah Akses</Button>}
                                    {canImpersonate && user.is_active && !user.roles.includes('Super Admin') && (
                                        <Button type="button" variant="ghost" onClick={() => { setTarget(user); impersonation.reset(); impersonation.clearErrors(); }}><ShieldCheck size={16} />Masuk sementara</Button>
                                    )}
                                </div>
                            </div>
                        )) : <p className="p-6 text-sm text-slate-500">Belum ada pengguna yang dapat dilihat dalam cakupan Anda.</p>}
                    </div>
                </section>
                <CreateUserForm form={create} roles={roles} branches={branches} divisions={divisions} subDivisions={subDivisions} />
            </div>
            <AccessDialog
                user={editing}
                form={access}
                roles={roles}
                branches={branches}
                divisions={divisions}
                subDivisions={subDivisions}
                close={() => setEditing(null)}
            />
            <ImpersonationDialog user={target} form={impersonation} close={() => setTarget(null)} />
        </AuthenticatedLayout>
    );
}

function CreateUserForm({
    form,
    roles,
    branches,
    divisions,
    subDivisions,
}: {
    form: ReturnType<typeof useForm<CreateForm>>;
    roles: string[];
    branches: Option[];
    divisions: Option[];
    subDivisions: Option[];
}) {
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('users.store'), { onSuccess: () => form.reset() });
    };

    return (
        <form className="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm" onSubmit={submit}>
            <h2 className="font-semibold text-slate-900">Tambah Pengguna</h2>
            <Field label="Nama" value={form.data.name} error={form.errors.name} onChange={(value) => form.setData('name', value)} />
            <Field label="Email" value={form.data.email} error={form.errors.email} type="email" onChange={(value) => form.setData('email', value)} />
            <Field label="Kata Sandi" value={form.data.password} error={form.errors.password} type="password" onChange={(value) => form.setData('password', value)} />
            <Select label="Role" value={form.data.role} error={form.errors.role} onChange={(role) => changeRole(role, form.data.scopes, (scopes) => form.setData('scopes', scopes), (value) => form.setData('role', value))} options={roles.map((name) => ({ id: name, name }))} />
            <ScopeEditor role={form.data.role} scopes={form.data.scopes} errors={form.errors as Record<string, string>} branches={branches} divisions={divisions} subDivisions={subDivisions} onChange={(scopes) => form.setData('scopes', scopes)} />
            <Button className="mt-5 w-full" disabled={form.processing} type="submit">{form.processing ? 'Menyimpan...' : 'Simpan Pengguna'}</Button>
        </form>
    );
}

function AccessDialog({
    user,
    form,
    roles,
    branches,
    divisions,
    subDivisions,
    close,
}: {
    user: UserRow | null;
    form: ReturnType<typeof useForm<AccessForm>>;
    roles: string[];
    branches: Option[];
    divisions: Option[];
    subDivisions: Option[];
    close: () => void;
}) {
    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (user) form.put(route('users.update', user.id), { onSuccess: close });
    };

    return (
        <Modal show={user !== null} maxWidth="lg" onClose={close}>
            <form className="max-h-[90vh] overflow-y-auto p-6" onSubmit={submit}>
                <DialogTitle className="text-lg font-semibold text-slate-900">Ubah akses {user?.name}</DialogTitle>
                <Field label="Nama" value={form.data.name} error={form.errors.name} onChange={(value) => form.setData('name', value)} />
                <Select label="Role" value={form.data.role} error={form.errors.role} onChange={(role) => changeRole(role, form.data.scopes, (scopes) => form.setData('scopes', scopes), (value) => form.setData('role', value))} options={roles.map((name) => ({ id: name, name }))} />
                <ScopeEditor role={form.data.role} scopes={form.data.scopes} errors={form.errors as Record<string, string>} branches={branches} divisions={divisions} subDivisions={subDivisions} onChange={(scopes) => form.setData('scopes', scopes)} />
                <div className="mt-6 flex justify-end gap-3">
                    <Button type="button" variant="secondary" onClick={close}>Batal</Button>
                    <Button disabled={form.processing} type="submit">{form.processing ? 'Menyimpan...' : 'Simpan'}</Button>
                </div>
            </form>
        </Modal>
    );
}

function ScopeEditor({
    role,
    scopes,
    errors,
    branches,
    divisions,
    subDivisions,
    onChange,
}: {
    role: string;
    scopes: Scope[];
    errors: Record<string, string>;
    branches: Option[];
    divisions: Option[];
    subDivisions: Option[];
    onChange: (scopes: Scope[]) => void;
}) {
    if (!role) return null;
    if (role === 'Super Admin') return <p className="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">Super Admin menggunakan cakupan seluruh organisasi.</p>;

    const single = singleScopeRoles.includes(role);
    const rows = scopes.length ? scopes : [emptyScope()];
    const update = (index: number, patch: Partial<Scope>) => onChange(rows.map((scope, item) => item === index ? { ...scope, ...patch } : scope));

    return (
        <div className="mt-5 border-t border-slate-100 pt-5">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-slate-800">Cakupan Organisasi</p>
                    <p className="mt-1 text-xs text-slate-500">{single ? 'Role kepala organisasi wajib memiliki tepat satu cakupan.' : 'Tambahkan semua cakupan yang boleh diakses pengguna.'}</p>
                </div>
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
                            {!single && !scope.branch_id && !scope.division_id && !scope.sub_division_id && <p className="mt-2 text-xs text-slate-500">Cakupan kosong berarti seluruh organisasi.</p>}
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
                <DialogTitle className="text-lg font-semibold text-slate-900">Masuk sementara sebagai {user?.name}</DialogTitle>
                <p className="mt-2 text-sm leading-6 text-slate-600">Akses Anda mengikuti role dan cakupan pengguna target. Perubahan sensitif diblokir dan sesi dicatat pada audit.</p>
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
    return <label className="mt-4 block text-sm font-semibold text-slate-700">{label}<input className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600" type={type} value={value} onChange={(event) => onChange(event.target.value)} required />{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</label>;
}

function Select({ label, value, error, onChange, options, compact = false }: { label: string; value: string; error?: string; onChange: (value: string) => void; options: Option[]; compact?: boolean }) {
    return <label className={`${compact ? '' : 'mt-4'} block text-sm font-semibold text-slate-700`}>{label}<select className="mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600" value={value} onChange={(event) => onChange(event.target.value)}><option value="">Pilih {label.toLowerCase()}</option>{options.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}</select>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</label>;
}
