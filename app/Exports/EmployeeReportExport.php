<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\User;
use App\Services\ReportQueryService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeReportExport implements FromQuery, WithColumnFormatting, WithColumnWidths, WithFreezePane, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly User $user,
        private readonly array $filters,
        private readonly array $scopeSnapshot,
    ) {}

    public function query(): Builder
    {
        return app(ReportQueryService::class)->query($this->user, $this->filters, $this->scopeSnapshot);
    }

    public function headings(): array
    {
        return [
            'No',
            'NIK / ID Karyawan',
            'Nama',
            'Cabang',
            'Divisi',
            'Sub Divisi',
            'Jabatan',
            'Tanggal Masuk',
            'Masa Kerja %',
            'Tanggung Jawab %',
            'Absensi %',
            'Inisiatif %',
            'Sikap %',
            'Komunikasi %',
            'Kerjasama Tim %',
            'Total %',
            'Kriteria Penilaian',
            'Keterangan',
            'Status',
            'Penilai',
            'Tanggal Penilaian',
        ];
    }

    public function map(mixed $row): array
    {
        /** @var Employee $row */
        $assignment = $row->currentAssignment ?? $row->latestAssignment;
        $evaluation = $row->evaluations->first();
        $scores = $evaluation?->scores->keyBy(fn ($score) => $score->component?->code) ?? collect();

        return [
            ++$this->rowNumber,
            $row->national_id ?: $row->employee_number,
            $row->full_name,
            $assignment?->branch?->name,
            $assignment?->division?->name,
            $assignment?->subDivision?->name,
            $assignment?->position?->name,
            $row->join_date ? Date::dateTimeToExcel($row->join_date) : null,
            $scores->get('MASA_KERJA')?->raw_score,
            $scores->get('TANGGUNG_JAWAB')?->raw_score,
            $scores->get('ABSENSI')?->raw_score,
            $scores->get('INISIATIF')?->raw_score,
            $scores->get('SIKAP')?->raw_score,
            $scores->get('KOMUNIKASI')?->raw_score,
            $scores->get('KERJASAMA_TIM')?->raw_score,
            $evaluation?->total_score,
            $evaluation?->criterion?->name,
            $evaluation?->notes,
            $row->current_status->label(),
            $evaluation?->evaluator?->name,
            $evaluation?->created_at ? Date::dateTimeToExcel($evaluation->created_at) : null,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => '0.00"%"',
            'J' => '0.00"%"',
            'K' => '0.00"%"',
            'L' => '0.00"%"',
            'M' => '0.00"%"',
            'N' => '0.00"%"',
            'O' => '0.00"%"',
            'P' => '0.00"%"',
            'U' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7, 'B' => 22, 'C' => 30, 'D' => 22, 'E' => 22,
            'F' => 22, 'G' => 22, 'H' => 16, 'I' => 16, 'J' => 20,
            'K' => 14, 'L' => 14, 'M' => 14, 'N' => 16, 'O' => 20,
            'P' => 14, 'Q' => 24, 'R' => 36, 'S' => 18, 'T' => 24, 'U' => 20,
        ];
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setAutoFilter('A1:U'.$sheet->getHighestRow());
        $sheet->getStyle('A1:U1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCFCE7');

        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FF0F172A']]],
        ];
    }
}
