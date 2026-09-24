<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttentionRuleRequest;
use App\Models\EmployeeAttentionRule;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;

class AttentionRuleController extends Controller
{
    public function store(StoreAttentionRuleRequest $request, AuditLogger $audit): RedirectResponse
    {
        $rule = EmployeeAttentionRule::query()->create([...$request->validated(), 'config' => ['statuses' => ['OPEN', 'UNDER_REVIEW']]]);
        $audit->log('attention_rule.create', $request->user(), $rule, null, $rule->toArray());

        return back()->with('success', 'Aturan perhatian berhasil ditambahkan.');
    }

    public function update(StoreAttentionRuleRequest $request, EmployeeAttentionRule $rule, AuditLogger $audit): RedirectResponse
    {
        $before = $rule->toArray();
        $rule->update([...$request->validated(), 'config' => ['statuses' => ['OPEN', 'UNDER_REVIEW']]]);
        $audit->log('attention_rule.update', $request->user(), $rule, $before, $rule->fresh()->toArray());

        return back()->with('success', 'Aturan perhatian berhasil diperbarui.');
    }
}
