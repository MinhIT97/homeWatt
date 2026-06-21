<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-500 hover:to-primary-600 border border-transparent rounded-xl font-semibold text-sm text-white shadow-md shadow-primary-600/15 hover:shadow-lg hover:shadow-primary-600/20 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:from-primary-700 active:to-primary-800 transition ease-in-out duration-150 hover:-translate-y-0.5 transform']) }}>
    {{ $slot }}
</button>

