<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizational_scopes', function (Blueprint $table): void {
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('division_id')->references('id')->on('divisions')->nullOnDelete();
            $table->foreign('sub_division_id')->references('id')->on('sub_divisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('organizational_scopes', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['division_id']);
            $table->dropForeign(['sub_division_id']);
        });
    }
};
