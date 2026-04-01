<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientDocumentController extends Controller
{
    public function index(Patient $patient, Request $request)
    {
        $documents = $patient->documents()
            ->with('uploader:id,name')
            ->when($request->type, fn ($q, $t) => $q->where('document_type', $t))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($documents);
    }

    public function store(Patient $patient, Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|in:' . implode(',', array_keys(PatientDocument::TYPE_LABELS)),
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,txt,rtf,dicom,dcm|max:10240', // 10MB max
            'document_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:document_date',
            'notes' => 'nullable|string|max:1000',
            'is_confidential' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $path = $file->store('patient-documents/' . $patient->id, 'public');

        $document = $patient->documents()->create([
            'uploaded_by' => auth()->id(),
            'document_type' => $validated['document_type'],
            'title' => $validated['title'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_date' => $validated['document_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_confidential' => $validated['is_confidential'] ?? false,
            'source' => 'admin',
        ]);

        AuditLogger::log('document_uploaded', $document, [
            'patient_id' => $patient->id,
            'type' => $validated['document_type'],
        ]);

        return response()->json([
            'document' => $document->fresh(['uploader:id,name']),
            'message' => 'Document uploaded successfully',
        ]);
    }

    public function destroy(Patient $patient, PatientDocument $document)
    {
        if ($document->patient_id !== $patient->id) abort(403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        AuditLogger::log('document_deleted', $document, [
            'patient_id' => $patient->id,
        ]);

        return response()->json(['message' => 'Document deleted']);
    }

    /**
     * Get expiring documents across all patients.
     */
    public function expiring(Request $request)
    {
        $days = $request->input('days', 30);

        $documents = PatientDocument::expiring($days)
            ->with(['patient:id,full_name,file_number,phone', 'uploader:id,name'])
            ->orderBy('expiry_date')
            ->paginate(20);

        return response()->json($documents);
    }
}
