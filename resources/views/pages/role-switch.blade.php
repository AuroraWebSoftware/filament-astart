<x-filament::page class="">
    <div class="max-w-7xl mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h1 class="text-3xl font-bold text-black mb-2">
                Rol Seçimi
            </h1>
            <p class="text-gray-600">
                Devam etmek için bir rol seçin
            </p>
        </div>

        <!-- Rol Kartları -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($this->roles as $role)
                <div
                    wire:click="switchRole({{ $role->role_id }})"
                    class="group"
                >
                    <div class="h-[140px] overflow-hidden rounded-lg bg-white shadow-sm border border-gray-200 hover:border-blue-400 transition-all duration-200 hover:shadow-md cursor-pointer relative">
                        <div class="p-5 h-full flex flex-col justify-between">
                            <!-- Rol İsmi -->
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 flex items-center justify-center bg-blue-500 rounded-md text-white font-bold text-sm shadow-sm">
                                    {{ substr($role->role_name, 0, 1) }}
                                </div>
                                <h3 class="text-lg font-semibold text-black truncate">
                                    {{ $role->role_name }}
                                </h3>
                            </div>

                            <!-- Düğüm Bilgisi -->
                            <div class="flex items-start gap-2 text-sm">
                                <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-gray-500 line-clamp-2">
                                    {{ $role->node_name }}
                                </span>
                            </div>

                            <!-- Hover Efekti Arrow (Bottom Right) -->
                            <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Footer Mesaj -->
        <div class="mt-12 text-center">
            <p class="text-sm text-gray-500">
                İhtiyacınıza göre farklı roller arasında geçiş yapabilirsiniz
            </p>
        </div>
    </div>

    <!-- Özel Stil -->
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</x-filament::page>
