<?php

namespace App\Data\Tenants\FinancialCollection\Collection;

class CollectionUpdateDto
{
    public function __construct(
        public readonly int $customerId,
        public readonly float $collectedAmount,
        public readonly string $collectionType,
        public readonly int $paymentMethodId,
        public readonly string $receiptNumber,
        public readonly ?string $closingCode,
        public readonly string $collectionDate,
        public readonly string $paymentType,
        public readonly mixed $attachment = null,
        public readonly bool $removeAttachment = false
    ) {}

    /**
     * تحويل البيانات إلى مصفوفة
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'collected_amount' => $this->collectedAmount,
            'collection_type' => $this->collectionType,
            'payment_method_id' => $this->paymentMethodId,
            'receipt_number' => $this->receiptNumber,
            'closing_code' => $this->closingCode,
            'collection_date' => $this->collectionDate,
            'payment_type' => $this->paymentType,
            'attachment' => $this->attachment,
            'remove_attachment' => $this->removeAttachment,
        ];
    }

    /**
     * إنشاء DTO من الطلب
     */
    public static function fromRequest(array $validatedData): self
    {
        return new self(
            customerId: $validatedData['customer_id'],
            collectedAmount: (float) $validatedData['collected_amount'],
            collectionType: $validatedData['collection_type'],
            paymentMethodId: $validatedData['payment_method_id'],
            receiptNumber: $validatedData['receipt_number'],
            closingCode: $validatedData['closing_code'] ?? null,
            collectionDate: $validatedData['collection_date'],
            paymentType: $validatedData['payment_type'],
            attachment: $validatedData['attachment'] ?? null,
            removeAttachment: $validatedData['remove_attachment'] ?? false
        );
    }
}
