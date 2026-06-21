<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-red-600 hover:bg-red-500 border border-transparent rounded-xl font-semibold text-sm text-white shadow-md shadow-red-600/10 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700 transition ease-in-out duration-150 hover:-translate-y-0.5 transform']) }}>
    {{ $slot }}
</button>

