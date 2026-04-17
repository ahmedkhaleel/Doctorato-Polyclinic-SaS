<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Models\InsurancePlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InsurancePlanController extends Controller
{
    public function index(Request $request)
    {
        $query = InsurancePlan::with('company:id,name_ar,name_en,code,logo')
            ->withCount('patientInsurances');

        if ($companyId = $request->input('company_id')) {
            $query->where('insurance_company_id', $companyId);
        }
        if ($class = $request->input('class')) {
            $query->where('class', $class);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('plan_code', 'like', "%{$search}%");
            });
        }

        $plans = $query->orderBy('insurance_company_id')->orderBy('class')->paginate(20)->withQueryString();
        $companies = InsuranceCompany::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Admin/Insurance/Plans/Index', [
            'plans' => $plans,
            'companies' => $companies,
            'filters' => $request->only(['company_id', 'class', 'search']),
        ]);
    }

    public function create()
    {
        $companies = InsuranceCompany::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        return Inertia::render('Admin/Insurance/Plans/Form', [
            'companies' => $companies,
            'plan' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        InsurancePlan::create($data);
        return redirect()->route('admin.insurance.plans.index')->with('success', 'تم إنشاء الباقة بنجاح');
    }

    public function edit(InsurancePlan $plan)
    {
        $companies = InsuranceCompany::active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        return Inertia::render('Admin/Insurance/Plans/Form', [
            'companies' => $companies,
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, InsurancePlan $plan)
    {
        $data = $this->validateData($request);
        $plan->update($data);
        return redirect()->route('admin.insurance.plans.index')->with('success', 'تم تحديث الباقة');
    }

    public function destroy(InsurancePlan $plan)
    {
        if ($plan->patientInsurances()->exists()) {
            return back()->with('error', 'لا يمكن حذف باقة مستخدمة بواسطة مرضى');
        }
        $plan->delete();
        return back()->with('success', 'تم الحذف');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'plan_code' => 'nullable|string|max:50',
            'class' => 'required|in:VIP,A,B,C,D,E',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'max_coverage_amount' => 'nullable|numeric|min:0',
            'copay_amount' => 'nullable|numeric|min:0',
            'deductible' => 'nullable|numeric|min:0',
            'covers_dental' => 'nullable|boolean',
            'covers_dermatology' => 'nullable|boolean',
            'covers_cosmetic' => 'nullable|boolean',
            'covers_lab' => 'nullable|boolean',
            'covers_xray' => 'nullable|boolean',
            'covers_medication' => 'nullable|boolean',
            'exclusions' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
