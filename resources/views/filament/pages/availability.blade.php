<x-filament-panels::page>
    <div>
        {{ $this->form }}
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="fi-section">
            <div class="fi-section-header">
                <h2 class="fi-section-header-heading text-base font-semibold">Available Drivers</h2>
            </div>
            <div class="fi-section-content-ctn p-4">
                <ul class="space-y-2">
                    @forelse ($drivers as $driver)
                        <li class="flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $driver['name'] }}</div>
                                <div class="text-sm text-gray-500">{{ $driver['company'] }}</div>
                            </div>
                        </li>
                    @empty
                        <div class="text-sm text-gray-500">No drivers available for this period.</div>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="fi-section">
            <div class="fi-section-header">
                <h2 class="fi-section-header-heading text-base font-semibold">Available Vehicles</h2>
            </div>
            <div class="fi-section-content-ctn p-4">
                <ul class="space-y-2">
                    @forelse ($vehicles as $vehicle)
                        <li class="flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $vehicle['license_plate'] }}</div>
                                <div class="text-sm text-gray-500">{{ $vehicle['company'] }}</div>
                            </div>
                        </li>
                    @empty
                        <div class="text-sm text-gray-500">No vehicles available for this period.</div>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>


