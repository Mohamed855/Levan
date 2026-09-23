<?php

namespace App\Services\Tenants\FinancialCollection\CollectionProcess;

use App\Models\Tenants\FinancialCollection\CollectionProcess\CollectionProcessSession;
use App\Models\Tenants\FinancialCollection\CollectionProcess\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CollectionSessionService
{
    // التأكد من وجود جلسة نشطة دون تحديث
    public function ensureActiveSession(): void
    {
        $userId = Auth::id();
        $today  = Carbon::today();
        $now    = Carbon::now();

        DB::transaction(function () use ($userId, $today, $now) {
            $session = CollectionProcessSession::where('user_id', $userId)->where('date', $today)->lockForUpdate()->first();

            if (!$session) {
                $session = CollectionProcessSession::create([
                    'user_id'          => $userId,
                    'date'             => $today,
                    'first_entry_time' => $now,
                    'is_active'        => true,
                ]);

                WorkPeriod::create([
                    'session_id' => $session->id,
                    'entry_time' => $now,
                ]);

                return;
            }

            if (!$session->is_active) {
                $session->update(['is_active' => true]);

                WorkPeriod::create([
                    'session_id' => $session->id,
                    'entry_time' => $now,
                ]);
            }
        });
    }

    // تسجيل خروج الموظف من شاشة التحصيل
    public function recordEmployeeExit(): void
    {
        $userId = Auth::id();
        $today = Carbon::today();
        $now = Carbon::now();

        DB::transaction(function () use ($userId, $today, $now) {
            $session = CollectionProcessSession::where('user_id', $userId)
                ->where('date', $today)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $openPeriod = WorkPeriod::where('session_id', $session->id)
                ->whereNull('exit_time')
                ->first();

            if ($openPeriod) {
                $this->closeWorkPeriod($openPeriod, 'manual', $now);
                return;
            }

            $session->update([
                'last_exit_time' => $now,
                'is_active'      => false,
            ]);
        });
    }

    // إغلاق فترة عمل وتحديث بيانات الجلسة المرتبطة بها
    public function closeWorkPeriod(WorkPeriod $period, string $exitType, ?Carbon $exitTime = null): void
    {
        $exitTime = $exitTime ?? Carbon::now();

        $period->close($exitType, $exitTime);

        $session = $period->session;
        $session->increment('total_work_minutes', $period->duration_minutes);
        $session->update([
            'last_exit_time' => $period->exit_time,
            'is_active'      => false,
        ]);
    }

    // تسجيل نشاط (إضافة متابعة فقط)
    public function recordActivity(string $activityType = 'follow', ?int $contactResultId = null): void
    {
        $userId = Auth::id();
        $today = Carbon::today();
        $now = Carbon::now();

        DB::transaction(function () use ($userId, $today, $now, $activityType, $contactResultId) {
            $session = CollectionProcessSession::where('user_id', $userId)
                ->where('date', $today)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return;
            }

            $openPeriod = WorkPeriod::where('session_id', $session->id)->whereNull('exit_time')->first();

            if (!$openPeriod) {
                $openPeriod = WorkPeriod::create([
                    'session_id' => $session->id,
                    'entry_time' => $now,
                ]);
                $session->update(['is_active' => true]);
            }

            if ($activityType === 'follow') {
                $session->incrementActivity('follow');
                $openPeriod->incrementActivity('follow');
            }

            if ($contactResultId == 1) {
                $session->incrementPaymentType('full_payment');
                $openPeriod->incrementPaymentType('full_payment');
            } elseif ($contactResultId == 2) {
                $session->incrementPaymentType('partial_payment');
                $openPeriod->incrementPaymentType('partial_payment');
            }
        });
    }
}
