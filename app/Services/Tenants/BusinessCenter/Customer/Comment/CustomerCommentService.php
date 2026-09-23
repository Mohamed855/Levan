<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Comment;

use App\Models\Tenants\BusinessCenter\Customers\Comment\CustomerComment;
use App\Models\Tenants\BusinessCenter\Customers\Comment\CustomerCommentMention;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Notification\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CustomerCommentService
{
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = App::make(NotificationService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Comment
    |--------------------------------------------------------------------------
    */
    public function createComment(Customer $customer, array $data): CustomerComment
    {
        return DB::transaction(function () use ($customer, $data) {
            $comment = CustomerComment::create([
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'comment_text' => $data['comment_text'],
            ]);

            $this->processMentionsAndSendNotifications($comment, $data['comment_text']);

            return $comment->load(['user', 'mentions.mentionedUser']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Process Mentions & Send Notifications
    |--------------------------------------------------------------------------
    */
    private function processMentionsAndSendNotifications(CustomerComment $comment, string $text): void
    {
        try {
            // 1. استخراج أسماء المستخدمين المذكورين
            preg_match_all('/@([^\s@]+)/', $text, $matches);

            if (empty($matches[1])) {
                // Log::info('No mentions found in comment.', ['comment_id' => $comment->id]);
                return;
            }

            $mentionedNames = array_unique($matches[1]);

            // Log::info('Mentions detected in comment.', [
            //     'comment_id' => $comment->id,
            //     'mentioned_names' => $mentionedNames
            // ]);

            // 2. البحث المرن عن المستخدمين (مطابقة جزئية)
            $usersToMention = User::where('status', 'active')
                ->where(function ($query) use ($mentionedNames) {
                    foreach ($mentionedNames as $name) {
                        $cleanName = strtolower(trim($name));

                        // البحث الجزئي في حقل name (يطابق بداية أو وسط الاسم)
                        $query->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ["%{$cleanName}%"]);

                        // البحث في حقل username إذا كان موجوداً
                        if (Schema::hasColumn('users', 'username')) {
                            $query->orWhereRaw('LOWER(TRIM(username)) LIKE ?', ["%{$cleanName}%"]);
                        }
                    }
                })
                ->get();

            // 3. إذا لم نجد بالبحث الجزئي، نحاول المطابقة الكاملة للكلمة الأولى
            if ($usersToMention->isEmpty()) {
                $usersToMention = User::where('status', 'active')
                    ->where(function ($query) use ($mentionedNames) {
                        foreach ($mentionedNames as $name) {
                            $cleanName = strtolower(trim($name));
                            // البحث في أول كلمة من الاسم
                            $query->orWhereRaw('LOWER(SUBSTRING_INDEX(TRIM(name), " ", 1)) = ?', [$cleanName]);
                        }
                    })
                    ->get();
            }

            // 4. تسجيل النتائج
            // Log::info('Database search completed.', [
            //     'comment_id' => $comment->id,
            //     'searched_names' => $mentionedNames,
            //     'found_users_count' => $usersToMention->count(),
            //     'found_users' => $usersToMention->pluck('name', 'id')->toArray()
            // ]);

            if ($usersToMention->isEmpty()) {
                // Log::warning('No active users found for mentions.', [
                //     'comment_id' => $comment->id,
                //     'mentioned_names' => $mentionedNames
                // ]);

                // عرض المستخدمين المتاحين
                $availableUsers = User::where('status', 'active')
                    ->select('id', 'name')
                    ->get();

                // Log::info('Available active users:', [
                //     'users' => $availableUsers->pluck('name', 'id')->toArray()
                // ]);

                return;
            }

            // 5. إذا وجدنا أكثر من مستخدم، نختار الأفضل مطابقة
            if ($usersToMention->count() > count($mentionedNames)) {
                // Log::info('Multiple users matched. Filtering best matches.', [
                //     'comment_id' => $comment->id,
                //     'matched_count' => $usersToMention->count()
                // ]);

                // ترتيب حسب دقة المطابقة (الأسماء الأقصر أولاً)
                $usersToMention = $usersToMention->sortBy(function ($user) {
                    return strlen($user->name);
                });
            }

            // 6. معالجة المنشنات وإرسال الإشعارات
            $mentionsToInsert = [];
            $author = auth()->user();
            $processedUserIds = []; // لتجنب التكرار

            foreach ($usersToMention as $user) {
                // تجنب المنشن الذاتي
                if ($user->id === $author->id) {
                    // Log::info('Skipping self-mention.', [
                    //     'user_id' => $user->id,
                    //     'comment_id' => $comment->id
                    // ]);
                    continue;
                }

                // تجنب تكرار نفس المستخدم
                if (in_array($user->id, $processedUserIds)) {
                    // Log::info('Skipping duplicate mention.', [
                    //     'user_id' => $user->id,
                    //     'comment_id' => $comment->id
                    // ]);
                    continue;
                }

                $processedUserIds[] = $user->id;

                // حفظ المنشن
                $mentionsToInsert[] = [
                    'customer_comment_id' => $comment->id,
                    'mentioned_user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // إرسال الإشعار
                try {
                    $this->notificationService->sendMentionNotification(
                        $user,
                        $author,
                        $comment->customer->name,
                        $comment->customer->id
                    );

                    // Log::info('✅ Mention notification sent successfully.', [
                    //     'comment_id' => $comment->id,
                    //     'from_user' => $author->name,
                    //     'from_user_id' => $author->id,
                    //     'to_user' => $user->name,
                    //     'to_user_id' => $user->id,
                    //     'customer_id' => $comment->customer->id
                    // ]);
                } catch (\Exception $e) {
                    // Log::error('❌ Failed to send mention notification.', [
                    //     'comment_id' => $comment->id,
                    //     'to_user_id' => $user->id,
                    //     'error' => $e->getMessage()
                    // ]);
                }
            }

            // 7. حفظ المنشنات في قاعدة البيانات
            if (!empty($mentionsToInsert)) {
                CustomerCommentMention::insert($mentionsToInsert);

                // Log::info('✅ Mentions saved to database.', [
                //     'comment_id' => $comment->id,
                //     'mentions_count' => count($mentionsToInsert)
                // ]);
            }
        } catch (\Exception $e) {
            // Log::error('❌ Critical error in processMentionsAndSendNotifications.', [
            //     'comment_id' => $comment->id ?? null,
            //     'error_message' => $e->getMessage(),
            //     'error_file' => $e->getFile(),
            //     'error_line' => $e->getLine(),
            //     'trace' => $e->getTraceAsString()
            // ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer Comments
    |--------------------------------------------------------------------------
    */
    public function getCustomerComments(int $customerId): Collection
    {
        return CustomerComment::with(['user', 'mentions.mentionedUser'])
            ->where('customer_id', $customerId)
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Comment - حذف مباشر بدون شروط
    |--------------------------------------------------------------------------
    */
    public function deleteComment(CustomerComment $comment): bool
    {
        return DB::transaction(function () use ($comment) {
            try {
                $deletedMentions = $comment->mentions()->delete();

                // Log::info('Comment mentions deleted.', [
                //     'comment_id' => $comment->id,
                //     'deleted_mentions_count' => $deletedMentions
                // ]);

                // 2. حذف التعليق نفسه
                $deleted = $comment->delete();

                // Log::info('Comment deleted successfully.', [
                //     'comment_id' => $comment->id,
                //     'customer_id' => $comment->customer_id,
                //     'deleted_by' => auth()->id()
                // ]);

                return $deleted;
            } catch (\Exception $e) {
                // Log::error('Error in deleteComment method.', [
                //     'comment_id' => $comment->id,
                //     'error' => $e->getMessage(),
                //     'trace' => $e->getTraceAsString()
                // ]);

                throw $e;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Mentionable Users
    |--------------------------------------------------------------------------
    */
    public function getAllMentionableUsers(): Collection
    {
        return User::select('id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->name),
                    'display' => '@' . trim($user->name)
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Parse Mentions
    |--------------------------------------------------------------------------
    */
    public function parseMentions(string $text): string
    {
        $users = User::where('status', 'active')
            ->get(['id', 'name'])
            ->keyBy(function ($user) {
                return strtolower(trim($user->name));
            });

        return preg_replace_callback(
            '/@([^\s@]+)/',
            function ($matches) use ($users) {
                $username = strtolower(trim($matches[1]));
                $user = $users->get($username);

                if ($user) {
                    $profileUrl = tenant_route_name('users.show', $user->id);
                    return '<a href="' . $profileUrl . '" class="mention-link text-primary fw-bold" target="_blank">@' . htmlspecialchars($user->name) . '</a>';
                }

                return $matches[0];
            },
            $text
        );
    }
}
