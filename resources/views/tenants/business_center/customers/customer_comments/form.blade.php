@php
    // هذا الجزء قد يكون موجوداً في الأعلى لعرض التعليقات الحالية عند تحميل الصفحة
    $customerComments = collect();
    try {
        if (method_exists($customer, 'comments')) {
            $customerComments = $customer
                ->comments()
                ->with(['user', 'mentions.mentionedUser'])
                ->latest()
                ->get();
            $customerComments->each(function ($comment) {
                $comment->parsed_comment_text = app(
                    \App\Services\Tenants\BusinessCenter\Customer\Comment\CustomerCommentService::class,
                )->parseMentions($comment->comment_text);
            });
        }
    } catch (\Exception $e) {
        $customerComments = collect();
    }
@endphp

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">التعليقات والمنشنات</h5>
        </div>
    </div>

    <div class="card-body">
        <!-- نموذج إضافة تعليق جديد -->
        <div class="border rounded p-3 mb-4">
            <form id="customerCommentsForm">
                @csrf
                <div class="mb-3">
                    <label for="comment_text" class="form-label">إضافة تعليق جديد</label>
                    <textarea name="comment_text" id="comment_text" class="form-control comment-textarea" rows="4" required
                        placeholder="اكتب تعليقك هنا... يمكنك استخدام @لمنشن أحد الزملاء"></textarea>
                    <div class="form-text">بين 3-2000 حرف. استخدم @اسم_المستخدم للإشارة إلى زملائك.</div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-2" id="save-comment-spinner"
                            style="display: none;"></span>
                        نشر التعليق
                    </button>
                </div>
            </form>
        </div>

        <!-- عرض التعليقات المحفوظة -->
        <div>
            <h6 class="mb-3">التعليقات السابقة</h6>
            <div id="saved-comments">
                @include('tenants.business_center.customers.customer_comments.list', [
                    'comments' => $customerComments,
                ])
            </div>
        </div>
    </div>
</div>

<!-- روابط Tribute.js -->
{{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tributejs/5.1.3/tribute.css" integrity="sha512-GnwBnXd+ZGO9CdP343MUr0jCcJXCr++JVtQRnllexRW2IDq4Zvrh/McTQjooAKnSUbXZ7wamp7AQSweTnfMVoA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/tributejs/5.1.3/tribute.min.js" integrity="sha512-KJYWC7RKz/Abtsu1QXd7VJ1IJua7P7GTpl3IKUqfa21Otg2opvRYmkui/CXBC6qeDYCNlQZ7c+7JfDXnKdILUA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> --}}
