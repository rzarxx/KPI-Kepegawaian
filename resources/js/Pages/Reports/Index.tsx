import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { zodResolver } from '@hookform/resolvers/zod';
import { flexRender, getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { Head, Link, router } from '@inertiajs/react';
import { Download, FileSpreadsheet, Filter, LoaderCircle } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useForm, useWatch } from 'react-hook-form';
import { z } from 'zod';

type Option = { id: number; name: string; branch_id?: number; division_id?: number };
type Row = { id: number; name: string; number: string; status: string; branch?: string; division?: string; score?: number | string; criteria?: string };
type ExportItem = { id: number; status: string; row_count?: number; created_at: string; expires_at?: string };
type Paginated<T> = { data: T[]; links: { url?: string; label: string; active: boolean }[]; from?: number; to?: number; total: number };
type FilterOptions = { branches: Option[]; divisions: Option[]; subDivisions: Option[]; criteria: Option[] };

const filterSchema = z.object({
    start_date: z.string().optional(),
    end_date: z.string().optional(),
    branch_id: z.string().optional(),
    division_id: z.string().optional(),
    sub_division_id: z.string().optional(),
    status: z.string().optional(),
    criteria_id: z.string().optional(),
    problem_status: z.string().optional(),
}).superRefine((values, context) => {
    if (values.start_date && values.end_date && values.end_date < values.start_date) {
        context.addIssue({ code: z.ZodIssueCode.custom, path: ['end_date'], message: 'Tanggal akhir tidak boleh sebelum tanggal mulai.' });
    }
});
type FilterValues = z.infer<typeof filterSchema>;

