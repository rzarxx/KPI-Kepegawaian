import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { ClipboardCheck, User } from 'lucide-react';

type ScoreDetail = {
    component_name: string;
    raw_score: string;
    weight: string;
    weighted_score: string;
    note: string | null;
};

type EvaluationData = {
    id: number;
    status: string;
    total_score: string;
    notes: string | null;
    submitted_at: string | null;
    employee: { id: number; full_name: string; employee_number: string };
    period: { id: number; name: string; start_date: string; end_date: string };
    scores: ScoreDetail[];
};

export default function Show({ evaluation }: { evaluation: EvaluationData }) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="text-sm text-slate-500">Penilaian</p>
                    <h1 className="text-[26px] font-bold text-slate-900">Hasil Penilaian Diri</h1>
                </div>
            }
        >
            <Head title="Hasil Penilaian Diri" />
            <div className="mx-auto max-w-[900px] space-y-6 p-4 sm:p-6 lg:p-8">
                {/* Header Info */}
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-brand-subtle">
                                <User size={20} className="text-brand" />
                            </div>
                            <div>
                                <h2 className="font-semibold text-slate-900">{evaluation.employee.full_name}</h2>
                                <p className="text-sm text-slate-500">{evaluation.employee.employee_number}</p>
                            </div>
                        </div>
                        <div className="flex flex-wrap items-center gap-2 text-sm">
                            <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                Penilaian Diri
                            </span>
                            <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                {evaluation.period.name}
                            </span>
                            {evaluation.submitted_at && (
                                <span className="text-xs text-slate-500">Diajukan {evaluation.submitted_at}</span>
                            )}
                        </div>
                    </div>
                </section>

                {/* Scores Table */}
                <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center gap-2 border-b border-slate-100 p-5">
                        <ClipboardCheck size={18} className="text-brand" />
                        <h2 className="font-semibold text-slate-900">Detail Penilaian</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                    <th className="px-5 py-3">Komponen</th>
                                    <th className="px-5 py-3 text-right">Bobot</th>
                                    <th className="px-5 py-3 text-right">Nilai</th>
                                    <th className="px-5 py-3 text-right">Nilai Terbobot</th>
                                    <th className="px-5 py-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {evaluation.scores.map((score, i) => (
                                    <tr key={i} className="hover:bg-slate-50">
                                        <td className="px-5 py-3 font-medium text-slate-800">{score.component_name}</td>
                                        <td className="px-5 py-3 text-right text-slate-600">{score.weight}%</td>
                                        <td className="px-5 py-3 text-right font-medium text-slate-900">{score.raw_score}</td>
                                        <td className="px-5 py-3 text-right text-slate-600">{Number(score.weighted_score).toFixed(2)}</td>
                                        <td className="px-5 py-3 text-slate-500">{score.note || '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-slate-200 bg-slate-50">
                                    <td className="px-5 py-3 font-semibold text-slate-900" colSpan={3}>
                                        Total
                                    </td>
                                    <td className="px-5 py-3 text-right text-lg font-bold text-brand">
                                        {Number(evaluation.total_score).toFixed(2)}
                                    </td>
                                    <td />
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                {/* Notes */}
                {evaluation.notes && (
                    <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 className="text-sm font-semibold text-slate-700">Catatan Tambahan</h3>
                        <p className="mt-2 whitespace-pre-wrap text-sm text-slate-600">{evaluation.notes}</p>
                    </section>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
