<?php

namespace App\Data\Tenants\FinancialCollection\Target;

class UpdateCollectionTargetDTO
{
    public int $user_id;
    public string $target_type;
    public float $target_amount;
    public ?string $status;

    // Fields for monthly targets
    public ?int $target_month;
    public ?int $target_year;

    // Fields for custom targets
    public ?string $custom_start_date;
    public ?string $custom_end_date;

    public function __construct(
        int $user_id,
        string $target_type,
        float $target_amount,
        ?string $status = null,
        ?int $target_month = null,
        ?int $target_year = null,
        ?string $custom_start_date = null,
        ?string $custom_end_date = null
    ) {
        $this->user_id = $user_id;
        $this->target_type = $target_type;
        $this->target_amount = $target_amount;
        $this->status = $status;
        $this->target_month = $target_month;
        $this->target_year = $target_year;
        $this->custom_start_date = $custom_start_date;
        $this->custom_end_date = $custom_end_date;
    }

    /**
     * Create DTO from request array
     */
    public static function fromArray(array $data): self
    {
        // Clean target amount
        $targetAmount = isset($data['target_amount'])
            ? (float) str_replace([',', ' '], '', $data['target_amount'])
            : 0;

        return new self(
            user_id: (int) $data['user_id'],
            target_type: $data['target_type'],
            target_amount: $targetAmount,
            status: $data['status'] ?? null,
            target_month: isset($data['target_month']) ? (int) $data['target_month'] : null,
            target_year: isset($data['target_year']) ? (int) $data['target_year'] : null,
            custom_start_date: $data['custom_start_date'] ?? null,
            custom_end_date: $data['custom_end_date'] ?? null
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        $data = [
            'user_id' => $this->user_id,
            'target_type' => $this->target_type,
            'target_amount' => $this->target_amount,
        ];

        // Only include status if it's set
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }

        // Include period fields based on target type
        switch ($this->target_type) {
            case 'monthly':
                $data['target_month'] = $this->target_month;
                $data['target_year'] = $this->target_year;
                break;
            case 'yearly':
                $data['target_year'] = $this->target_year;
                break;
            case 'custom':
                $data['custom_start_date'] = $this->custom_start_date;
                $data['custom_end_date'] = $this->custom_end_date;
                break;
        }

        return $data;
    }
}
