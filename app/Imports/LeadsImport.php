<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadScoringRule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LeadsImport implements ToCollection, WithHeadingRow
{
    protected int $userId;
    protected array $columnMap;
    protected int $imported = 0;
    protected int $skipped = 0;
    protected array $errors = [];

    public function __construct(int $userId, array $columnMap = [])
    {
        $this->userId = $userId;
        $this->columnMap = $columnMap;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 because heading row is 1, then data starts at 2

            try {
                $data = $this->mapRow($row);

                // Skip if no name or phone
                if (empty($data['full_name']) || empty($data['phone'])) {
                    $this->skipped++;
                    $this->errors[] = [
                        'row' => $rowNum,
                        'message_en' => 'Missing required field: name or phone.',
                        'message_ar' => 'حقل مطلوب مفقود: الاسم أو الهاتف.',
                    ];
                    continue;
                }

                // Clean phone
                $data['phone'] = preg_replace('/[^0-9+]/', '', $data['phone']);

                // Check duplicate
                $existing = Lead::where('phone', $data['phone'])->first();
                if ($existing) {
                    $this->skipped++;
                    $this->errors[] = [
                        'row' => $rowNum,
                        'message_en' => "Duplicate phone: {$data['phone']} (Lead: {$existing->full_name})",
                        'message_ar' => "رقم مكرر: {$data['phone']} (العميل: {$existing->full_name})",
                    ];
                    continue;
                }

                // Normalize priority
                $data['priority'] = $this->normalizePriority($data['priority'] ?? null);

                // Normalize gender
                $data['gender'] = $this->normalizeGender($data['gender'] ?? null);

                // Set defaults
                $data['status'] = 'new';
                $data['created_by'] = $this->userId;
                $data['assigned_to'] = $this->userId;
                $data['assigned_at'] = now();
                $data['score'] = 0;

                // Clean optional fields
                if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    unset($data['email']);
                }

                $lead = Lead::create($data);

                // Apply scoring
                LeadScoringRule::applyToLead($lead, 'lead_created');

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'type' => 'system',
                    'subject' => 'Lead imported from Excel',
                    'performed_by' => $this->userId,
                ]);

                $this->imported++;
            } catch (\Exception $e) {
                $this->skipped++;
                $this->errors[] = [
                    'row' => $rowNum,
                    'message_en' => 'Error: ' . $e->getMessage(),
                    'message_ar' => 'خطأ: ' . $e->getMessage(),
                ];
            }
        }
    }

    protected function mapRow($row): array
    {
        $data = [];
        $fields = ['full_name', 'phone', 'phone2', 'email', 'gender', 'date_of_birth', 'city', 'nationality', 'priority', 'notes'];

        foreach ($fields as $field) {
            $column = $this->columnMap[$field] ?? $field;
            $data[$field] = $this->getValueFromRow($row, $column);
        }

        return $data;
    }

    protected function getValueFromRow($row, $column)
    {
        if (empty($column)) return null;

        // Try exact key match
        if (isset($row[$column])) {
            $val = $row[$column];
            return is_string($val) ? trim($val) : $val;
        }

        // Try lowercase/normalized
        $normalized = strtolower(str_replace([' ', '-', '_'], '', $column));
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace([' ', '-', '_'], '', (string) $key));
            if ($normalizedKey === $normalized) {
                return is_string($value) ? trim($value) : $value;
            }
        }

        return null;
    }

    protected function normalizePriority($value): int
    {
        if (is_numeric($value) && in_array((int) $value, [1, 2, 3])) {
            return (int) $value;
        }

        $lower = strtolower(trim((string) $value));

        $hotWords = ['hot', '1', 'high', 'ساخن', 'عالي', 'مرتفع'];
        $warmWords = ['warm', '2', 'medium', 'دافئ', 'متوسط'];

        if (in_array($lower, $hotWords)) return 1;
        if (in_array($lower, $warmWords)) return 2;
        return 3; // Default: cold
    }

    protected function normalizeGender($value): ?string
    {
        if (empty($value)) return null;

        $lower = strtolower(trim((string) $value));

        $male = ['male', 'm', 'ذكر', 'رجل'];
        $female = ['female', 'f', 'أنثى', 'امرأة', 'انثى'];

        if (in_array($lower, $male)) return 'male';
        if (in_array($lower, $female)) return 'female';
        return null;
    }

    public function getImported(): int { return $this->imported; }
    public function getSkipped(): int { return $this->skipped; }
    public function getErrors(): array { return $this->errors; }
}
