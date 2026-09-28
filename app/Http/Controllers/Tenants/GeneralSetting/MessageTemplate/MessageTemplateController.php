<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\MessageTemplate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\GeneralSetting\MessageTemplate\StoreMessageTemplateRequest;
use App\Http\Requests\Tenants\GeneralSetting\MessageTemplate\UpdateMessageTemplateRequest;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class MessageTemplateController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    */
    public function __construct()
    {
        $this->middleware('can:نماذج الرسائل');
    }

    /*
    |--------------------------------------------------------------------------
    | Display Templates List
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = MessageTemplate::with('creator')
                ->select([
                    'id',
                    'name',
                    'content',
                    'category',
                    'channel',
                    'is_active',
                    'created_by',
                    'created_at'
                ]);

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('channel')) {
                $query->where('channel', $request->channel);
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status == '1');
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('content_preview', function ($template) {
                    return '<div class="text-truncate" style="max-width:250px;" title="' . e($template->content) . '">'
                        . e(\Str::limit(strip_tags($template->content), 60)) . '</div>';
                })
                ->addColumn('category_label', function ($template) {
                    return '<span class="badge bg-label-secondary">' . e($template->category_name) . '</span>';
                })
                ->addColumn('channel_label', function ($template) {
                    $label = $template->channel_name ?? strtoupper((string)$template->channel);
                    return '<span class="badge bg-label-info">' . e($label) . '</span>';
                })
                ->addColumn('status', function ($template) {
                    return '<span class="badge ' . e($template->status_class) . ' cursor-pointer"
                          onclick="toggleTemplateStatus(' . (int)$template->id . ')"
                          title="اضغط لتبديل الحالة">' . e($template->status_label) . '</span>';
                })
                ->addColumn('creator_name', function ($template) {
                    return e($template->creator->name ?? 'غير محدد');
                })
                ->addColumn('actions', function ($template) {
                    return $this->getActionsColumn($template);
                })
                ->editColumn('created_at', function ($template) {
                    return $template->created_at?->format('Y-m-d H:i');
                })

                ->orderColumn('name', fn($q, $order) => $q->orderBy('name', $order))
                ->orderColumn('category', fn($q, $order) => $q->orderBy('category', $order))
                ->orderColumn('channel', fn($q, $order) => $q->orderBy('channel', $order))
                ->orderColumn('is_active', fn($q, $order) => $q->orderBy('is_active', $order))
                ->orderColumn('created_at', fn($q, $order) => $q->orderBy('created_at', $order))

                ->filterColumn('name', fn($q, $keyword) => $q->where('name', 'like', "%{$keyword}%"))
                ->filterColumn('content', fn($q, $keyword) => $q->where('content', 'like', "%{$keyword}%"))
                ->filterColumn('channel', fn($q, $keyword) => $q->where('channel', 'like', "%{$keyword}%"))

                ->rawColumns(['content_preview', 'category_label', 'channel_label', 'status', 'actions'])
                ->make(true);
        }

        $statistics = [
            'total'     => MessageTemplate::count(),
            'companies' => MessageTemplate::where('category', 'company')->count(),
            'customers' => MessageTemplate::where('category', 'customers')->count(),
            'users'     => MessageTemplate::where('category', 'users')->count(),
        ];

        $categories = MessageTemplate::getCategories();
        $channels   = MessageTemplate::getChannels();

        return view('tenants.general_setting.message_templates.index', compact('categories', 'channels', 'statistics'));
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    */
    public function create(): View
    {
        $categories = MessageTemplate::getCategories();
        $channels = MessageTemplate::getChannels();
        $variables = MessageTemplate::getVariablesByCategory();
        $groupedVariables = MessageTemplate::getGroupedVariables();

        return view('tenants.general_setting.message_templates.create', compact('categories', 'channels', 'variables', 'groupedVariables'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Template
    |--------------------------------------------------------------------------
    */
    public function store(StoreMessageTemplateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            MessageTemplate::create([
                'name'       => $data['name'],
                'content'    => $data['content'],
                'category'   => $data['category'],
                'channel'    => $data['channel'],
                'is_active'  => $request->boolean('is_active', true),
                'created_by' => Auth::id(),
            ]);

            return redirect()
                ->to(tenant_route_name('general-settings.message-templates.index'))
                ->with('success', 'تم إنشاء القالب بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء القالب');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Template
    |--------------------------------------------------------------------------
    */
    public function show(MessageTemplate $messageTemplate): View
    {
        $messageTemplate->load('creator');
        return view('tenants.general_setting.message_templates.show', compact('messageTemplate'));
    }

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    */
    public function edit(MessageTemplate $messageTemplate): View
    {
        $categories = MessageTemplate::getCategories();
        $channels = MessageTemplate::getChannels();
        $variables = MessageTemplate::getVariablesByCategory($messageTemplate->category);
        $groupedVariables = MessageTemplate::getGroupedVariables($messageTemplate->category);

        return view('tenants.general_setting.message_templates.edit', compact('messageTemplate', 'categories', 'channels', 'variables', 'groupedVariables'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Template
    |--------------------------------------------------------------------------
    */
    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $messageTemplate): RedirectResponse
    {
        $data = $request->validated();

        try {
            $messageTemplate->update([
                'name'     => $data['name'],
                'content'  => $data['content'],
                'category' => $data['category'],
                'channel'  => $data['channel'],
            ]);

            return redirect()
                ->to(tenant_route_name('general-settings.message-templates.index'))
                ->with('success', 'تم تحديث القالب بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث القالب');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Template
    |--------------------------------------------------------------------------
    */
    public function destroy(MessageTemplate $messageTemplate): RedirectResponse
    {
        try {
            $messageTemplate->delete();

            return redirect()
                ->to(tenant_route_name('general-settings.message-templates.index'))
                ->with('success', 'تم حذف القالب بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء حذف القالب');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Template Status
    |--------------------------------------------------------------------------
    */
    public function toggleStatus(MessageTemplate $messageTemplate): JsonResponse
    {
        try {
            $newStatus = !$messageTemplate->is_active;
            $messageTemplate->update(['is_active' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة القالب بنجاح',
                'new_status' => $newStatus,
                'status_text' => $newStatus ? 'نشط' : 'غير نشط'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Preview Template with Variables
    |--------------------------------------------------------------------------
    */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
            'category' => 'nullable|string',
            'sample_data' => 'nullable|array'
        ]);

        try {
            // إنشاء قالب مؤقت للمعاينة
            $tempTemplate = new MessageTemplate([
                'content' => $request->content,
                'category' => $request->category ?? 'general'
            ]);

            $sampleData = $request->sample_data ?? [];
            $previewContent = $tempTemplate->previewContent($sampleData);

            return response()->json([
                'success' => true,
                'preview' => $previewContent,
                'length' => mb_strlen(strip_tags($previewContent)),
                'available_variables' => MessageTemplate::getVariablesByCategory($request->category)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة القالب'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Variables by Category
    |--------------------------------------------------------------------------
    */
    public function getVariablesByCategory(Request $request): JsonResponse
    {
        $category = $request->get('category');

        try {
            $variables = MessageTemplate::getVariablesByCategory($category);
            $groupedVariables = MessageTemplate::getGroupedVariables($category);

            return response()->json([
                'success' => true,
                'variables' => $variables,
                'grouped_variables' => $groupedVariables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المتغيرات'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Templates by Type and Category
    |--------------------------------------------------------------------------
    */
    public function getTemplates(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $category = $request->get('category');

        $templates = MessageTemplate::active()
            ->when($category, function ($query, $category) {
                return $query->where('category', $category);
            })
            ->select('id', 'name', 'content')
            ->orderBy('name')
            ->get();

        return response()->json($templates);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Statistics
    |--------------------------------------------------------------------------
    */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total' => MessageTemplate::count(),
            'active' => MessageTemplate::where('is_active', true)->count(),
            'customers' => MessageTemplate::where('category', 'customers')->count(),
            'general' => MessageTemplate::where('category', 'general')->count(),
        ];

        return response()->json($stats);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function getActionsColumn(MessageTemplate $template): string
    {
        $showUrl = tenant_route_name('general-settings.message-templates.show', $template->id);
        $editUrl = tenant_route_name('general-settings.message-templates.edit', $template->id);
        $deleteUrl = tenant_route_name('general-settings.message-templates.destroy', $template->id);
        $formId = 'delete-form-' . $template->id;

        return '<div class="d-flex justify-content-center">
                    <a title="عرض القالب" href="' . $showUrl . '" class="btn btn-icon btn-label-primary me-1">
                        <i class="ti ti-eye"></i>
                    </a>
                    <a title="تعديل القالب" href="' . $editUrl . '" class="btn btn-icon btn-label-primary me-1">
                        <i class="ti ti-edit"></i>
                    </a>
                    <button title="حذف القالب" type="button" onclick="confirmDelete(' . $template->id . ')" class="btn btn-icon btn-label-primary">
                        <i class="ti ti-trash"></i>
                    </button>
                    <form id="' . $formId . '" action="' . $deleteUrl . '" method="POST" style="display: none;">
                        ' . csrf_field() . '
                        ' . method_field('DELETE') . '
                    </form>
                </div>';
    }
}
