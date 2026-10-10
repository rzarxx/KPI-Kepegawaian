import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ReactNode } from 'react';

type Option = { id: number; name: string; branch_id?: number; division_id?: number };
type LockedScope = { branch_id: number | null; division_id: number | null; sub_division_id: number | null } | null;

export default function Create({ branches, divisions, subDivisions, positions, lockedScope }: {
    branches: Option[];
    divisions: Option[];
    subDivisions: Option[];
    positions: Option[];
    lockedScope?: LockedScope;
}) {
    const locked = lockedScope ?? null;
    const { data, setData, post, processing, errors } = useForm({
        employee_number: '',
        national_id: '',
        full_name: '',
        email: '',
        phone: '',
        birth_date: '',
        gender: '',
        join_date: '',
        current_status: 'ACTIVE',
        branch_id: String(locked?.branch_id ?? ''),
        division_id: String(locked?.division_id ?? ''),
        sub_division_id: String(locked?.sub_division_id ?? ''),
        position_id: '',
    });
    const filteredDivisions = divisions.filter((division) => !data.branch_id || division.branch_id === Number(data.branch_id));
    const filteredSubDivisions = subDivisions.filter((subDivision) => !data.division_id || subDivision.division_id === Number(data.division_id));
    const field = 'mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand disabled:bg-slate-100 disabled:text-slate-500';

    return <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Karyawan</p><h1 className="text-[26px] font-bold text-slate-900">Tambah Karyawan</h1></div>}>
        <Head title="Tambah Karyawan" />
        <div className="mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">
            <form className="space-y-6" onSubmit={(event) => { event.preventDefault(); post(route('employees.store')); }}>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Identitas Karyawan</h2>
                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                        <Field label="Nomor Induk Karyawan" error={errors.employee_number}><input className={field} value={data.employee_number} onChange={(event) => setData('employee_number', event.target.value)} /></Field>
                        <Field label="Nama Lengkap" error={errors.full_name}><input className={field} value={data.full_name} onChange={(event) => setData('full_name', event.target.value)} /></Field>
                        <Field label="NIK" error={errors.national_id}><input className={field} value={data.national_id} onChange={(event) => setData('national_id', event.target.value)} /></Field>
                        <Field label="Email" error={errors.email}><input className={field} type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} /></Field>
                        <Field label="Nomor Telepon" error={errors.phone}><input className={field} value={data.phone} onChange={(event) => setData('phone', event.target.value)} /></Field>
                        <Field label="Tanggal Bergabung" error={errors.join_date}><input className={field} type="date" value={data.join_date} onChange={(event) => setData('join_date', event.target.value)} /></Field>
                    </div>
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Penempatan Awal</h2>
                    {locked && <p className="mt-2 text-sm text-slate-500">Cakupan organisasi Anda sudah diterapkan secara otomatis.</p>}
                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                        <Field label="Cabang" error={errors.branch_id}><select className={field} disabled={locked?.branch_id !== null && locked?.branch_id !== undefined} value={data.branch_id} onChange={(event) => { setData('branch_id', event.target.value); setData('division_id', ''); setData('sub_division_id', ''); }}><option value="">Pilih cabang</option>{branches.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></Field>
                        <Field label="Divisi" error={errors.division_id}><select className={field} disabled={locked?.division_id !== null && locked?.division_id !== undefined} value={data.division_id} onChange={(event) => { setData('division_id', event.target.value); setData('sub_division_id', ''); }}><option value="">Pilih divisi</option>{filteredDivisions.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></Field>
                        <Field label="Sub Divisi" error={errors.sub_division_id}><select className={field} disabled={locked?.sub_division_id !== null && locked?.sub_division_id !== undefined} value={data.sub_division_id} onChange={(event) => setData('sub_division_id', event.target.value)}><option value="">Tidak ada</option>{filteredSubDivisions.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></Field>
                        <Field label="Jabatan" error={errors.position_id}><select className={field} value={data.position_id} onChange={(event) => setData('position_id', event.target.value)}><option value="">Belum ditentukan</option>{positions.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></Field>
                        <Field label="Status Awal" error={errors.current_status}><select className={field} value={data.current_status} onChange={(event) => setData('current_status', event.target.value)}><option value="ACTIVE">Aktif</option><option value="PROBATION">Masa Percobaan</option><option value="INACTIVE">Tidak Aktif</option></select></Field>
                    </div>
                </section>
                <div className="flex justify-end gap-3"><Button asChild variant="secondary"><Link href={route('employees.index')}>Batal</Link></Button><Button disabled={processing} type="submit">{processing ? 'Menyimpan...' : 'Simpan Karyawan'}</Button></div>
            </form>
        </div>
    </AuthenticatedLayout>;
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
    return <label className="block text-sm font-semibold text-slate-700">{label}{children}<InputError className="mt-1" message={error} /></label>;
}
