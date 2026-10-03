import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, router, useForm } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowLeft,
    BriefcaseBusiness,
    ClipboardCheck,
    Download,
    Eye,
    FileText,
    History,
    Pencil,
    RefreshCw,
    Trash2,
    Upload,
    UserRoundCheck,
    UserRoundX,
    X,
} from "lucide-react";
import { useState } from "react";

type Option = {
    id: number;
    name: string;
    branch_id?: number;
    division_id?: number;
};
type Employee = {
    id: number;
    full_name: string;
    employee_number: string;
    email?: string;
    phone?: string;
    status_label: string;
    assignment: {
        branch?: string;
        division?: string;
        sub_division?: string;
        position?: string;
    } | null;
};
type Assignment = {
    id: number;
    start_date: string;
    end_date?: string;
    branch: string;
    division: string;
    sub_division?: string;
    position?: string;
    reason?: string;
};
type StatusHistory = {
    id: number;
    label: string;
    effective_date: string;
    reason?: string;
};
type Activity = {
    id: string;
    date: string;
    type: "ASSIGNMENT" | "STATUS" | "INCIDENT" | "EVALUATION";
    title: string;
    description?: string;
};
type Document = {
    id: number;
    category: string;
    name: string;
    mime_type: string;
    size: number;
    uploaded_by?: string;
    created_at: string;
    can_delete: boolean;
};
type EvaluationPeriod = {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    evaluation_id?: number | null;
    evaluation_status?: 'DRAFT' | 'SUBMITTED' | 'APPROVED' | 'FINALIZED' | 'CLOSED' | null;
    self_assessment_id?: number | null;
    self_assessment_status?: string | null;
    self_assessment_score?: string | null;
};

