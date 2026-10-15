<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function store(StoreEmployeeDocumentRequest $request, Employee $employee, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('view', $employee);
        $file = $request->file('document');
        $storedName = Str::uuid().'.'.$file->guessExtension();
        $path = $file->storeAs("employee-documents/{$employee->id}", $storedName, 'local');
        $document = $employee->documents()->create([
            'uploaded_by' => $request->user()->id,
            'category' => $request->validated('category'),
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => 'local',
            'path' => $path,
        ]);
        $audit->log('employee.document.upload', $request->user(), $document, null, $document->only(['employee_id', 'category', 'mime_type', 'size']));

        return back()->with('success', 'Dokumen pribadi berhasil diunggah.');
    }

    public function download(EmployeeDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function preview(EmployeeDocument $document): Response
    {
        $this->authorize('view', $document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        $content = Storage::disk($document->disk)->get($document->path);
        $displayName = preg_replace('/[\x00-\x1F\x7F]/u', '', $document->original_name) ?: 'dokumen';
        $displayName = str_replace(['/', '\\'], '_', $displayName);
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', Str::ascii($displayName)) ?: 'dokumen';
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $displayName,
            $fallbackName,
        );

        return response($content, 200)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', $disposition)
            ->header('Cache-Control', 'private, no-store');
    }

    public function destroy(EmployeeDocument $document, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $document);

        try {
            $snapshot = $document->toArray();
            Storage::disk($document->disk)->delete($document->path);
            $document->delete();
            $audit->log('employee.document.delete', request()->user(), null, $snapshot, null);
        } catch (QueryException $e) {
            return back()->with('error', 'Dokumen tidak dapat dihapus karena masih memiliki data terkait.');
        }

        return back()->with('success', 'Dokumen pribadi berhasil dihapus.');
    }
}
