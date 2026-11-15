<x-actions.row>
    @can('user_archive.restore')
        <x-actions.restore :id="$id" title="استرجاع المستخدم" icon="ti ti-refresh" />
    @endcan

    @can('user_archive.force_delete')
        <x-actions.force-delete :id="$id" title="حذف نهائي" />
    @endcan
</x-actions.row>