export default function Show({
    employee,
    assignments,
    statusHistories,
    activities,
    documents,
    branches,
    divisions,
    subDivisions,
    positions,
    canViewIncidents,
    canViewDocuments,
    canUploadDocument,
    canEvaluate,
    evaluationPeriods,
    canRehire,
    canTransfer,
    canChangeStatus,
    canUpdate,
}: {
    employee: Employee;
    assignments: Assignment[];
    statusHistories: StatusHistory[];
    activities: Activity[];
    documents: Document[];
    branches: Option[];
    divisions: Option[];
    subDivisions: Option[];
    positions: Option[];
    canViewIncidents: boolean;
    canViewDocuments: boolean;
    canUploadDocument: boolean;
    canEvaluate: boolean;
    evaluationPeriods: EvaluationPeriod[];
    canRehire: boolean;
    canTransfer: boolean;
    canChangeStatus: boolean;
    canUpdate: boolean;
}) {
    const [showTransfer, setShowTransfer] = useState(false);
    const [showStatus, setShowStatus] = useState(false);
    const [showRehire, setShowRehire] = useState(false);
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <Button asChild variant="ghost">
                        <Link
                            aria-label="Kembali ke daftar karyawan"
                            href={route("employees.index")}
                        >
                            <ArrowLeft size={18} />
                        </Link>
                    </Button>
                    <div>
                        <p className="text-sm text-slate-500">Karyawan</p>
                        <h1 className="text-[26px] font-bold text-slate-900">
                            Detail Karyawan
                        </h1>
                    </div>
                </div>
            }
        >
            <Head title={employee.full_name} />
            <div className="mx-auto max-w-[1200px] space-y-6 p-4 sm:p-6 lg:p-8">
                <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row">
                        <div>
                            <p className="text-xl font-bold text-slate-900">
                                {employee.full_name}
                            </p>
                            <p className="mt-1 text-sm text-slate-500">
                                {employee.employee_number}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <span className="h-fit rounded-full bg-brand-subtle px-3 py-1.5 text-sm font-semibold text-brand-dark">
                                {employee.status_label}
                            </span>
                            {canViewIncidents && (
                                <Button asChild variant="secondary">
                                    <Link
                                        href={route(
                                            "employees.incidents.index",
                                            employee.id,
                                        )}
                                    >
                                        <AlertTriangle size={16} />
                                        Catatan Masalah
                                    </Link>
                                </Button>
                            )}
                            {canUpdate && (
                                <Button asChild variant="secondary">
                                    <Link
                                        href={route(
                                            "employees.edit",
                                            employee.id,
                                        )}
                                    >
                                        <Pencil size={16} />
                                        Ubah Identitas
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                    <div className="mt-6 grid gap-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-3">
                        <Info
                            label="Cabang"
                            value={employee.assignment?.branch}
                        />
                        <Info
                            label="Divisi"
                            value={employee.assignment?.division}
                        />
                        <Info
                            label="Sub Divisi"
                            value={employee.assignment?.sub_division}
                        />
                        <Info
                            label="Jabatan"
                            value={employee.assignment?.position}
                        />
                        <Info label="Email" value={employee.email} />
                        <Info label="Telepon" value={employee.phone} />
                    </div>
                </section>
                {canEvaluate && (
                    <EvaluationCard
                        employeeId={employee.id}
                        periods={evaluationPeriods}
                    />
                )}
                {(canTransfer || canChangeStatus || canRehire) && (
                    <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-base font-semibold text-slate-900">
                            Tindakan Karyawan
                        </h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Setiap tindakan menambah riwayat dan tidak menghapus
                            data sebelumnya.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-3">
                            {canTransfer && (
                                <Button
                                    type="button"
                                    onClick={() =>
                                        setShowTransfer(!showTransfer)
                                    }
                                >
                                    <RefreshCw size={16} />
                                    Mutasi Penempatan
                                </Button>
                            )}
                            {canChangeStatus && (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => setShowStatus(!showStatus)}
                                >
                                    <UserRoundX size={16} />
                                    Ubah Status Akhir
                                </Button>
                            )}
                            {canRehire && (
                                <Button
                                    type="button"
                                    onClick={() => setShowRehire(!showRehire)}
                                >
                                    <UserRoundCheck size={16} />
                                    Aktifkan Kembali
                                </Button>
                            )}
                        </div>
                        {showTransfer && (
                            <TransferForm
                                employeeId={employee.id}
                                branches={branches}
                                divisions={divisions}
                                subDivisions={subDivisions}
                                positions={positions}
                                onDone={() => setShowTransfer(false)}
                            />
                        )}
                        {showStatus && (
                            <StatusForm
                                employeeId={employee.id}
                                onDone={() => setShowStatus(false)}
                            />
                        )}
                        {showRehire && (
                            <RehireForm
                                employeeId={employee.id}
                                branches={branches}
                                divisions={divisions}
                                subDivisions={subDivisions}
                                positions={positions}
                                onDone={() => setShowRehire(false)}
                            />
                        )}
                    </section>
                )}
                <HistoryCard
                    assignments={assignments}
                    histories={statusHistories}
                />
                <ActivityTimeline activities={activities} />
                {canViewDocuments && (
                    <DocumentsCard
                        employeeId={employee.id}
                        documents={documents}
                        canUpload={canUploadDocument}
                    />
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function EvaluationCard({
    employeeId,
    periods,
}: {
    employeeId: number;
    periods: EvaluationPeriod[];
}) {
    const evalStatusLabel: Record<string, string> = {
        DRAFT: 'Draf',
        SUBMITTED: 'Diajukan',
        APPROVED: 'Disetujui',
        FINALIZED: 'Final',
        CLOSED: 'Ditutup',
    };
    const evalStatusStyle: Record<string, string> = {
        DRAFT: 'bg-slate-100 text-slate-600',
        SUBMITTED: 'bg-blue-50 text-blue-700',
        APPROVED: 'bg-amber-50 text-amber-700',
        FINALIZED: 'bg-brand-subtle text-brand-dark',
        CLOSED: 'bg-slate-800 text-white',
    };
    return (
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="flex items-center gap-2 text-base font-semibold text-slate-900">
                <ClipboardCheck size={18} />
                Penilaian Karyawan
            </h2>
            <p className="mt-1 text-sm text-slate-500">
                Pilih periode untuk memulai atau melanjutkan penilaian.
            </p>
            <div className="mt-4">
                {periods.length > 0 ? (
                    <div className="divide-y divide-slate-100 rounded-lg border border-slate-200">
                        {periods.map((period) => (
                            <div
                                className="flex flex-col justify-between gap-3 p-4 sm:flex-row sm:items-center"
                                key={period.id}
                            >
                                <div>
                                    <p className="text-sm font-semibold text-slate-900">
                                        {period.name}
                                    </p>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {period.start_date} s/d {period.end_date}
                                    </p>
                                    {period.self_assessment_status && (
                                        <p className="mt-1 flex items-center gap-1.5 text-xs">
                                            <span className="inline-block size-1.5 rounded-full bg-blue-400" />
                                            <span className="text-slate-500">
                                                Penilaian Diri:{' '}
                                                <span className="font-medium text-blue-700">
                                                    {evalStatusLabel[period.self_assessment_status] ?? period.self_assessment_status}
                                                </span>
                                            </span>
                                            {period.self_assessment_id && (
                                                <Link
                                                    href={route('self-assessment.show', period.self_assessment_id)}
                                                    className="text-brand underline underline-offset-2 hover:no-underline"
                                                >
                                                    Lihat
                                                </Link>
                                            )}
                                        </p>
                                    )}
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    {period.evaluation_status && (
                                        <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                            evalStatusStyle[period.evaluation_status] ?? 'bg-slate-100 text-slate-600'
                                        }`}>
                                            {evalStatusLabel[period.evaluation_status] ?? period.evaluation_status}
                                        </span>
                                    )}
                                    <Button asChild variant={period.evaluation_id ? 'secondary' : 'primary'}>
                                        <Link
                                            href={
                                                period.evaluation_id
                                                    ? route('evaluations.show', period.evaluation_id)
                                                    : route('evaluations.create', employeeId) + '?period_id=' + period.id
                                            }
                                        >
                                            {period.evaluation_id ? 'Buka Penilaian' : 'Mulai Penilaian'}
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
                        <p className="mt-1 text-xs text-slate-500">Buat periode penilaian di halaman Konfigurasi Penilaian terlebih dahulu.</p>
                    </div>
                )}
            </div>
        </section>
    );
}

function TransferForm({
    employeeId,
    branches,
    divisions,
    subDivisions,
    positions,
    onDone,
}: {
    employeeId: number;
    branches: Option[];
    divisions: Option[];
    subDivisions: Option[];
    positions: Option[];
    onDone: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        branch_id: "",
        division_id: "",
        sub_division_id: "",
        position_id: "",
        effective_date: "",
        reason: "",
    });
    const visibleDivisions = divisions.filter(
        (item) => item.branch_id === Number(data.branch_id),
    );
    const visibleSubDivisions = subDivisions.filter(
        (item) => item.division_id === Number(data.division_id),
    );
    return (
        <form
            className="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2"
            onSubmit={(event) => {
                event.preventDefault();
                post(route("employees.transfer", employeeId), {
                    onSuccess: onDone,
                });
            }}
        >
            <Select
                label="Cabang tujuan"
                value={data.branch_id}
                error={errors.branch_id}
                onChange={(value) => {
                    setData("branch_id", value);
                    setData("division_id", "");
                    setData("sub_division_id", "");
                }}
                options={branches}
            />
            <Select
                label="Divisi tujuan"
                value={data.division_id}
                error={errors.division_id}
                onChange={(value) => {
                    setData("division_id", value);
                    setData("sub_division_id", "");
                }}
                options={visibleDivisions}
            />
            <Select
                label="Sub Divisi tujuan"
                value={data.sub_division_id}
                error={errors.sub_division_id}
                onChange={(value) => setData("sub_division_id", value)}
                options={visibleSubDivisions}
                optional
            />
            <Select
                label="Jabatan tujuan"
                value={data.position_id}
                error={errors.position_id}
                onChange={(value) => setData("position_id", value)}
                options={positions}
                optional
            />
            <Field label="Tanggal berlaku" error={errors.effective_date}>
                <input
                    className={inputClass}
                    type="date"
                    value={data.effective_date}
                    onChange={(event) =>
                        setData("effective_date", event.target.value)
                    }
                    required
                />
            </Field>
            <Field label="Alasan mutasi" error={errors.reason}>
                <input
                    className={inputClass}
                    value={data.reason}
                    onChange={(event) => setData("reason", event.target.value)}
                    required
                />
            </Field>
            <div className="sm:col-span-2">
                <Button disabled={processing} type="submit">
                    {processing ? "Menyimpan..." : "Simpan Mutasi"}
                </Button>
            </div>
        </form>
    );
}
function RehireForm({
    employeeId,
    branches,
    divisions,
    subDivisions,
    positions,
    onDone,
}: {
    employeeId: number;
    branches: Option[];
    divisions: Option[];
    subDivisions: Option[];
    positions: Option[];
    onDone: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        branch_id: "",
        division_id: "",
        sub_division_id: "",
        position_id: "",
        effective_date: "",
        reason: "",
    });
    const visibleDivisions = divisions.filter(
        (item) => item.branch_id === Number(data.branch_id),
    );
    const visibleSubDivisions = subDivisions.filter(
        (item) => item.division_id === Number(data.division_id),
    );
    return (
        <form
            className="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2"
            onSubmit={(event) => {
                event.preventDefault();
                post(route("employees.rehire", employeeId), {
                    onSuccess: onDone,
                });
            }}
        >
            <Select
                label="Cabang penempatan"
                value={data.branch_id}
                error={errors.branch_id}
                onChange={(value) => {
                    setData("branch_id", value);
                    setData("division_id", "");
                    setData("sub_division_id", "");
                }}
                options={branches}
            />
            <Select
                label="Divisi penempatan"
                value={data.division_id}
                error={errors.division_id}
                onChange={(value) => {
                    setData("division_id", value);
                    setData("sub_division_id", "");
                }}
                options={visibleDivisions}
            />
            <Select
                label="Sub Divisi"
                value={data.sub_division_id}
                error={errors.sub_division_id}
                onChange={(value) => setData("sub_division_id", value)}
                options={visibleSubDivisions}
                optional
            />
            <Select
                label="Jabatan"
                value={data.position_id}
                error={errors.position_id}
                onChange={(value) => setData("position_id", value)}
                options={positions}
                optional
            />
            <Field label="Tanggal aktif kembali" error={errors.effective_date}>
                <input
                    className={inputClass}
                    type="date"
                    value={data.effective_date}
                    onChange={(event) =>
                        setData("effective_date", event.target.value)
                    }
                    required
                />
            </Field>
            <Field label="Alasan pengaktifan" error={errors.reason}>
                <input
                    className={inputClass}
                    value={data.reason}
                    onChange={(event) => setData("reason", event.target.value)}
                    required
                />
            </Field>
            <div className="sm:col-span-2">
                <Button disabled={processing} type="submit">
                    {processing ? "Menyimpan..." : "Aktifkan Kembali"}
                </Button>
            </div>
        </form>
    );
}
function StatusForm({
    employeeId,
    onDone,
}: {
    employeeId: number;
    onDone: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        status: "RESIGNED",
        effective_date: "",
        reason: "",
    });
    return (
        <form
            className="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2"
            onSubmit={(event) => {
                event.preventDefault();
                post(route("employees.status", employeeId), {
                    onSuccess: onDone,
                });
            }}
        >
            <label className="text-sm font-medium text-slate-700">
                Status akhir
                <select
                    className={inputClass}
                    value={data.status}
                    onChange={(event) => setData("status", event.target.value)}
                >
                    <option value="RESIGNED">Resign</option>
                    <option value="TERMINATED">Terminasi</option>
                    <option value="INACTIVE">Tidak Aktif</option>
                </select>
            </label>
            <Field label="Tanggal berlaku" error={errors.effective_date}>
                <input
                    className={inputClass}
                    type="date"
                    value={data.effective_date}
                    onChange={(event) =>
                        setData("effective_date", event.target.value)
                    }
                    required
                />
            </Field>
            <Field label="Alasan" error={errors.reason}>
                <input
                    className={inputClass}
                    value={data.reason}
                    onChange={(event) => setData("reason", event.target.value)}
                    required
                />
            </Field>
            <div className="flex items-end">
                <Button disabled={processing} type="submit" variant="secondary">
                    {processing ? "Menyimpan..." : "Simpan Status"}
                </Button>
            </div>
        </form>
    );
}
function HistoryCard({
    assignments,
    histories,
}: {
    assignments: Assignment[];
    histories: StatusHistory[];
}) {
    return (
        <>
            <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                    <BriefcaseBusiness size={18} />
                    Riwayat Kerja
                </h2>
                <div className="mt-5 space-y-4">
                    {assignments.length ? (
                        assignments.map((assignment) => (
                            <div
                                className="border-l-2 border-brand-soft pl-4"
                                key={assignment.id}
                            >
                                <p className="font-semibold text-slate-800">
                                    {assignment.division} -{" "}
                                    {assignment.position ??
                                        "Jabatan belum ditentukan"}
                                </p>
                                <p className="mt-1 text-sm text-slate-500">
                                    {assignment.branch}
                                    {assignment.sub_division
                                        ? ` - ${assignment.sub_division}`
                                        : ""}{" "}
                                    - {assignment.start_date}{" "}
                                    {assignment.end_date
                                        ? `sampai ${assignment.end_date}`
                                        : "hingga sekarang"}
                                </p>
                                {assignment.reason && (
                                    <p className="mt-1 text-sm text-slate-600">
                                        {assignment.reason}
                                    </p>
                                )}
                            </div>
                        ))
                    ) : (
                        <p className="py-4 text-sm text-slate-500">
                            Belum ada riwayat kerja.
                        </p>
                    )}
                </div>
            </section>
            <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                    <History size={18} />
                    Riwayat Status
                </h2>
                <div className="mt-5 space-y-3">
                    {histories.length ? (
                        histories.map((history) => (
                            <div
                                className="flex justify-between gap-4 border-b border-slate-100 pb-3 text-sm"
                                key={history.id}
                            >
                                <div>
                                    <p className="font-medium text-slate-800">
                                        {history.label}
                                    </p>
                                    {history.reason && (
                                        <p className="mt-1 text-slate-500">
                                            {history.reason}
                                        </p>
                                    )}
                                </div>
                                <span className="whitespace-nowrap text-slate-500">
                                    {history.effective_date}
                                </span>
                            </div>
                        ))
                    ) : (
                        <p className="py-4 text-sm text-slate-500">
                            Belum ada riwayat status.
                        </p>
                    )}
                </div>
            </section>
        </>
    );
}
function ActivityTimeline({ activities }: { activities: Activity[] }) {
    return (
        <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                <History size={18} />
                Aktivitas Karyawan
            </h2>
            <p className="mt-1 text-sm text-slate-500">
                Rangkaian penempatan, status, penilaian, dan catatan masalah
                sesuai izin Anda.
            </p>
            <div className="relative mt-5 space-y-5 before:absolute before:bottom-2 before:left-[5px] before:top-2 before:w-px before:bg-slate-200">
                {activities.length ? (
                    activities.map((activity) => (
                        <article className="relative pl-7" key={activity.id}>
                            <span
                                className={
                                    "absolute left-0 top-1.5 size-[11px] rounded-full ring-4 ring-white " +
                                    activityColors[activity.type]
                                }
                            />
                            <div className="flex flex-col justify-between gap-1 sm:flex-row sm:items-start">
                                <div>
                                    <p className="text-sm font-semibold text-slate-800">
                                        {activity.title}
                                    </p>
                                    {activity.description && (
                                        <p className="mt-1 text-sm text-slate-500">
                                            {activity.description}
                                        </p>
                                    )}
                                </div>
                                <time className="whitespace-nowrap text-xs text-slate-400">
                                    {activity.date}
                                </time>
                            </div>
                        </article>
                    ))
                ) : (
                    <p className="py-4 text-sm text-slate-500">
                        Belum ada aktivitas yang dapat ditampilkan.
                    </p>
                )}
            </div>
        </section>
    );
}

const activityColors = {
    ASSIGNMENT: "bg-blue-500",
    STATUS: "bg-slate-500",
    INCIDENT: "bg-red-500",
    EVALUATION: "bg-brand",
};

function DocumentsCard({
    employeeId,
    documents,
    canUpload,
}: {
    employeeId: number;
    documents: Document[];
    canUpload: boolean;
}) {
    const form = useForm<{ category: string; document: File | null }>({
        category: "LAINNYA",
        document: null,
    });
    const [deleteTarget, setDeleteTarget] = useState<Document | null>(null);
    const [previewTarget, setPreviewTarget] = useState<Document | null>(null);
    const [deleting, setDeleting] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) return;
        setDeleting(true);
        router.delete(route("employees.documents.destroy", deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    const previewUrl = previewTarget
        ? route("employees.documents.preview", previewTarget.id)
        : null;
    const downloadUrl = (doc: Document) => route("employees.documents.download", doc.id);
    const isPreviewable = (doc: Document) =>
        doc.mime_type.startsWith("image/") || doc.mime_type === "application/pdf";

    return (
        <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <h2 className="flex items-center gap-2 font-semibold text-slate-900">
                    <FileText size={18} />
                    Dokumen Pribadi
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                    Dokumen hanya dapat diakses pengguna yang memiliki izin dan
                    cakupan karyawan ini.
                </p>
            </div>
            {canUpload && (
                <form
                    className="mt-5 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[180px_1fr_auto]"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(
                            route("employees.documents.store", employeeId),
                            {
                                forceFormData: true,
                                preserveScroll: true,
                                onSuccess: () => form.reset("document"),
                            },
                        );
                    }}
                >
                    <select
                        className={inputClass}
                        value={form.data.category}
                        onChange={(event) =>
                            form.setData("category", event.target.value)
                        }
                    >
                        <option value="KONTRAK">Kontrak</option>
                        <option value="SERTIFIKAT">Sertifikat</option>
                        <option value="IDENTITAS">Identitas</option>
                        <option value="LAINNYA">Lainnya</option>
                    </select>
                    <input
                        accept=".pdf,.jpg,.jpeg,.png"
                        className="block min-h-10 w-full rounded-lg border border-slate-300 bg-white text-sm file:mr-3 file:min-h-10 file:border-0 file:bg-slate-100 file:px-3 file:text-sm file:font-semibold"
                        onChange={(event) =>
                            form.setData(
                                "document",
                                event.target.files?.[0] ?? null,
                            )
                        }
                        required
                        type="file"
                    />
                    <Button
                        disabled={form.processing || !form.data.document}
                        type="submit"
                    >
                        <Upload size={16} />
                        Unggah
                    </Button>
                    {form.errors.document && (
                        <span className="text-xs text-red-600 sm:col-span-3">
                            {form.errors.document}
                        </span>
                    )}
                </form>
            )}
            <div className="mt-5 divide-y divide-slate-100">
                {documents.length ? (
                    documents.map((document) => (
                        <div
                            className="flex flex-col justify-between gap-3 py-3 sm:flex-row sm:items-center"
                            key={document.id}
                        >
                            <div className="min-w-0">
                                <p className="truncate text-sm font-semibold text-slate-800">
                                    {document.name}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {document.category} ·{" "}
                                    {formatBytes(document.size)}
                                    {document.uploaded_by
                                        ? " · " + document.uploaded_by
                                        : ""}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                {isPreviewable(document) && (
                                    <button
                                        type="button"
                                        aria-label="Lihat dokumen"
                                        className="flex size-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                        onClick={() => setPreviewTarget(document)}
                                    >
                                        <Eye size={16} />
                                    </button>
                                )}
                                <Link
                                    aria-label="Unduh dokumen"
                                    className="flex size-10 items-center justify-center rounded-lg border border-slate-200 text-brand hover:bg-brand-subtle"
                                    href={route(
                                        "employees.documents.download",
                                        document.id,
                                    )}
                                >
                                    <Download size={16} />
                                </Link>
                                {document.can_delete && (
                                    <button
                                        type="button"
                                        aria-label="Hapus dokumen"
                                        className="flex size-10 items-center justify-center rounded-lg border border-red-200 text-red-700 hover:bg-red-50"
                                        onClick={() => setDeleteTarget(document)}
                                    >
                                        <Trash2 size={16} />
                                    </button>
                                )}
                            </div>
                        </div>
                    ))
                ) : (
                    <p className="py-8 text-center text-sm text-slate-500">
                        Belum ada dokumen pribadi.
                    </p>
                )}
            </div>

            {/* Confirm Delete Dialog */}
            {deleteTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                        <div className="flex items-start gap-3">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                                <Trash2 size={18} />
                            </span>
                            <div>
                                <h2 className="font-semibold text-slate-900">Hapus Dokumen?</h2>
                                <p className="mt-1 text-sm text-slate-600">
                                    Dokumen <span className="font-medium">"{deleteTarget.name}"</span> akan dihapus secara permanen dan tidak dapat dikembalikan.
                                </p>
                            </div>
                        </div>
                        <div className="mt-5 flex justify-end gap-3">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => setDeleteTarget(null)}
                                disabled={deleting}
                            >
                                Batal
                            </Button>
                            <button
                                type="button"
                                className="inline-flex min-h-10 items-center rounded-lg bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                                onClick={confirmDelete}
                                disabled={deleting}
                            >
                                {deleting ? "Menghapus..." : "Ya, Hapus"}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Document Preview Modal */}
            {previewTarget && previewUrl && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
                    <div className="relative flex h-full max-h-[90vh] w-full max-w-4xl flex-col rounded-2xl bg-white shadow-2xl">
                        <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                            <div className="min-w-0">
                                <p className="truncate font-semibold text-slate-900">{previewTarget.name}</p>
                                <p className="text-xs text-slate-500">{previewTarget.category} · {formatBytes(previewTarget.size)}</p>
                            </div>
                            <div className="flex shrink-0 items-center gap-2">
                                <Link
                                    href={previewUrl}
                                    className="flex min-h-9 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    aria-label="Unduh"
                                >
                                    <Download size={15} />Unduh
                                </Link>
                                <button
                                    type="button"
                                    aria-label="Tutup pratinjau"
                                    className="flex size-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50"
                                    onClick={() => setPreviewTarget(null)}
                                >
                                    <X size={18} />
                                </button>
                            </div>
                        </div>
                        <div className="flex-1 overflow-auto bg-slate-100 p-4">
                            {previewTarget.mime_type === "application/pdf" ? (
                                <iframe
                                    src={previewUrl}
                                    className="h-full min-h-[60vh] w-full rounded-lg border border-slate-200 bg-white"
                                    title={previewTarget.name}
                                />
                            ) : (
                                <img
                                    src={previewUrl}
                                    alt={previewTarget.name}
                                    className="mx-auto max-h-full rounded-lg object-contain shadow"
                                />
                            )}
                        </div>
                    </div>
                </div>
            )}
        </section>
    );
}
const inputClass =
    "mt-1 h-10 w-full rounded-[9px] border-slate-300 text-sm focus:border-brand focus:ring-brand";
function formatBytes(bytes: number) {
    return bytes < 1024 * 1024
        ? Math.max(1, Math.round(bytes / 1024)) + " KB"
        : (bytes / 1024 / 1024).toFixed(1) + " MB";
}
function Info({ label, value }: { label: string; value?: string }) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {label}
            </p>
            <p className="mt-1 text-slate-700">{value || "-"}</p>
        </div>
    );
}
function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <label className="text-sm font-medium text-slate-700">
            {label}
            {children}
            {error && (
                <span className="mt-1 block text-xs text-red-600">{error}</span>
            )}
        </label>
    );
}
function Select({
    label,
    value,
    error,
    onChange,
    options,
    optional = false,
}: {
    label: string;
    value: string;
    error?: string;
    onChange: (value: string) => void;
    options: Option[];
    optional?: boolean;
}) {
    return (
        <label className="text-sm font-medium text-slate-700">
            {label}
            <select
                className={inputClass}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                required={!optional}
            >
                <option value="">
                    {optional ? "Tidak ada" : `Pilih ${label.toLowerCase()}`}
                </option>
                {options.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.name}
                    </option>
                ))}
            </select>
            {error && (
                <span className="mt-1 block text-xs text-red-600">{error}</span>
            )}
        </label>
    );
}
