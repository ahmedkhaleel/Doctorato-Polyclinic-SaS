<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Expense::with(['category', 'expenseItem', 'creator']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($cq) use ($search) {
                        $cq->where('name_ar', 'like', "%{$search}%")
                            ->orWhere('name_en', 'like', "%{$search}%");
                    });
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('expense_category_id', $categoryId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('expense_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('expense_date', '<=', $dateTo);
        }

        $expenses = $query->latest('expense_date')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only(['search', 'category_id', 'date_from', 'date_to']),
            'categories' => ExpenseCategory::active()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Expenses/Create', [
            'categories' => ExpenseCategory::active()->with(['expenseItems' => fn ($q) => $q->active()])->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_item_id' => 'nullable|exists:expense_items,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'receipt_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_recurring' => 'boolean',
            'recurring_period' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        if ($request->hasFile('receipt_photo')) {
            $data['receipt_photo'] = $request->file('receipt_photo')->store('uploads/expenses', 'public');
        }

        $data['created_by'] = auth()->id();

        $expense = Expense::create($data);

        AuditLogger::log('created', $expense);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function edit(Expense $expense): Response
    {
        return Inertia::render('Admin/Expenses/Edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::active()->with(['expenseItems' => fn ($q) => $q->active()])->get(),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_item_id' => 'nullable|exists:expense_items,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'receipt_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_recurring' => 'boolean',
            'recurring_period' => 'nullable|in:daily,weekly,monthly,yearly',
        ]);

        if ($request->hasFile('receipt_photo')) {
            $data['receipt_photo'] = $request->file('receipt_photo')->store('uploads/expenses', 'public');
        }

        $expense->update($data);

        AuditLogger::log('updated', $expense);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        AuditLogger::log('deleted', $expense);

        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('success', 'Expense deleted successfully.');
    }
}
