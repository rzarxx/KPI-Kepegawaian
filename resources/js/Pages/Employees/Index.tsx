import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Filter, Plus, Search, Users } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';

type Option = { id: number; name: string; branch_id?: number; division_id?: number };
type Employee = {
    id: number;
    employee_number: string;
    full_name: string;
    status: string;
    status_label: string;
    assignment: { branch?: string; division?: string; position?: string } | null;
};
type Filters = { search?: string; status?: string; branch_id?: number; division_id?: number; sub_division_id?: number };
type Props = {
    employees: { data: Employee[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: Filters;
    filterOptions: { branches: Option[]; divisions: Option[]; subDivisions: Option[] };
    canCreate: boolean;
};

export default function Index({ employees, filters, filterOptions, canCreate }: Props) {
    const [form, setForm] = useState({
        search: filters.search ?? '',
        status: filters.status ?? '',
        branch_id: String(filters.branch_id ?? ''),
        division_id: String(filters.division_id ?? ''),
        sub_division_id: String(filters.sub_division_id ?? ''),
    });
    const divisions = useMemo(() => filterOptions.divisions.filter((item) => !form.branch_id || item.branch_id === Number(form.branch_id)), [filterOptions.divisions, form.branch_id]);
    const subDivisions = useMemo(() => filterOptions.subDivisions.filter((item) => !form.division_id || item.division_id === Number(form.division_id)), [filterOptions.subDivisions, form.division_id]);
    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(route('employees.index'), clean(form), { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Data kepegawaian</p><h1 className="text-[26px] font-bold text-slate-900">Karyawan</h1></div>}>
            <Head title="Karyawan" />
            <div className="mx-auto max-w-[1600px] p-4 sm:p-6 lg:p-8">
                <form className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" onSubmit={submit}>
                    <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                        <div className="grid flex-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                            <label className="relative sm:col-span-2 xl:col-span-1">
                                <span className="sr-only">Cari karyawan</span>
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={17} />
                                <input className={inputClass + ' pl-10'} onChange={(event) => setForm({ ...form, search: event.target.value })} placeholder="Cari nama atau nomor" value={form.search} />
                            </label>
                            <select aria-label="Filter cabang" className={inputClass} value={form.branch_id} onChange={(event) => setForm({ ...form, branch_id: event.target.value, division_id: '', sub_division_id: '' })}><option value="">Semua cabang</option>{filterOptions.branches.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>
                            <select aria-label="Filter divisi" className={inputClass} value={form.division_id} onChange={(event) => setForm({ ...form, division_id: event.target.value, sub_division_id: '' })}><option value="">Semua divisi</option>{divisions.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>
                            <select aria-label="Filter sub divisi" className={inputClass} value={form.sub_division_id} onChange={(event) => setForm({ ...form, sub_division_id: event.target.value })}><option value="">Semua sub divisi</option>{subDivisions.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>
                            <select aria-label="Filter status" className={inputClass} value={form.status} onChange={(event) => setForm({ ...form, status: event.target.value })}><option value="">Semua status</option><option value="ACTIVE">Aktif</option><option value="PROBATION">Masa Percobaan</option><option value="MUTATED">Mutasi</option><option value="RESIGNED">Resign</option><option value="TERMINATED">Terminasi</option><option value="INACTIVE">Tidak Aktif</option></select>
                        </div>
                        <div className="flex gap-3"><Button type="submit" variant="secondary"><Filter size={16} />Terapkan</Button>{canCreate && <Button asChild><Link href={route('employees.create')}><Plus size={16} />Tambah Karyawan</Link></Button>}</div>
                    </div>
                </form>

                <div className="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    {employees.data.length ? <>
                        <div className="hidden overflow-x-auto md:block"><table className="w-full text-left text-sm"><thead className="bg-slate-100 text-xs font-semibold text-slate-600"><tr><th className="px-5 py-4">Karyawan</th><th className="px-5 py-4">Penempatan</th><th className="px-5 py-4">Status</th><th className="px-5 py-4"><span className="sr-only">Tindakan</span></th></tr></thead><tbody>{employees.data.map((employee) => <tr className="border-t border-slate-100" key={employee.id}><td className="px-5 py-4"><Link className="font-semibold text-slate-900 hover:text-green-700" href={route('employees.show', employee.id)}>{employee.full_name}</Link><p className="mt-1 text-xs text-slate-500">{employee.employee_number}</p></td><td className="px-5 py-4 text-slate-600">{employee.assignment ? `${employee.assignment.division ?? '-'} · ${employee.assignment.position ?? '-'}` : 'Belum ada penempatan'}</td><td className="px-5 py-4"><span className="rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-800">{employee.status_label}</span></td><td className="px-5 py-4 text-right"><Link className="text-sm font-semibold text-green-700" href={route('employees.show', employee.id)}>Lihat</Link></td></tr>)}</tbody></table></div>
                        <div className="divide-y divide-slate-100 md:hidden">{employees.data.map((employee) => <Link className="block p-4" href={route('employees.show', employee.id)} key={employee.id}><p className="font-semibold text-slate-900">{employee.full_name}</p><p className="mt-1 text-sm text-slate-500">{employee.employee_number} · {employee.assignment?.division ?? 'Belum ada penempatan'}</p></Link>)}</div>
                    </> : <div className="flex flex-col items-center px-6 py-16 text-center"><Users className="text-slate-400" size={40} /><h2 className="mt-4 font-semibold text-slate-900">Tidak ada karyawan yang sesuai</h2><p className="mt-1 max-w-sm text-sm text-slate-500">Ubah pencarian atau filter, atau tambahkan karyawan baru jika Anda memiliki izin.</p></div>}
                </div>
                {employees.links.length > 3 && <nav aria-label="Navigasi halaman" className="mt-5 flex flex-wrap justify-center gap-1">{employees.links.map((link, index) => link.url ? <Link className={'rounded-lg border px-3 py-2 text-sm ' + (link.active ? 'border-green-600 bg-green-50 text-green-800' : 'border-slate-200 bg-white text-slate-600')} href={link.url} key={index}>{paginationLabel(link.label)}</Link> : <span className="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" key={index}>{paginationLabel(link.label)}</span>)}</nav>}
            </div>
        </AuthenticatedLayout>
    );
}

const inputClass = 'h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600';
function clean(values: Record<string, string>) { return Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '')); }
function paginationLabel(label: string) { return label.replace('&laquo;', '‹').replace('&raquo;', '›'); }
