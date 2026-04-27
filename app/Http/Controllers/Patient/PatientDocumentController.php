<?php

namespace App\Http\Controllers\Patient;

use App\Models\PatientDocument;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Patient self-service medical document management.
 *
 * Patients can upload their own documents (lab reports done at external
 * labs, prior medical history from other clinics, insurance cards, etc.)
 * and download/delete only the ones they uploaded themselves.
 *
 * Admin-uploaded documents are visible (so the patient sees what's on
 * file) but cannot be deleted by the patient.
 *
 * Highly-restricted types (consent forms, prescriptions, signed_consent)
 * are excluded — those flow through dedicated endpoints with their own
 * authorization rules.
 */
class PatientDocumentController extends BasePatientController
{
    /** Document types patients are allowed to upload themselves. */
    private const ALLOWED_TYPES = [
        'medical_report'    => ['ar' => 'تقرير طبي',     'en' => 'Medical Report'],
        'lab_result'        => ['ar' => 'نتيجة مختبر',   'en' => 'Lab Result'],
        'xray_report'       => ['ar' => 'تقرير أشعة',    'en' => 'X-ray Report'],
        'referral_letter'   => ['ar' => 'خطاب إحالة',    'en' => 'Referral Letter'],
        'discharge_summary' => ['ar' => 'ملخص خروج',     'en' => 'Discharge Summary'],
        'insurance_card'    => ['ar' => 'بطاقة تأمين',   'en' => 'Insurance Card'],
        'other'             => ['ar' => 'أخرى',          'en' => 'Other'],
    ];

    public function index(Request $request): Response
    {
        $patient = $this->patient($request);

        // Patient sees ALL non-confidential documents on their file
        // (their own uploads + admin uploads), but only their own
        // uploads can be deleted.
        $documents = $patient->documents()
            ->where('is_confidential', false)
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn ($d) => [
                'id'             => $d->id,
                'document_type'  => $d->document_type,
                'type_label'     => $d->type_label,
                'title'          => $d->title,
                'original_name'  => $d->original_name,
                'mime_type'      => $d->mime_type,
                'file_size'      => $d->file_size,
                'document_date'  => $d->document_date?->toDateString(),
                'expiry_date'    => $d->expiry_date?->toDateString(),
                'is_expired'     => $d->is_expired,
                'notes'          => $d->notes,
                'source'         => $d->source,
                'uploaded_by_me' => $d->source === 'patient_upload',
                'uploader_name'  => $d->uploader?->name,
                'created_at'     => $d->created_at?->toDateString(),
            ]);

        return Inertia::render('Patient/Documents/Index', [
            'documents'    => $documents,
            'allowedTypes' => self::ALLOWED_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $patient = $this->patient($request);

        $data = $request->validate([
            'document_type'  => 'required|string|in:' . implode(',', array_keys(self::ALLOWED_TYPES)),
            'title'          => 'required|string|max:255',
            'file'           => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240', // 10 MB
            'document_date'  => 'nullable|date|before_or_equal:today',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $path = $file->store('patient-documents/' . $patient->id, 'public');

        $document = $patient->documents()->create([
            'uploaded_by'     => $request->user()->id, // the User row, not the Patient
            'document_type'   => $data['document_type'],
            'title'           => $data['title'],
            'file_path'       => $path,
            'original_name'   => $file->getClientOriginalName(),
            'mime_type'       => $file->getMimeType(),
            'file_size'       => $file->getSize(),
            'document_date'   => $data['document_date'] ?? null,
            'notes'           => $data['notes'] ?? null,
            'is_confidential' => false,
            'source'          => 'patient_upload',
        ]);

        AuditLogger::log('patient_document_uploaded', $document, [
            'patient_id' => $patient->id,
            'type'       => $data['document_type'],
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function download(Request $request, string $locale, PatientDocument $document): StreamedResponse
    {
        $patient = $this->patient($request);

        // Defense in depth: only the owning patient can download, and
        // never confidential docs even if URL-guessed.
        if ($document->patient_id !== $patient->id) abort(403);
        if ($document->is_confidential) abort(403);

        if (! Storage::disk('public')->exists($document->file_path)) abort(404);

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_name ?: 'document'
        );
    }

    public function destroy(Request $request, string $locale, PatientDocument $document): RedirectResponse
    {
        $patient = $this->patient($request);

        if ($document->patient_id !== $patient->id) abort(403);
        // Patients can only delete their own uploads — never staff uploads.
        if ($document->source !== 'patient_upload') abort(403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        AuditLogger::log('patient_document_deleted', $document, [
            'patient_id' => $patient->id,
        ]);

        return back()->with('success', 'Document deleted.');
    }
}
