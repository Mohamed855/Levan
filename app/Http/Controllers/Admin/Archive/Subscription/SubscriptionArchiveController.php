<?php
namespace App\Http\Controllers\Admin\Archive\Subscription;

use App\DataTables\Admin\Archive\Subscription\SubscriptionDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Subscription\Subscription;
use Illuminate\Http\Request;

class SubscriptionArchiveController extends Controller
{
    public function index(SubscriptionDataTable $datatable){
        return $datatable->render('admin.archive.subscription.index');
    }
    public function restore($id)
    {
        $subscription = Subscription::onlyTrashed()->findOrFail($id);
        $subscription->restore();

        return redirect()->route('admin.archives.subscriptions.index')->with('success', 'تم استرجاع الاشتراك بنجاح.');
    }
    public function forceDelete($id)
    {
        $subscription = Subscription::onlyTrashed()->findOrFail($id);
        $subscription->forceDelete();

        return redirect()->route('admin.archives.subscriptions.index')->with('success','تم حذف الاشتراك نهائيًا بنجاح.');
    }
}