export default function Index({
    rows,
    filters,
    filterOptions,
    exports,
    canExport,
}: {
    rows: Paginated<Row>;
    filters: Partial<Record<keyof FilterValues, string | number>>;
    filterOptions: FilterOptions;
    exports: ExportItem[];
    canExport: boolean;
}) {
    const [exporting, setExporting] = useState(false);
    const { register, handleSubmit, control, setValue, formState: { errors } } = useForm<FilterValues>({
        resolver: zodResolver(filterSchema),
        defaultValues: Object.fromEntries(Object.keys(filterSchema._def.schema.shape).map((key) => [key, String(filters[key as keyof FilterValues] ?? '')])) as FilterValues,
    });
    const branchId = useWatch({ control, name: 'branch_id' });
    const divisionId = useWatch({ control, name: 'division_id' });
    const divisions = filterOptions.divisions.filter((option) => !branchId || option.branch_id === Number(branchId));
    const subDivisions = filterOptions.subDivisions.filter((option) => !divisionId || option.division_id === Number(divisionId));
    const columns = useMemo<ColumnDef<Row>[]>(() => [
        { accessorKey: 'name', header: 'Karyawan', cell: ({ row }) => <div><p className="font-semibold text-slate-800">{row.original.name}</p><p className="text-xs text-slate-500">{row.original.number}</p></div> },
        { accessorKey: 'status', header: 'Status' },
        { accessorKey: 'branch', header: 'Cabang', cell: ({ getValue }) => getValue<string>() || '-' },
        { accessorKey: 'division', header: 'Divisi', cell: ({ getValue }) => getValue<string>() || '-' },
        { accessorKey: 'score', header: 'Nilai', cell: ({ getValue }) => getValue() !== null && getValue() !== undefined ? Number(getValue()).toFixed(2) : '-' },
        { accessorKey: 'criteria', header: 'Kriteria', cell: ({ getValue }) => getValue<string>() || '-' },
    ], []);
    // TanStack Table intentionally returns callbacks that React Compiler does not memoize.
    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({ data: rows.data, columns, getCoreRowModel: getCoreRowModel() });
    const apply = (values: FilterValues) => router.get(route('reports.index'), clean(values), { preserveState: true, replace: true });
    const exportReport = (values: FilterValues) => {
        setExporting(true);
        router.post(route('reports.export'), clean(values), { preserveScroll: true, onFinish: () => setExporting(false) });
    };

    return (
        <AuthenticatedLayout header={<div><p className="text-sm text-slate-500">Analisis dan ekspor</p><h1 className="text-[26px] font-bold text-slate-900">Laporan</h1></div>}>
            <Head title="Laporan" />
            <div className="mx-auto max-w-[1500px] space-y-6 p-4 sm:p-6 lg:p-8">
                <form className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" onSubmit={handleSubmit(apply)}>
                    <div className="flex items-center gap-2"><Filter size={18} className="text-green-700" /><h2 className="font-semibold text-slate-900">Filter laporan</h2></div>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Field label="Tanggal mulai"><input className={input} type="date" {...register('start_date')} /></Field>
                        <Field label="Tanggal akhir" error={errors.end_date?.message}><input className={input} type="date" {...register('end_date')} /></Field>
                        <Field label="Cabang"><select className={input} {...register('branch_id', { onChange: () => { setValue('division_id', ''); setValue('sub_division_id', ''); } })}><option value="">Semua cabang</option>{filterOptions.branches.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}</select></Field>
                        <Field label="Divisi"><select className={input} {...register('division_id', { onChange: () => setValue('sub_division_id', '') })}><option value="">Semua divisi</option>{divisions.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}</select></Field>
                        <Field label="Sub Divisi"><select className={input} {...register('sub_division_id')}><option value="">Semua sub divisi</option>{subDivisions.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}</select></Field>
                        <Field label="Status karyawan"><select className={input} {...register('status')}><option value="">Semua status</option><option value="ACTIVE">Aktif</option><option value="PROBATION">Masa Percobaan</option><option value="MUTATED">Mutasi</option><option value="RESIGNED">Resign</option><option value="TERMINATED">Terminasi</option><option value="INACTIVE">Tidak Aktif</option></select></Field>
                        <Field label="Kriteria penilaian"><select className={input} {...register('criteria_id')}><option value="">Semua kriteria</option>{filterOptions.criteria.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}</select></Field>
                        <Field label="Status masalah"><select className={input} {...register('problem_status')}><option value="">Semua status</option><option value="OPEN">Terbuka</option><option value="UNDER_REVIEW">Ditinjau</option><option value="RESOLVED">Selesai</option><option value="CLOSED">Ditutup</option></select></Field>
                    </div>
                    <div className="mt-5 flex flex-wrap gap-3"><Button type="submit" variant="secondary">Terapkan Filter</Button>{canExport && <Button disabled={exporting} type="button" onClick={handleSubmit(exportReport)}>{exporting ? <LoaderCircle className="animate-spin" size={16} /> : <FileSpreadsheet size={16} />}{exporting ? 'Menyiapkan...' : 'Ekspor XLSX'}</Button>}</div>
                </form>

                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-100 p-5"><div><h2 className="font-semibold text-slate-900">Hasil laporan</h2><p className="mt-1 text-sm text-slate-500">{rows.total} karyawan sesuai filter dan cakupan Anda.</p></div></div>
                    <div className="overflow-x-auto"><table className="w-full min-w-[900px] text-sm"><thead className="bg-slate-50">{table.getHeaderGroups().map((group) => <tr key={group.id}>{group.headers.map((header) => <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500" key={header.id}>{flexRender(header.column.columnDef.header, header.getContext())}</th>)}</tr>)}</thead><tbody className="divide-y divide-slate-100">{table.getRowModel().rows.length ? table.getRowModel().rows.map((row) => <tr className="hover:bg-slate-50" key={row.id}>{row.getVisibleCells().map((cell) => <td className="px-5 py-3 text-slate-600" key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</td>)}</tr>) : <tr><td className="px-5 py-12 text-center text-slate-500" colSpan={columns.length}>Tidak ada data yang sesuai dengan filter.</td></tr>}</tbody></table></div>
                    {rows.links.length > 3 && <Pagination links={rows.links} />}
                </section>

                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="font-semibold text-slate-900">Riwayat ekspor</h2>
                    <p className="mt-1 text-sm text-slate-500">File disimpan privat dan otomatis kedaluwarsa setelah 24 jam.</p>
                    <div className="mt-4 divide-y divide-slate-100">{exports.length ? exports.map((item) => <div className="flex items-center justify-between gap-4 py-3 text-sm" key={item.id}><div><p className="font-medium text-slate-800">{exportLabels[item.status] || item.status}</p><p className="text-xs text-slate-500">{item.row_count !== null && item.row_count !== undefined ? item.row_count + ' baris' : 'Menunggu proses'}</p></div>{item.status === 'COMPLETED' && <Link aria-label="Unduh laporan" className="flex size-10 items-center justify-center rounded-lg border border-slate-200 text-green-700 hover:bg-green-50" href={route('reports.download', item.id)}><Download size={17} /></Link>}</div>) : <p className="py-8 text-center text-sm text-slate-500">Belum ada riwayat ekspor.</p>}</div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function clean(values: FilterValues) { return Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '')); }
function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) { return <label className="text-sm font-medium text-slate-700">{label}{children}{error && <span className="mt-1 block text-xs text-red-600">{error}</span>}</label>; }
function Pagination({ links }: { links: Paginated<unknown>['links'] }) { return <nav className="flex flex-wrap justify-center gap-1 border-t border-slate-100 p-4" aria-label="Navigasi halaman">{links.map((link, index) => link.url ? <Link className={'rounded-lg border px-3 py-2 text-sm ' + (link.active ? 'border-green-600 bg-green-50 text-green-800' : 'border-slate-200 text-slate-600')} href={link.url} key={index}>{paginationLabel(link.label)}</Link> : <span className="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" key={index}>{paginationLabel(link.label)}</span>)}</nav>; }
function paginationLabel(label: string) { return label.replace('&laquo;', '‹').replace('&raquo;', '›'); }
const input = 'mt-1 min-h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-green-600 focus:ring-green-600';
const exportLabels: Record<string, string> = { QUEUED: 'Menunggu antrean', PROCESSING: 'Sedang diproses', COMPLETED: 'Siap diunduh', FAILED: 'Gagal diproses', EXPIRED: 'Kedaluwarsa' };
