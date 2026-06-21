<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Onboarding card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">
                    <div class="text-5xl mb-4">⚡</div>
                    <h2 class="text-xl font-semibold text-gray-700 mb-2">Welcome to HomeWatt!</h2>
                    <p class="text-gray-500 max-w-md mx-auto mb-6">
                        Start by adding your home, rooms, and devices to get energy estimates. Snap photos of appliance labels and let AI extract the specs.
                    </p>
                    <div class="flex justify-center gap-3">
                        <a href="#" class="inline-flex items-center px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 text-sm font-semibold">Add Your Home</a>
                    </div>
                </div>
            </div>

            <!-- Feature cards -->
            <div class="grid md:grid-cols-3 gap-6 mt-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-2xl mb-2">📷</div>
                    <h3 class="font-semibold text-lg mb-2">AI Device Recognition</h3>
                    <p class="text-gray-600 text-sm">Snap a photo of any appliance label and let AI extract power specifications automatically.</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-2xl mb-2">📊</div>
                    <h3 class="font-semibold text-lg mb-2">Energy Estimation</h3>
                    <p class="text-gray-600 text-sm">Get monthly kWh and cost estimates per device, room, or your entire home.</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-2xl mb-2">🔍</div>
                    <h3 class="font-semibold text-lg mb-2">Transparent Data</h3>
                    <p class="text-gray-600 text-sm">Every result shows measurement source, confidence level, and calculation method.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
