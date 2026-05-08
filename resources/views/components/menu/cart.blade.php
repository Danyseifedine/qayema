@props([
    'slug',
    'phone',
    'restaurantName',
    'currency' => 'USD',
])

{{-- Alpine.js cart store + sidebar UI --}}
<div
    x-data
    x-init="
        Alpine.store('cart').init({
            slug: '{{ $slug }}',
            phone: '{{ $phone }}',
            restaurantName: @js($restaurantName),
            currency: '{{ $currency }}',
        })
    "
>
    {{-- Floating cart button --}}
    <button
        @click="Alpine.store('cart').open = true"
        x-show="Alpine.store('cart').count > 0"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        class="fixed bottom-6 right-6 z-40 flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-3 rounded-full shadow-lg transition-colors"
        aria-label="Open cart"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span x-text="Alpine.store('cart').count"></span>
        <span class="hidden sm:inline">items</span>
    </button>

    {{-- Sidebar overlay --}}
    <div
        x-show="Alpine.store('cart').open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="Alpine.store('cart').open = false"
        class="fixed inset-0 z-40 bg-black/40"
        aria-hidden="true"
    ></div>

    {{-- Sidebar panel --}}
    <div
        x-show="Alpine.store('cart').open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed top-0 right-0 z-50 h-full w-full max-w-sm bg-white shadow-2xl flex flex-col"
        role="dialog"
        aria-label="Your order"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h2 class="text-lg font-semibold text-gray-900">Your Order</h2>
                <span
                    class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-500 text-white text-xs font-bold"
                    x-text="Alpine.store('cart').count"
                ></span>
            </div>
            <button
                @click="Alpine.store('cart').open = false"
                class="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                aria-label="Close cart"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Empty state --}}
        <div
            x-show="Alpine.store('cart').isEmpty"
            class="flex-1 flex flex-col items-center justify-center gap-3 text-gray-400 p-8"
        >
            <svg class="w-16 h-16 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <p class="text-sm font-medium">Your cart is empty</p>
            <p class="text-xs text-center">Add items from the menu to get started.</p>
        </div>

        {{-- Cart items --}}
        <div
            x-show="!Alpine.store('cart').isEmpty"
            class="flex-1 overflow-y-auto divide-y divide-gray-100"
        >
            <template x-for="item in Alpine.store('cart').items" :key="item.id">
                <div class="flex items-start gap-3 px-5 py-4">
                    {{-- Dish image --}}
                    <template x-if="item.image">
                        <img :src="item.image" :alt="item.name" class="w-14 h-14 rounded-lg object-cover flex-shrink-0">
                    </template>
                    <template x-if="!item.image">
                        <div class="w-14 h-14 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </template>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate" x-text="item.name"></p>
                        <p class="text-sm text-green-600 font-medium mt-0.5" x-text="Alpine.store('cart').formatPrice(item.price)"></p>

                        {{-- Quantity controls --}}
                        <div class="flex items-center gap-2 mt-2">
                            <button
                                @click="Alpine.store('cart').decrement(item.id)"
                                class="w-7 h-7 flex items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors text-base leading-none"
                            >−</button>
                            <span class="text-sm font-semibold w-5 text-center" x-text="item.qty"></span>
                            <button
                                @click="Alpine.store('cart').increment(item.id)"
                                class="w-7 h-7 flex items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-100 transition-colors text-base leading-none"
                            >+</button>
                            <button
                                @click="Alpine.store('cart').remove(item.id)"
                                class="ml-auto p-1 text-gray-300 hover:text-red-400 transition-colors"
                                aria-label="Remove item"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div x-show="!Alpine.store('cart').isEmpty" class="border-t border-gray-100 px-5 py-4 space-y-4 bg-white">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600">Total</span>
                <span class="text-xl font-bold text-gray-900" x-text="Alpine.store('cart').formattedTotal"></span>
            </div>

            <button
                @click="Alpine.store('cart').checkout()"
                :disabled="Alpine.store('cart').loading"
                class="w-full flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 disabled:opacity-60 text-white font-semibold py-3 px-4 rounded-xl transition-colors"
            >
                <template x-if="!Alpine.store('cart').loading">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12.004 0C5.374 0 0 5.373 0 11.996c0 2.117.554 4.101 1.523 5.828L.057 23.97l6.304-1.654a12.005 12.005 0 005.643 1.418c6.63 0 12.004-5.372 12.004-11.995C24.008 5.372 18.634 0 12.004 0zm0 21.93a9.954 9.954 0 01-5.074-1.385l-.364-.216-3.741.981 1-3.642-.237-.375A9.943 9.943 0 012.07 11.996C2.07 6.527 6.534 2.07 12.004 2.07c5.47 0 9.934 4.457 9.934 9.926 0 5.47-4.464 9.934-9.934 9.934z"/>
                    </svg>
                </template>
                <template x-if="Alpine.store('cart').loading">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </template>
                <span x-text="Alpine.store('cart').loading ? 'Sending...' : 'Order on WhatsApp'"></span>
            </button>

            <button
                @click="Alpine.store('cart').clear()"
                class="w-full text-xs text-gray-400 hover:text-red-400 transition-colors text-center"
            >Clear cart</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('cart', {
            open: false,
            loading: false,
            items: [],
            slug: '',
            phone: '',
            restaurantName: '',
            currency: 'USD',

            init(config) {
                this.slug = config.slug;
                this.phone = config.phone;
                this.restaurantName = config.restaurantName;
                this.currency = config.currency;
            },

            get count() {
                return this.items.reduce((sum, item) => sum + item.qty, 0);
            },

            get isEmpty() {
                return this.items.length === 0;
            },

            get total() {
                return this.items.reduce((sum, item) => sum + item.price * item.qty, 0);
            },

            get formattedTotal() {
                return this.formatPrice(this.total);
            },

            formatPrice(amount) {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: this.currency || 'USD',
                    minimumFractionDigits: 2,
                }).format(amount);
            },

            add(dish) {
                const existing = this.items.find(i => i.id === dish.id);
                if (existing) {
                    existing.qty++;
                } else {
                    this.items.push({ ...dish, qty: 1 });
                }
                this.open = true;
            },

            remove(dishId) {
                this.items = this.items.filter(i => i.id !== dishId);
            },

            increment(dishId) {
                const item = this.items.find(i => i.id === dishId);
                if (item) { item.qty++; }
            },

            decrement(dishId) {
                const item = this.items.find(i => i.id === dishId);
                if (!item) { return; }
                if (item.qty <= 1) {
                    this.remove(dishId);
                } else {
                    item.qty--;
                }
            },

            clear() {
                this.items = [];
            },

            buildMessage() {
                let msg = `Hi! I'd like to order from *${this.restaurantName}*:\n\n`;
                this.items.forEach(item => {
                    msg += `• ${item.qty}x ${item.name} – ${this.formatPrice(item.price * item.qty)}\n`;
                });
                msg += `\n*Total: ${this.formattedTotal}*`;
                return msg;
            },

            async checkout() {
                if (this.isEmpty || this.loading) { return; }
                this.loading = true;

                try {
                    await fetch(`/${this.slug}/track-whatsapp-order`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            'Accept': 'application/json',
                        },
                    });
                } catch (_) {
                    // tracking failure is non-fatal
                }

                const encoded = encodeURIComponent(this.buildMessage());
                const phone = this.phone.replace(/\D/g, '');
                window.open(`https://wa.me/${phone}?text=${encoded}`, '_blank');

                this.loading = false;
            },
        });
    });
</script>
