<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\LogsActivity;

class Expense extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'expense_category_id', 'expense_item_id', 'amount', 'expense_date',
        'description', 'receipt_photo', 'is_recurring', 'recurring_period', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    protected $appends = ['receipt_url'];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function expenseItem()
    {
        return $this->belongsTo(ExpenseItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function receiptUrl(): Attribute
    {
        return Attribute::get(fn () => $this->receipt_photo ? '/storage/' . $this->receipt_photo : null);
    }
}
