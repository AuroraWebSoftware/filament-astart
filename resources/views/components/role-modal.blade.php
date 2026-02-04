<x-filament::modal
    id="role-select-modal"
    width="md"
    display-classes="block z-9999"
    :persistent="true"
    heading="Rol Seçimi"
>
    <div class="space-y-4">
        @php
            $roles = \Illuminate\Support\Facades\DB::table('user_role_organization_node')
                ->where('user_id', auth()->id())
                ->leftJoin('organization_nodes', 'organization_nodes.id', '=', 'user_role_organization_node.organization_node_id')
                ->leftJoin('roles', 'roles.id', '=', 'user_role_organization_node.role_id')
                ->select(
                    'user_role_organization_node.role_id',
                    'roles.name as role_name',
                    DB::raw('STRING_AGG(organization_nodes.name, \', \') AS node_name')
                )
                ->groupBy('user_role_organization_node.role_id', 'roles.name')
                ->get();
        @endphp

        @foreach($roles as $role)
            <x-filament::button
                wire:click="selectRole({{ $role->role_id }})"
                full-width
                class="mb-2"
            >
                {{ $role->role_name }}
                <span class="text-xs fi-color-gray block">{{ $role->node_name }}</span>
            </x-filament::button>
        @endforeach
    </div>
</x-filament::modal>
