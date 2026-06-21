@props(['active' => false, 'href' => '#'])

<a href="{{ $href }}"
   @class([
       'flex items-center px-3 py-2 rounded-lg text-sm font-medium transition',
       'bg-primary-50 text-primary-700' => $active,
       'text-gray-700 hover:bg-gray-100' => !$active,
   ])
   {{ $attributes }}>
    {{ $slot }}
</a>
