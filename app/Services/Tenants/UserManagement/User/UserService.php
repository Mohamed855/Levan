<?php

namespace App\Services\Tenants\UserManagement\User;

use App\Models\Tenants\Branch\Branch;
use App\Models\Tenants\FinancialCollection\CollectionProcess\CollectionProcessSession;
use App\Models\Tenants\GeneralSetting\Branch\SettingsBranch;
use App\Models\Tenants\UserMangment\User\User;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserService
{
    /*
    |--------------------------------------------------------------------------
    | User Creation
    |--------------------------------------------------------------------------
    | Handle user creation with all related operations.
    */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $roleId = $data['role_id'] ?? null;
            unset($data['role_id']);

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $this->handleImageUpload($data['image']);
            }

            $data['password'] = Hash::make($data['password']);
            $data['status'] = $data['status'] ?? 'active';

            $user = User::create($data);

            if ($roleId) {
                $role = Role::findOrFail($roleId);
                $user->syncRoles([$role->name]);
            }

            return $user;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | User Update
    |--------------------------------------------------------------------------
    | Handle user update with all related operations.
    */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {

            $roleId = $data['role_id'] ?? null;
            unset($data['role_id']);

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                if ($user->image) {
                    $this->deleteImage($user->image);
                }
                $data['image'] = $this->handleImageUpload($data['image']);
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            if ($roleId) {
                $role = Role::findOrFail($roleId);
                $user->syncRoles([$role->name]);
            }

            return $user->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | User Deletion
    |--------------------------------------------------------------------------
    | Handle user deletion with cleanup.
    */
    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Delete user image
            if ($user->image) {
                $this->deleteImage($user->image);
            }

            // Delete user
            return $user->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Image Upload Handling
    |--------------------------------------------------------------------------
    | Handle image upload with proper naming and storage.
    */
    private function handleImageUpload(UploadedFile $image): string
    {
        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        // Store image
        return $image->storeAs('users', $filename, 'tenant_public');
    }

    /*
    |--------------------------------------------------------------------------
    | Image Deletion
    |--------------------------------------------------------------------------
    | Delete image from storage.
    */
    private function deleteImage(string $imagePath): bool
    {
        return Storage::disk('tenant_public')->delete($imagePath);
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Statistics
    |--------------------------------------------------------------------------
    | Get user statistics for dashboard.
    */
    public function getUserStatistics(): array
    {
        return [
            'total' => User::accessible()->count(),
            'active' => User::accessible()->where('status', 'active')->count(),
            'inactive' => User::accessible()->where('status', 'inactive')->count(),
            'archived' => User::onlyTrashed()->accessible()->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Branches
    |--------------------------------------------------------------------------
    | Get all active branches for user assignment.
    */
    public function getActiveBranches()
    {
        return Branch::where('status', 'active')
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Next Employee Number
    |--------------------------------------------------------------------------
    | Generates the next sequential employee number based on the last user.
    */
    public function generateNextEmployeeNumber(): string
    {
        $prefix = 'MSHL';
        $nextNumber = 1;

        $lastUser = User::latest('id')->first();

        if ($lastUser && !empty($lastUser->employee_number) && str_starts_with($lastUser->employee_number, $prefix)) {
            $lastNumber = (int) substr($lastUser->employee_number, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        }
        $paddedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        return $prefix . $paddedNumber;
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Sessions Data
    |--------------------------------------------------------------------------
    | جلب بيانات جلسات التحصيل للموظف من بداية الشهر حتى اليوم
    */
    public function getUserSessionsData(int $userId): array
    {
        if (!Schema::hasTable('collection_process_sessions')) {
            return [
                'days' => [],
                'statistics' => [
                    'attendance_days' => 0,
                    'total_work_hours' => '0:00',
                    'total_work_minutes' => 0,
                    'avg_work_hours' => '0:00',
                    'total_follows' => 0,
                    'avg_follows' => 0,
                ],
            ];
        }

        $startOfMonth = Carbon::now()->startOfMonth();
        $today = Carbon::today();

        // جلب جميع الجلسات من بداية الشهر حتى اليوم
        $sessions = CollectionProcessSession::with(['workPeriods' => function ($q) {
            $q->orderBy('entry_time', 'asc');
        }])
            ->where('user_id', $userId)
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $today->format('Y-m-d')])
            ->orderBy('date', 'asc')
            ->get();

        $daysData = [];
        $totalWorkMinutes = 0;
        $totalFollows = 0;

        foreach ($sessions as $session) {
            $date = Carbon::parse($session->date);

            // تخطي السبت والجمعة
            if ($date->dayOfWeek === Carbon::SATURDAY || $date->dayOfWeek === Carbon::FRIDAY) {
                continue;
            }

            // حساب إجمالي الوقت
            $workMinutes = $session->total_work_minutes;

            // إضافة الوقت الحالي إذا كان نشطاً
            if ($session->is_active && $date->isToday()) {
                $openPeriod = $session->workPeriods->whereNull('exit_time')->first();
                if ($openPeriod) {
                    $workMinutes += $openPeriod->entry_time->diffInMinutes(now());
                }
            }

            $totalWorkMinutes += $workMinutes;
            $totalFollows += $session->total_follows ?? 0;

            // إنشاء Timeline للخط
            $timeline = $this->buildDayTimeline($session, $date);

            $daysData[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->locale('ar')->dayName,
                'day_number' => $date->format('d'),
                'has_session' => true,
                'is_active' => $session->is_active,
                'is_today' => $date->isToday(),
                'work_hours' => $this->formatMinutesToTime($workMinutes),
                'work_minutes' => $workMinutes,
                'follows_count' => $session->total_follows ?? 0,
                'timeline' => $timeline,
            ];
        }

        // حساب المتوسطات
        $attendanceDays = count($daysData);
        $avgWorkMinutes = $attendanceDays > 0 ? round($totalWorkMinutes / $attendanceDays) : 0;
        $avgFollows = $attendanceDays > 0 ? round($totalFollows / $attendanceDays, 1) : 0;

        return [
            'days' => $daysData,
            'statistics' => [
                'attendance_days' => $attendanceDays,
                'total_work_hours' => $this->formatMinutesToTime($totalWorkMinutes),
                'total_work_minutes' => $totalWorkMinutes,
                'avg_work_hours' => $this->formatMinutesToTime($avgWorkMinutes),
                'total_follows' => $totalFollows,
                'avg_follows' => $avgFollows,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Build Day Timeline
    |--------------------------------------------------------------------------
    | بناء الخط الزمني لليوم (فترات العمل والانقطاع)
    */
    private function buildDayTimeline($session, $date): array
    {
        $timeline = [];

        if ($session->workPeriods->isEmpty()) {
            return [
                'periods' => [],
                'work_range' => ['start' => '08:00', 'end' => '16:00']
            ];
        }

        // الحصول على أول دخول وآخر خروج
        $firstEntry = $session->workPeriods->min('entry_time');
        $lastExit = $session->workPeriods->whereNotNull('exit_time')->max('exit_time');

        // إذا كان لا يزال نشطاً، استخدم الوقت الحالي
        if (!$lastExit || $session->is_active) {
            $lastExit = now();
        }

        $workStart = Carbon::parse($firstEntry)->startOfHour();
        $workEnd = Carbon::parse($lastExit)->addHour()->startOfHour();
        $totalMinutes = $workStart->diffInMinutes($workEnd);

        if ($totalMinutes <= 0) {
            $totalMinutes = 480;
        }

        $periods = [];
        foreach ($session->workPeriods as $period) {
            $entryTime = Carbon::parse($period->entry_time);
            $exitTime = $period->exit_time ? Carbon::parse($period->exit_time) : now();

            // حساب النسبة المئوية من بداية العمل
            $startPercent = max(0, $entryTime->diffInMinutes($workStart) / $totalMinutes * 100);
            $endPercent = min(100, $exitTime->diffInMinutes($workStart) / $totalMinutes * 100);
            $width = $endPercent - $startPercent;

            if ($width > 0) {
                $duration = $entryTime->diffInMinutes($exitTime);
                $durationFormatted = sprintf('%d دقيقة', $duration);
                if ($duration >= 60) {
                    $hours = floor($duration / 60);
                    $mins = $duration % 60;
                    $durationFormatted = sprintf('%d:%02d ساعة', $hours, $mins);
                }

                $periods[] = [
                    'start_percent' => round($startPercent, 2),
                    'width' => round($width, 2),
                    'start_time' => $entryTime->format('H:i'),
                    'end_time' => $period->exit_time ? $exitTime->format('H:i') : 'الآن',
                    'duration' => $durationFormatted,
                    'is_active' => !$period->exit_time,
                ];
            }
        }

        return [
            'periods' => $periods,
            'work_range' => [
                'start' => $workStart->format('H:i'),
                'end' => $workEnd->format('H:i'),
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Format Minutes to Time
    |--------------------------------------------------------------------------
    | تحويل الدقائق إلى صيغة HH:MM
    */
    private function formatMinutesToTime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }

    /*
    |--------------------------------------------------------------------------
    | Update User Image
    |--------------------------------------------------------------------------
    | Update only the user profile image.
    */
    public function updateUserImage(User $user, $image): User
    {
        return DB::transaction(function () use ($user, $image) {
            if ($user->image) {
                $this->deleteImage($user->image);
            }

            $imagePath = $this->handleImageUpload($image);
            $user->update(['image' => $imagePath]);

            return $user->fresh();
        });
    }


    public function updateBackgroundImage(User $user, $image): User
    {
        return DB::transaction(function () use ($user, $image) {
            if ($user->background_image) {
                $this->deleteImage($user->background_image);
            }

            $imagePath = $this->handleImageUpload($image);
            $user->update(['background_image' => $imagePath]);

            return $user->fresh();
        });
    }
}
