<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceCompany;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InsuranceCompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = InsuranceCompany::withCount(['plans', 'patientInsurances'])
            ->when($request->search, fn ($q, $s) => $q->where('name_ar', 'like', "%{$s}%")->orWhere('name_en', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name_en')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Insurance/Companies/Index', [
            'companies' => $companies,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Insurance/Companies/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:insurance_companies,code',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('insurance-logos', 'public');
        }

        $company = InsuranceCompany::create($validated);

        AuditLogger::log('created', $company);

        return redirect()->route('admin.insurance.companies.index')
            ->with('success', 'Insurance company created successfully');
    }

    public function edit(InsuranceCompany $company)
    {
        $company->load('plans');

        return Inertia::render('Admin/Insurance/Companies/Edit', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, InsuranceCompany $company)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:insurance_companies,code,' . $company->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('insurance-logos', 'public');
        }

        $company->update($validated);

        AuditLogger::log('updated', $company);

        return redirect()->route('admin.insurance.companies.index')
            ->with('success', 'Insurance company updated');
    }

    public function destroy(InsuranceCompany $company)
    {
        if ($company->patientInsurances()->exists()) {
            return back()->with('error', 'Cannot delete — company has active patient records');
        }

        $company->delete();
        AuditLogger::log('deleted', $company);

        return back()->with('success', 'Insurance company deleted');
    }
}
