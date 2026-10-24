<div class="d-flex gap-1 justify-content-center">
    <form method="POST" action="{{ route('tenant.archives.auctions.restore', $id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-label-success" title="استرجاع"><i class="ti ti-rotate-clockwise"></i></button></form>
    <form method="POST" action="{{ route('tenant.archives.auctions.force-delete', $id) }}" onsubmit="return confirm('سيتم حذف المزاد نهائيًا. هل تريد المتابعة؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-label-danger" title="حذف نهائي"><i class="ti ti-trash-x"></i></button></form>
</div>
