@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-blue-500 dark:focus:ring-blue-500 rounded-xl shadow-sm text-sm placeholder-gray-400 dark:placeholder-gray-500']) }}>
