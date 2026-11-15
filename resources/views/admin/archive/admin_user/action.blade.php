<x-actions.row>
    @can('users.restore')
        <x-actions.restore :id="$id" title="استرجاع المستخدم" icon="ti ti-refresh" />
    @endcan

    @can('users.force_delete')
        <x-actions.force-delete :id="$id" title="حذف نهائي" />
    @endcan
</x-actions.row>
