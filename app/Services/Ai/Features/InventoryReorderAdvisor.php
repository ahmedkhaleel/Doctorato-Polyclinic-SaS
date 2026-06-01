<?php

namespace App\Services\Ai\Features;

use App\Services\Ai\AiManager;
use App\Services\Ai\AiResult;
use Illuminate\Support\Facades\DB;

/**
 * Inventory reorder suggestions. Gathers low-stock items (quantity at/under the
 * reorder point) and asks the model to propose what to reorder and how much,
 * considering each item's reorder_quantity. No PHI involved.
 */
class InventoryReorderAdvisor
{
    public function __construct(private readonly AiManager $ai) {}

    public function suggest(array $options = []): AiResult
    {
        $items = $this->lowStock();
        $locale = $options['locale'] ?? app()->getLocale();

        $system = $locale === 'ar'
            ? 'أنت مسؤول مشتريات لعيادة. بناءً على قائمة المستلزمات منخفضة المخزون، اقترح ما يجب إعادة طلبه والكميات المناسبة (مع مراعاة كمية إعادة الطلب). أجب بقائمة موجزة بالعربية.'
            : 'You are a clinic procurement officer. From the low-stock list, suggest what to reorder and suitable quantities (consider each item reorder quantity). Answer as a concise list.';

        $user = "Low-stock items (JSON):\n".json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $this->ai->generate('inventory_reorder', [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], $options);
    }

    public function lowStock(int $limit = 50): array
    {
        try {
            return DB::table('supplies')
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereColumn('quantity', '<=', 'reorder_point')
                        ->orWhereColumn('quantity', '<=', 'min_quantity');
                })
                ->orderBy('quantity')
                ->limit($limit)
                ->get(['name_ar', 'name_en', 'unit', 'quantity', 'min_quantity', 'reorder_point', 'reorder_quantity', 'supplier'])
                ->map(fn ($s) => [
                    'name' => $s->name_ar ?: $s->name_en,
                    'unit' => $s->unit,
                    'quantity' => $s->quantity,
                    'min' => $s->min_quantity,
                    'reorder_point' => $s->reorder_point,
                    'reorder_qty' => $s->reorder_quantity,
                    'supplier' => $s->supplier,
                ])->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
