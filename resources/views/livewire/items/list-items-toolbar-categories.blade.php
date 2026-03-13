<div x-data="{ category: $wire.entangle('category') }" class="flex flex-wrap gap-2">
    <template x-for="item in [
        { key: 'all', label: 'All' },
        { key: 'meals', label: 'Meals' },
        { key: 'drinks', label: 'Drinks' },
        { key: 'snacks', label: 'Snacks' },
    ]" :key="item.key">
        <button type="button"
            x-on:click="category = item.key"
            :class="category === item.key
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white text-gray-800 border-blue-300 hover:bg-blue-50 dark:bg-neutral-900 dark:text-gray-100 dark:border-blue-700 dark:hover:bg-neutral-800'"
            class="px-4 py-2 rounded-full text-sm font-semibold border transition-colors whitespace-nowrap">
            <span x-text="item.label"></span>
        </button>
    </template>
</div>
