<x-actions.row>
    <x-actions.show :url="tenant_route_name('auctions.show', $row)" title="تفاصيل المزاد" permission="pending_auctions.view" />
</x-actions.row>
