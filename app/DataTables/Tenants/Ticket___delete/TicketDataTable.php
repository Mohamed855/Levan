<?php


use App\DataTables\Abstract\AbstractDataTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\shared\Integration\EtmamTicketService as IntegrationEtmamTicketService;

class TicketDataTable_old extends AbstractDataTable
{
    private $page   = "tenants.ticket";
    private $route  = "ticket";

    protected function getTableId()
    {
        return "ticket-table";
    }

    protected function resource()
    {
        try {
            $apiClient = IntegrationEtmamTicketService::fromConfig();

            $query = 'tenant_id=' . tenant('id') . '&tenant_user_id=' . Auth::id();
            $response = $apiClient->getTenantTickets('/tenants/tickets', $query);

            if (isset($response['success']) && $response['success'] === true) {
                $data = $response['data']['data'] ?? [];
                // Convert all nested arrays/objects to proper format
                return collect($data)->map(function ($item) {
                    return $this->normalizeTicketData($item);
                });
            }

            if (isset($response['data']['data'])) {
                return collect($response['data']['data'])->map(function ($item) {
                    return $this->normalizeTicketData($item);
                });
            }

            Log::warning('No tickets data returned from API', ['response' => $response]);
            return collect([]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch tickets from API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]);
        }
    }

    /**
     * Normalize ticket data to ensure proper display
     */
    private function normalizeTicketData($item)
    {
        // Convert to array if it's an object
        if (is_object($item)) {
            $item = json_decode(json_encode($item), true);
        }

        return [
            'id' => $item['id'] ?? null,
            'ticket_number' => $item['ticket_number'] ?? '-',
            'title' => is_array($item['title'] ?? null) ? implode(', ', $item['title']) : ($item['title'] ?? '-'),
            'description' => is_array($item['description'] ?? null) ? implode(', ', $item['description']) : ($item['description'] ?? ''),
            'priority' => $item['priority'] ?? 'low',
            'status' => $item['status'] ?? 'open',
            'customer_name' => is_array($item['customer_name'] ?? null) ? implode(', ', $item['customer_name']) : ($item['customer_name'] ?? '-'),
            'customer_email' => $item['customer_email'] ?? '-',
            'customer_phone' => $item['customer_phone'] ?? '-',
            'attachments' => is_array($item['attachments'] ?? null) ? $item['attachments'] : [],
            'created_at' => $item['created_at'] ?? null,
        ];
    }

    protected function getColumns(): array
    {
        return [
            [
                'data' => 'DT_RowIndex',
                'name' => 'DT_RowIndex',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px'
            ],
            [
                'data' => 'id',
                'name' => 'id',
                'visible' => false
            ],
            [
                'data' => null,
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'className' => 'custom-checkbox',
                'width' => '10px',
                'defaultContent' => ''
            ],
            [
                'data' => 'ticket_number',
                'name' => 'ticket_number',
                'title' => 'رقم التذكرة',
                'className' => 'text-start'
            ],
            [
                'data' => 'title',
                'name' => 'title',
                'title' => 'عنوان التذكرة',
                'className' => 'text-start'
            ],
            [
                'data' => 'description',
                'name' => 'description',
                'title' => 'وصف التذكرة',
                'className' => 'text-start',
                'width' => '300px'
            ],
            [
                'data' => 'priority',
                'name' => 'priority',
                'title' => 'الأولوية',
                'className' => 'text-start'
            ],
            [
                'data' => 'status',
                'name' => 'status',
                'title' => 'الحالة',
                'className' => 'text-start'
            ],
            [
                'data' => 'customer_name',
                'name' => 'customer_name',
                'title' => 'اسم العميل',
                'className' => 'text-start'
            ],
            [
                'data' => 'customer_email',
                'name' => 'customer_email',
                'title' => 'البريد',
                'className' => 'text-start'
            ],
            [
                'data' => 'customer_phone',
                'name' => 'customer_phone',
                'title' => 'الهاتف',
                'className' => 'text-start'
            ],
            [
                'data' => 'attachments',
                'name' => 'attachments',
                'title' => 'المرفقات',
                'className' => 'text-center',
                'orderable' => false,
                'searchable' => false,
                'width' => '80px',
                'defaultContent' => '<span class="text-muted">لا يوجد</span>'
            ],
            [
                'data' => 'created_at',
                'name' => 'created_at',
                'title' => 'تاريخ الإنشاء',
                'className' => 'text-start'
            ],
            [
                'data' => null,
                'name' => 'actions',
                'title' => 'الإجراءات',
                'orderable' => false,
                'searchable' => false,
                'width' => '120px',
                'defaultContent' => ''
            ],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                $id = is_array($row) ? ($row['id'] ?? '') : $row->id ?? '';
                return '<input type="checkbox" class="row-checkbox" value="' . $id . '">';
            })

            ->editColumn('ticket_number', function ($row) {
                $ticketNumber = is_array($row) ? ($row['ticket_number'] ?? '-') : ($row->ticket_number ?? '-');
                return '<strong class="text-primary">' . e($ticketNumber) . '</strong>';
            })

            ->editColumn('title', function ($row) {
                $title = is_array($row) ? ($row['title'] ?? '-') : ($row->title ?? '-');
                return '<span>' . e($title) . '</span>';
            })

            ->editColumn('description', function ($row) {
                $description = is_array($row) ? ($row['description'] ?? '') : ($row->description ?? '');
                return $this->getTruncatedDescription($description);
            })

            ->editColumn('priority', function ($row) {
                $priority = is_array($row) ? ($row['priority'] ?? 'low') : ($row->priority ?? 'low');
                return $this->getPriorityBadge($priority);
            })

            ->editColumn('status', function ($row) {
                $status = is_array($row) ? ($row['status'] ?? 'open') : ($row->status ?? 'open');
                return $this->getStatusBadge($status);
            })

            ->editColumn('customer_name', function ($row) {
                $name = is_array($row) ? ($row['customer_name'] ?? '-') : ($row->customer_name ?? '-');
                return '<span>' . e($name) . '</span>';
            })

            ->editColumn('customer_email', function ($row) {
                $email = is_array($row) ? ($row['customer_email'] ?? '-') : ($row->customer_email ?? '-');
                if ($email === '-') {
                    return $email;
                }
                return '<a href="mailto:' . $email . '">' . e($email) . '</a>';
            })

            ->editColumn('customer_phone', function ($row) {
                $phone = is_array($row) ? ($row['customer_phone'] ?? '-') : ($row->customer_phone ?? '-');
                return '<span dir="ltr">' . e($phone) . '</span>';
            })

            ->editColumn('attachments', function ($row) {
                return $this->getAttachmentsPreview($row);
            })

            ->editColumn('created_at', function ($row) {
                $date = is_array($row) ? ($row['created_at'] ?? null) : ($row->created_at ?? null);
                if (!$date) return '-';
                try {
                    return date('Y-m-d H:i', strtotime($date));
                } catch (\Exception $e) {
                    return '-';
                }
            })

            ->addColumn('actions', function ($row) {
                return $this->getActionButtons($row);
            });
    }

    private function getTruncatedDescription($description)
    {
        if (!$description || $description === '-') {
            return '<span class="text-muted">لا يوجد وصف</span>';
        }

        $fullText = e($description);
        $limit = 50;

        if (mb_strlen($description) > $limit) {
            $truncated = Str::limit($description, $limit, '');
            return '<span class="description-truncate" title="' . $fullText . '" data-bs-toggle="tooltip" data-bs-placement="top">'
                . e($truncated) . '...</span>';
        }

        return '<span>' . $fullText . '</span>';
    }

    private function getPriorityBadge($priority)
    {
        $badges = [
            'low'    => '<span class="badge bg-info">منخفض</span>',
            'medium' => '<span class="badge bg-warning">متوسط</span>',
            'high'   => '<span class="badge bg-danger">عالي</span>',
        ];

        return $badges[$priority] ?? '<span class="badge bg-secondary">غير محدد</span>';
    }

    private function getStatusBadge($status)
    {
        $badges = [
            'open'                    => '<span class="badge bg-primary">جديدة</span>',
            'in_progress'             => '<span class="badge bg-warning">قيد المعالجة</span>',
            'resolved'                => '<span class="badge bg-success">تم الحل</span>',
            'closed'                  => '<span class="badge bg-secondary">مغلقة</span>',
            'reopened'                => '<span class="badge bg-info">أعيد فتحها</span>',
            'resolved_pending_survey' => '<span class="badge bg-success">تم الحل - بانتظار التقييم</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-light text-dark">' . e($status) . '</span>';
    }

    private function getAttachmentsPreview($row)
    {
        $attachments = is_array($row) ? ($row['attachments'] ?? []) : ($row->attachments ?? []);

        // Convert object to array if needed
        if (is_object($attachments)) {
            $attachments = json_decode(json_encode($attachments), true);
        }

        if (empty($attachments) || !is_array($attachments)) {
            return '<span class="text-muted">لا يوجد</span>';
        }

        $count = count($attachments);
        $firstAttachment = $attachments[0];

        if (!isset($firstAttachment['file_path'])) {
            return '<span class="text-muted">لا يوجد</span>';
        }

        $filePath = 'https://app.e-tec.sa/storage/' . $firstAttachment['file_path'];
        $extension = pathinfo($firstAttachment['file_path'], PATHINFO_EXTENSION);
        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);

        $rowId = is_array($row) ? ($row['id'] ?? '') : ($row->id ?? '');
        $html = '<div class="d-flex align-items-center justify-content-center gap-1">';

        if ($isImage) {
            $html .= '<a href="' . $filePath . '" target="_blank" data-lightbox="ticket-' . $rowId . '">';
            $html .= '<img src="' . $filePath . '" alt="attachment" style="width: 50px; height: 40px; object-fit: cover; border-radius: 4px; cursor: pointer;" class="img-thumbnail">';
            $html .= '</a>';
        } else {
            $html .= '<a href="' . $filePath . '" target="_blank" class="btn btn-sm btn-outline-primary" title="عرض الملف">';
            $html .= '<i class="ti ti-file-text"></i>';
            $html .= '</a>';
        }

        if ($count > 1) {
            $html .= '<span class="badge bg-secondary">+' . ($count - 1) . '</span>';
        }

        $html .= '</div>';
        return $html;
    }

    private function getActionButtons($row)
    {
        $status = is_array($row) ? ($row['status'] ?? '') : ($row->status ?? '');
        $id = is_array($row) ? ($row['id'] ?? '') : ($row->id ?? '');

        if (!$id) {
            return '<span class="text-muted">-</span>';
        }

        $html = '<div class="dropdown">';
        $html .= '<button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">';
        $html .= '<i class="ti ti-dots-vertical"></i>';
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu dropdown-menu-end">';

        // عرض
        $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="viewTicket(' . $id . ')"><i class="ti ti-eye me-2"></i>عرض</a></li>';

        if ($status === 'open') {
            // تعديل
            $html .= '<li><a class="dropdown-item" href="javascript:void(0);" onclick="editTicket(' . $id . ')"><i class="ti ti-edit me-2"></i>تعديل</a></li>';

            // حذف
            $html .= '<li><hr class="dropdown-divider"></li>';
            $html .= '<li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteTicket(' . $id . ')"><i class="ti ti-trash me-2"></i>حذف</a></li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'ticket_number',
            'title',
            'attachments',
            'priority',
            'status',
            'description',
            'customer_name',
            'customer_email',
            'customer_phone',
            'actions'
        ];
    }

    protected function getSearchableColumns(): array
    {
        return ['ticket_number', 'title', 'description', 'customer_name', 'customer_email'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
            $this->getAddButton()
        ];
    }

    private function getActionsDropdownButton(): array
    {
        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل'],
            ],
        ];
    }

    private function getAddButton(): array
    {
        return [
            [
                'text' => '<i class="fas fa-plus-circle me-1"></i> إضافة تذكرة',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='" . tenant_route_name($this->route . '.create') . "'; }"
            ],
            [
                'text' => '<i class="fas fa-reply me-1"></i> الردود',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='" . tenant_route_name($this->route . '.reply') . "'; }"
            ],
        ];
    }
}
