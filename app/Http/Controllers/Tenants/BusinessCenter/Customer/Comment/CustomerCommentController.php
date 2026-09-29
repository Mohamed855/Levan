<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Comment\StoreCustomerCommentRequest;
use App\Models\Tenants\BusinessCenter\Customers\Comment\CustomerComment;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Services\Tenants\BusinessCenter\Customer\Comment\CustomerCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class 
CustomerCommentController extends Controller
{
    protected CustomerCommentService $customerCommentService;

    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    | Initialize controller with required dependencies.
    */
    public function __construct(CustomerCommentService $customerCommentService)
    {
        $this->customerCommentService = $customerCommentService;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Comments List
    |--------------------------------------------------------------------------
    | Retrieve and render a list of comments for a specific customer via AJAX.
    */
    public function getCommentsList(Customer $customer): JsonResponse
    {
        try {
            $comments = $this->customerCommentService->getCustomerComments($customer->id);
            $comments->each(function ($comment) {
                $comment->parsed_comment_text = $this->customerCommentService->parseMentions($comment->comment_text);
            });

            $html = view('tenants.business_center.customers.customer_comments.list', ['comments' => $comments])->render();

            return response()->json(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء جلب البيانات'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Comment
    |--------------------------------------------------------------------------
    | Create a new comment, process mentions, and send notifications.
    */
    public function store(StoreCustomerCommentRequest $request, Customer $customer): JsonResponse
    {
        try {
            $comment = $this->customerCommentService->createComment($customer, $request->validated());
            $comment->parsed_comment_text = $this->customerCommentService->parseMentions($comment->comment_text);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة التعليق بنجاح',
                'comment' => $comment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ التعليق: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Comment
    |--------------------------------------------------------------------------
    | Delete a specific comment after verifying ownership and permissions.
    */
    public function destroy(Request $request, Customer $customer, $commentId): JsonResponse
    {
        try {
            // البحث عن التعليق فقط
            $comment = CustomerComment::where('id', $commentId)
                ->where('customer_id', $customer->id)
                ->first();

            // التحقق من وجود التعليق
            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'التعليق المطلوب غير موجود أو لا ينتمي لهذا العميل.'
                ], 404);
            }

            // الحذف مباشرة بدون أي شروط
            $this->customerCommentService->deleteComment($comment);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التعليق بنجاح.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف التعليق.'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Mentionable Users
    |--------------------------------------------------------------------------
    | Fetch a list of active users available for mentioning.
    */
    public function getMentionableUsers(): JsonResponse
    {
        try {
            $users = $this->customerCommentService->getAllMentionableUsers();
            return response()->json(['success' => true, 'users' => $users]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء جلب قائمة المستخدمين'], 500);
        }
    }
}
