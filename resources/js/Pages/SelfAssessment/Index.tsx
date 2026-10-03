import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { ClipboardCheck, ClipboardList } from 'lucide-react';

type Period = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    frequency: string;
    self_assessment_id: number | null;
    self_assessment_status: string | null;
};

const statusLabels: Record<string, string> = {
    DRAFT: 'Draf',
    SUBMITTED: 'Diajukan',
};

const statusStyles: Record<string, string> = {
    DRAFT: 'bg-slate-100 text-slate-700',
    SUBMITTED: 'bg-blue-50 text-blue-700',
};

export default function Index({
    periods,
    employee,
    error,
}: {
    periods: Period[];
    employee: { id: number; full_name: string; employee_number: string } | null;
    error?: string;
}) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="text-sm text-slate-500">Penilaian</p>
                    <h1 className="text-[26px] font-bold text-slate-900">Penilaian Diri</h1>
                </div>
            }
        >
            <Head title="Penilaian Diri" />
            <div className="mx-auto max-w-[900px] space-y-6 p-4 sm:p-6 lg:p-8">
                {error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {error}
                    </div>
                )}

                {employee && (
                    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-3 border-b border-slate-100 pb-4">
                            <div className="flex size-10 items-center justify-center rounded-full bg-brand-subtle">
                                <ClipboardCheck size={20} className="text-brand" />
                            </div>
                            <div>
                                <p className="font-semibold text-slate-900">{employee.full_name}</p>
                                <p className="text-sm text-slate-500">{employee.employee_number}</p>
                            </div>
                        </div>

                        <p className="mt-4 text-sm text-slate-600">
                            Penilaian diri membantu Anda merefleksikan kinerja selama periode tertentu.
                            Hasil penilaian diri akan digunakan sebagai referensi oleh atasan saat melakukan penilaian.
                        </p>
                    </div>
                )}

                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="flex items-center gap-2 text-base font-semibold text-slate-900">
                        <ClipboardList size={18} />
                        Periode Tersedia
                    </h2>

                    <div className="mt-4">
                        {periods.length > 0 ? (
                            <div className="divide-y divide-slate-100 rounded-lg border border-slate-200">
                                {periods.map((period) => (
                                    <div
                                        className="flex flex-col justify-between gap-3 p-4 sm:flex-row sm:items-center"
                                        key={period.id}
                                    >
                                        <div>
                                            <p className="text-sm font-semibold text-slate-900">{period.name}</p>
                                            <p className="mt-1 text-xs text-slate-500">
                                                {period.start_date} s/d {period.end_date}
                                            </p>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-2">
                                            {period.self_assessment_status && (
                                                <span
                                                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusStyles[period.self_assessment_status] ?? 'bg-slate-100 text-slate-600'}`}
                                                >
                                                    {statusLabels[period.self_assessment_status] ?? period.self_assessment_status}
                                                </span>
                                            )}
                                            <Button asChild variant={period.self_assessment_id ? 'secondary' : 'default'}>
                                                <Link href={route('self-assessment.create', period.id)}>
                                                    {period.self_assessment_id ? 'Buka Penilaian' : 'Mulai Penilaian'}
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                                <ClipboardCheck className="mx-auto text-slate-300" size={28} />
                                <p className="mt-3 text-sm font-semibold text-slate-700">Belum ada periode aktif</p>
                                <p className="mt-1 text-xs text-slate-500">
                                    Periode penilaian akan muncul di sini ketika sudah diaktifkan oleh SDM.
                                </p>
                            </div>
                        )}
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
