@props([
    'model',
    'options' => [],
    'value' => '',
    'placeholder' => 'Pilih opsi',
    'searchPlaceholder' => 'Cari...',
    'emptyText' => 'Tidak ada hasil.',
    'clearable' => true,
])

@php
    $items = collect($options)
        ->map(fn ($label, $id): array => [
            'value' => (string) $id,
            'label' => (string) $label,
        ])
        ->values();
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    wire:ignore
    x-data="{
        open: false,
        search: '',
        activeIndex: 0,
        value: @js((string) $value),
        selected: null,
        items: @js($items),
        placeholder: @js($placeholder),
        emptyText: @js($emptyText),
        init() {
            this.sync(this.value);
            this.$wire.$watch(@js($model), (value) => {
                this.value = String(value ?? '');
                this.sync(this.value);
            });
        },
        sync(value) {
            this.selected = this.items.find((item) => item.value === String(value ?? '')) ?? null;
            this.search = '';
            this.activeIndex = 0;
        },
        get filtered() {
            const needle = this.search.trim().toLowerCase();
            const matches = needle === ''
                ? this.items
                : this.items.filter((item) => item.label.toLowerCase().includes(needle));

            return matches.slice(0, 100);
        },
        openMenu() {
            this.open = true;
            this.activeIndex = 0;
            this.$nextTick(() => this.$refs.search?.focus());
        },
        closeMenu() {
            this.open = false;
        },
        choose(item) {
            this.value = item.value;
            this.selected = item;
            this.open = false;
            this.search = '';
            this.$wire.set(@js($model), item.value);
        },
        clear() {
            this.value = '';
            this.selected = null;
            this.search = '';
            this.$wire.set(@js($model), '');
        },
        move(delta) {
            const count = this.filtered.length;
            if (count === 0) return;
            this.activeIndex = (this.activeIndex + delta + count) % count;
        },
        chooseActive() {
            const item = this.filtered[this.activeIndex];
            if (item) this.choose(item);
        },
    }"
    @keydown.escape.window="closeMenu()"
    @click.outside="closeMenu()"
>
    <button
        type="button"
        class="flex min-h-9 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-white/10 dark:bg-white/5 dark:text-white"
        @click="open ? closeMenu() : openMenu()"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox"
    >
        <span
            class="truncate"
            :class="selected ? 'text-gray-950 dark:text-white' : 'text-gray-400 dark:text-gray-500'"
            x-text="selected ? selected.label : placeholder"
        ></span>
        <x-filament::icon icon="heroicon-o-chevron-up-down" class="size-4 shrink-0 text-gray-400" />
    </button>

    @if ($clearable)
        <button
            type="button"
            x-cloak
            x-show="selected !== null"
            class="absolute right-8 top-1/2 -translate-y-1/2 rounded p-0.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200"
            @click.stop="clear()"
            title="Kosongkan pilihan"
        >
            <x-filament::icon icon="heroicon-o-x-mark" class="size-4" />
        </button>
    @endif

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top
        class="absolute z-40 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-gray-900"
    >
        <div class="border-b border-gray-100 p-2 dark:border-white/10">
            <input
                x-ref="search"
                type="search"
                x-model="search"
                @input="activeIndex = 0"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="chooseActive()"
                placeholder="{{ $searchPlaceholder }}"
                class="w-full rounded-md border-0 bg-transparent px-2 py-1.5 text-sm text-gray-950 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:text-white dark:placeholder:text-gray-500"
            />
        </div>

        <div class="max-h-64 overflow-y-auto p-1" role="listbox">
            <template x-for="(item, index) in filtered" :key="item.value">
                <button
                    type="button"
                    role="option"
                    class="flex w-full items-center rounded-md px-2 py-2 text-left text-sm text-gray-700 transition dark:text-gray-200"
                    :class="index === activeIndex ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : 'hover:bg-gray-50 dark:hover:bg-white/5'"
                    @mouseenter="activeIndex = index"
                    @mousedown.prevent="choose(item)"
                >
                    <span class="truncate" x-text="item.label"></span>
                </button>
            </template>

            <p x-show="filtered.length === 0" class="px-2 py-6 text-center text-sm text-gray-500 dark:text-gray-400" x-text="emptyText"></p>
        </div>
    </div>
</div>
