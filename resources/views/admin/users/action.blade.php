<x-actions.row>
    <x-actions.show :url="route('admin.users.profile', $row->id)" title="عرض الملف الشخصي" />

    @can('users.edit')
        <x-actions.edit :url="route($route . '.edit', $row->id)" title="تعديل المستخدم" permission="users.edit" />
    @endcan

    @can('users.delete')
        <x-actions.delete :id="$row->id" :url="route($route . '.destroy', $row->id)" title="حذف المستخدم" permission="users.delete" />
    @endcan
</x-actions.row>
