<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Services\Ai\Exceptions\AiUnavailableException;
use App\Services\Ai\Features\VisionAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PatientInsuranceController extends Controller
{
    public function index(Request $request)
    {
        $query = PatientInsurance::with([
            'patient:id,full_name,file_number,phone',
            'company:id,name_ar,name_en,code,logo',
            'plan:id,name_ar,name_en,class,coverage_percentage',
            'verifier:id,name',
        ]);

        if ($companyId = $request->input('company_id')) {
            $query->where('insurance_company_id', $companyId);
        }
        if ($request->input('verified') === '1') {
            $query->where('is_verified', true);
        }
        if ($request->input('verified') === '0') {
            $query->where('is_verified', false);
        }
        if ($request->input('expired') === '1') {
            $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                    ->orWhere('member_id', 'like', "%{$search}%")
                    ->orWhere('policy_number', 'like', "%{$search}%");
            });
        }

        $insurances = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $companies = InsuranceCompany::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        // Companies with their plans, for the add-insurance form's dependent dropdown.
        $companiesWithPlans = InsuranceCompany::active()
            ->with('plans:id,insurance_company_id,name_ar,name_en,class')
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Admin/Insurance/PatientInsurances/Index', [
            'insurances' => $insurances,
            'companies' => $companies,
            'companiesWithPlans' => $companiesWithPlans,
            'filters' => $request->only(['company_id', 'verified', 'expired', 'search']),
        ]);
    }

    /**
     * AI gap #3 — extract insurance-card fields from an uploaded photo (OCR via
     * GPT-4o vision), to pre-fill the add-insurance form. Returns structured
     * fields; never persists anything. Gated by the insurance_ocr feature flag.
     */
    public function ocr(Request $request, VisionAnalyzer $vision): JsonResponse
    {
        $request->validate(['image' => 'required|image|max:4096']);

        $file = $request->file('image');
        $dataUri = 'data:'.$file->getMimeType().';base64,'.base64_encode(file_get_contents($file->getRealPath()));

        try {
            $result = $vision->analyze('insurance_ocr', $dataUri, '', [
                'locale' => app()->getLocale(),
                'rate_key' => 'user:'.$request->user()?->id,
                'actor' => ['type' => 'user', 'id' => $request->user()?->id],
            ]);
        } catch (AiUnavailableException) {
            return response()->json([
                'ok' => false,
                'message' => app()->getLocale() === 'ar'
                    ? 'خدمة الاستخراج غير متاحة حاليًا. أدخل البيانات يدويًا.'
                    : 'Extraction is currently unavailable. Please enter the data manually.',
            ], 422);
        }

        return response()->json(['ok' => true, 'fields' => $this->parseOcrJson($result->text)]);
    }

    /** Pull the model's JSON object out of its reply and keep only known keys. */
    private function parseOcrJson(string $text): array
    {
        $allowed = ['member_id', 'policy_number', 'card_number', 'company_name', 'plan_name', 'principal_name', 'expiry_date'];

        if (preg_match('/\{.*\}/s', $text, $m)) {
            $data = json_decode($m[0], true);
            if (is_array($data)) {
                return array_filter(
                    array_intersect_key($data, array_flip($allowed)),
                    fn ($v) => $v !== null && $v !== ''
                );
            }
        }

        return [];
    }

    public function storeForPatient(Request $request, Patient $patient)
    {
        $data = $this->validateData($request);
        $data['patient_id'] = $patient->id;
        // start_date is required by the schema; default to today when the card
        // doesn't show one (coverage is treated as active from when it's added).
        $data['start_date'] = $data['start_date'] ?? now()->toDateString();

        foreach (['card_image_front', 'card_image_back'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('insurance-cards', 'local');
            }
        }

        PatientInsurance::create($data);

        return back()->with('success', 'تم إضافة التأمين');
    }

    public function update(Request $request, PatientInsurance $insurance)
    {
        $data = $this->validateData($request);

        foreach (['card_image_front', 'card_image_back'] as $field) {
            if ($request->hasFile($field)) {
                if ($insurance->$field) {
                    \App\Support\SecureMedia::delete($insurance->$field);
                }
                $data[$field] = $request->file($field)->store('insurance-cards', 'local');
            }
        }

        $insurance->update($data);

        return back()->with('success', 'تم التحديث');
    }

    public function verify(PatientInsurance $insurance)
    {
        $insurance->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم التحقق من التأمين');
    }

    public function destroy(PatientInsurance $insurance)
    {
        $insurance->update(['is_active' => false]);

        return back()->with('success', 'تم إلغاء تفعيل التأمين');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'insurance_plan_id' => 'nullable|exists:insurance_plans,id',
            'member_id' => 'required|string|max:100',
            'policy_number' => 'nullable|string|max:100',
            'card_number' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:start_date',
            'relationship' => 'required|in:self,spouse,child,parent,other',
            'principal_name' => 'nullable|string|max:255',
            'max_annual_limit' => 'nullable|numeric|min:0',
            'used_amount' => 'nullable|numeric|min:0',
            'card_image_front' => 'nullable|image|max:4096',
            'card_image_back' => 'nullable|image|max:4096',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
