<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')->where('national_id', '')->update(['national_id' => null]);
        $duplicateNationalId = DB::table('employees')
            ->select('national_id')
            ->whereNotNull('national_id')
            ->groupBy('national_id')
            ->havingRaw('COUNT(*) > 1')
            ->value('national_id');
        if ($duplicateNationalId !== null) {
            throw new RuntimeException('Migration dihentikan: terdapat national_id duplikat. Perbaiki data duplikat sebelum menjalankan migration kembali.');
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->unique('national_id', 'employees_national_id_unique');
        });

        Schema::table('employee_incidents', function (Blueprint $table): void {
            $table->foreignId('employee_assignment_id')->nullable()->after('employee_id')->constrained('employee_assignments')->nullOnDelete();
            $table->string('title')->nullable()->after('category');
            $table->index(['category', 'status']);
        });

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->timestamp('closed_at')->nullable()->after('finalized_at');
        });

        Schema::table('employee_attention_rules', function (Blueprint $table): void {
            $table->string('rule_type', 40)->nullable()->after('name');
            $table->string('operator', 8)->default('>=')->after('minimum_severity');
            $table->decimal('threshold', 10, 2)->nullable()->after('operator');
            $table->json('config')->nullable()->after('threshold');
        });

        DB::table('employee_attention_rules')->whereNull('rule_type')->update([
            'rule_type' => 'INCIDENT_SEVERITY_COUNT',
            'operator' => '>=',
            'threshold' => 1,
            'config' => json_encode(['statuses' => ['OPEN', 'UNDER_REVIEW']], JSON_THROW_ON_ERROR),
        ]);

        Schema::create('incident_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('report_exports', function (Blueprint $table): void {
            $table->json('scope_snapshot')->nullable()->after('filters');
            $table->unsignedBigInteger('row_count')->nullable()->after('scope_snapshot');
            $table->timestamp('expires_at')->nullable()->after('completed_at')->index();
        });
        DB::table('report_exports')
            ->where('status', 'COMPLETED')
            ->whereNull('expires_at')
            ->orderBy('id')
            ->eachById(function ($export): void {
                $completedAt = $export->completed_at ? Carbon::parse($export->completed_at) : now();
                DB::table('report_exports')->where('id', $export->id)->update(['expires_at' => $completedAt->addDay()]);
            });

        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 64);
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->timestamps();
            $table->index(['employee_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');

        Schema::table('report_exports', function (Blueprint $table): void {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['scope_snapshot', 'row_count', 'expires_at']);
        });

        Schema::dropIfExists('incident_categories');

        Schema::table('employee_attention_rules', function (Blueprint $table): void {
            $table->dropColumn(['rule_type', 'operator', 'threshold', 'config']);
        });

        Schema::table('employee_evaluations', function (Blueprint $table): void {
            $table->dropColumn('closed_at');
        });

        Schema::table('employee_incidents', function (Blueprint $table): void {
            $table->dropIndex(['category', 'status']);
            $table->dropConstrainedForeignId('employee_assignment_id');
            $table->dropColumn('title');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique('employees_national_id_unique');
        });
    }
};
