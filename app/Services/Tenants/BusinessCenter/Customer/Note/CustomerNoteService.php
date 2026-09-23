<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Note;


use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Note\CustomerNote;
use Illuminate\Support\Facades\Auth;

class CustomerNoteService
{

    /*
    |--------------------------------------------------------------------------
    |  إنشاء ملاحظات متعددة للعميل
    |--------------------------------------------------------------------------
    */
    public function createMultipleNotes(Customer $customer, array $notesData): array
    {
        $createdNotes = [];

        foreach ($notesData as $noteText) {
            if (!empty(trim($noteText))) {
                $createdNotes[] = $this->createSingleNote($customer, trim($noteText));
            }
        }

        return $createdNotes;
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء ملاحظة واحدة
    |--------------------------------------------------------------------------
    */
    public function createSingleNote(Customer $customer, string $noteText): CustomerNote
    {
        return CustomerNote::create([
            'customer_id' => $customer->id,
            'created_by' => Auth::id(),
            'note' => $noteText,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | حذف ملاحظة (حذف ناعم)
    |--------------------------------------------------------------------------
    */
    public function deleteNote(CustomerNote $note): bool
    {
        return $note->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | جلب ملاحظات العميل
    |--------------------------------------------------------------------------
    */
    public function getCustomerNotes(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerNote::where('customer_id', $customerId)
            ->with(['createdBy:id,name'])
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    |  إحصائيات بسيطة للملاحظات
    |--------------------------------------------------------------------------
    */
    public function getSimpleStatistics(int $customerId): array
    {
        $notes = CustomerNote::where('customer_id', $customerId);

        return [
            'total_count' => $notes->count(),
            'active_count' => $notes->whereNull('deleted_at')->count(),
            'deleted_count' => $notes->whereNotNull('deleted_at')->count(),
            'recent_count' => $notes->where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }
}
