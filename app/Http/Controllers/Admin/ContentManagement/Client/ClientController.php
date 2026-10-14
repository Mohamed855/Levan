<?php

namespace App\Http\Controllers\Admin\ContentManagement\Client;

use App\Actions\Admin\ContentManagement\ManageClientAction;
use App\DataTables\Admin\ContentManagement\Client\ClientDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\ContentManagement\StoreClientRequest;
use App\Http\Requests\Admin\ContentManagement\UpdateClientRequest;
use App\Models\Admin\ContentManagement\Client;

class ClientController extends Controller
{
    public function __construct(private readonly ManageClientAction $clients)
    {
        $this->middleware('auth:admin');
        $this->authorizeResource(Client::class, 'client');
        $this->middleware('can:update,client')->only(['toggleStatus']);
    }

    public function index(ClientDataTable $datatable)
    {
        $client = Client::query();

        $stats = [
            'total' => (clone $client)->count(),
            'active' => (clone $client)->where('status', 1)->count(),
            'inactive' => (clone $client)->where('status', 0)->count(),
        ];
        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
        ];

        return $datatable->render('admin.content_management.client.index', compact('stats', 'filterOptions'));
    }

    public function create()
    {
        return view('admin.content_management.client.create');
    }

    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->clients->create($validated, $request->file('logo'));

            return redirect()->route('admin.content_management.client.index')
                ->with('success', 'تم إضافة العميل بنجاح');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ: '.$e->getMessage());
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

    public function update(UpdateClientRequest $request, Client $client)
    {
        $validated = $request->validated();

        try {
            $this->clients->update($client, $validated, $request->file('logo'));

            return redirect()->route('admin.content_management.client.index')
                ->with('success', 'تم تحديث العميل بنجاح');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    public function destroy(AdminAuthenticatedRequest $request, Client $client)
    {
        try {
            $this->clients->delete($client);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف العميل بنجاح']);
            }

            return redirect()->route('admin.content_management.client.index')
                ->with('success', 'تم حذف العميل بنجاح');
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ: '.$e->getMessage());
        }
    }

    public function toggleStatus(AdminAuthenticatedRequest $request, Client $client)
    {
        try {
            $newStatus = $this->clients->toggleStatus($client);

            return response()->json([
                'success' => true,
                'message' => $newStatus
                    ? 'تم تفعيل العميل بنجاح'
                    : 'تم إيقاف العميل بنجاح',
                'status' => $newStatus,
                'badge' => $newStatus
                    ? '<span class="status-badge badge bg-success">نشط</span>'
                    : '<span class="status-badge badge bg-danger">غير نشط</span>',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر تحديث حالة العميل.',
            ], 500);
        }
    }
}
