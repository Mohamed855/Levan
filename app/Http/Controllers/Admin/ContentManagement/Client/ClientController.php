<?php

namespace App\Http\Controllers\Admin\ContentManagement\Client;

use App\DataTables\Admin\ContentManagement\Client\ClientDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\ContentManagement\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index(ClientDataTable $datatable)
    {
        $client = Client::query();

        $stats = [
            'total' => (clone $client)->count(),
            'active' => (clone $client)->where('status', 1)->count(),
            'inactive' => (clone $client)->where('status', 0)->count(),
        ];
        return $datatable->render('admin.content_management.client.index', compact('stats'));
    }

    public function create()
    {
        return view('admin.content_management.client.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
        ], [
            'name.required' => 'اسم العميل مطلوب',
            'name.string' => 'اسم العميل يجب أن يكون نص',
            'name.max' => 'اسم العميل لا يمكن أن يتجاوز 255 حرف',
            'logo.image' => 'الشعار يجب أن يكون صورة',
            'logo.mimes' => 'الشعار يجب أن يكون من نوع: jpeg, png, jpg, gif',
            'status.required' => 'حالة العميل مطلوبة',
        ]);

        try {
            if ($request->hasFile('logo')) {
                $validated['logo'] = $request->file('logo')->store('clients', 'public');
            }

            Client::create($validated);

            return redirect()->route('admin.content_management.client.index')
                ->with('success', 'تم إضافة العميل بنجاح');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function show(Client $client)
    {
        return view('admin.content_management.client.details', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('admin.content_management.client.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|boolean',
        ], [
            'name.required' => 'اسم العميل مطلوب',
            'name.string' => 'اسم العميل يجب أن يكون نص',
            'name.max' => 'اسم العميل لا يمكن أن يتجاوز 255 حرف',
            'logo.image' => 'الشعار يجب أن يكون صورة',
            'logo.mimes' => 'الشعار يجب أن يكون من نوع: jpeg, png, jpg, gif',
            'status.required' => 'حالة العميل مطلوبة',
        ]);

        try {
            if ($request->hasFile('logo')) {
                // حذف الشعار القديم
                if ($client->logo) {
                    Storage::disk('public')->delete($client->logo);
                }
                $validated['logo'] = $request->file('logo')->store('clients', 'public');
            }

            $client->update($validated);

            return redirect()->route('admin.content_management.client.index')
                ->with('success', 'تم تحديث العميل بنجاح');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function destroy(Client $client)
    {
        try {
            if ($client->logo) {
                Storage::disk('public')->delete($client->logo);
            }
            $client->delete();

            return redirect()->route('admin.content_management.client.index')
                ->with('success', 'تم حذف العميل بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Request $request, Client $client)
    {
        try {
            $newStatus = !$client->status;
            $client->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => $newStatus 
                    ? 'تم تفعيل العميل بنجاح'
                    : 'تم إيقاف العميل بنجاح',
                'status' => $newStatus,
                'badge' => $newStatus 
                    ? '<span class="status-badge badge bg-success">نشط</span>'
                    : '<span class="status-badge badge bg-danger">غير نشط</span>'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

}
