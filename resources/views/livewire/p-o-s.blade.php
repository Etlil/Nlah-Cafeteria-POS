<div class="flex h-screen bg-gray-100 dark:bg-neutral-900 font-sans antialiased text-gray-800 dark:text-gray-100">
    <div class="w-2/3 p-6 flex flex-col">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">Products</h2>

        <div class="flex-shrink-0 mb-4">
            <input wire:model.live="search" type="text" placeholder="Search products by name" class="w-full px-5 py-3 border border-blue-300 rounded-xl shadow-sm
                        focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors
                        dark:bg-neutral-800 dark:border-blue-700 dark:text-gray-100">
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (['all' => 'All', 'meals' => 'Meals', 'drinks' => 'Drinks', 'snacks' => 'Snacks'] as $key => $label)
                    <button type="button"
                        wire:click="$set('category', '{{ $key }}')"
                        class="px-4 py-2 rounded-full text-sm font-semibold border transition-colors
                               {{ $category === $key
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-white text-gray-800 border-blue-300 hover:bg-blue-50' }}
                               dark:border-blue-700 dark:bg-neutral-900 dark:text-gray-100 dark:hover:bg-neutral-800">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if(session()->has('error'))
                <div class="mt-2 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded-lg shadow-md">
                    {{ session('error') }}
                </div>
            @endif
            @if(session()->has('success'))
                <div
                    class="mt-2 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-lg shadow-md">
                    {{ session('success') }}
                </div>
            @endif
        </div>
        {{-- Left panel item listing/item catalog --}}
        <div class="flex-grow overflow-y-auto pr-2">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($this->filteredItems as $item)
                    <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-lg overflow-hidden
                                         transition-all duration-200 transform hover:scale-105 hover:shadow-xl">
                        <div class="p-4">
                            @php
                                $imagePath = $item->image
                                    ? (str_starts_with($item->image, 'item_images/') ? $item->image : 'item_images/' . ltrim($item->image, '/'))
                                    : null;
                                $imageUrl = $imagePath
                                    ? rtrim(request()->getSchemeAndHttpHost(), '/') . '/storage/' . $imagePath
                                    : rtrim(request()->getSchemeAndHttpHost(), '/') . '/images/placeholder.png';
                            @endphp
                            <img
                                src="{{ $imageUrl }}"
                                alt="{{ $item->name }}"
                                class="w-full h-32 object-cover rounded-lg mb-3 bg-gray-200 dark:bg-neutral-700"
                            />
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $item->name }}</h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 font-bold">
                                <!-- Display price with 2 decimal places -->
                                PHP {{ number_format($item->price, 2, '.', ',') }}
                            </p>
                        </div>
                        <button wire:click="addToCart({{ $item->id }})" class="w-full py-3 bg-indigo-600 text-white font-bold transition-colors duration-200
                                                 hover:bg-indigo-700 rounded-b-2xl">
                            Add to Cart
                        </button>
                    </div>
                @empty
                    <p class="col-span-full text-center text-gray-500 dark:text-gray-400 mt-8">No products found.</p>
                @endforelse
            </div>
        </div>
    </div>
    {{-- Right panel --}}
    <div
        class="w-1/3 bg-white dark:bg-neutral-800 border-l dark:border-neutral-700 p-6 flex flex-col shadow-xl overflow-y-auto">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6">Checkout</h2>
        <div class="flex-grow pr-2">
            @forelse($this->cart as $cartItem)
                <div class="flex items-center justify-between p-4 mb-4 bg-gray-50 dark:bg-neutral-700 rounded-xl shadow-sm">
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $cartItem['name'] }}</h4>
                        <!-- Display price with 2 decimal places -->
                        <p class="text-xs text-gray-500 dark:text-gray-400">PHP {{ number_format($cartItem['price'], 2, '.', ',') }} each</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="number" min="1" wire:model.live.debounce.500ms="cart.{{ $cartItem['id'] }}.quantity"
                            class="py-2.5 sm:py-3 px-4 block w-20 border-gray-200 rounded-lg sm:text-sm
                                                 focus:border-blue-500 focus:ring-blue-500
                                                 dark:bg-neutral-900 dark:border-neutral-700
                                                 dark:text-neutral-400 dark:placeholder-neutral-500
                                                 dark:focus:ring-neutral-600">

                        <button wire:click="removeFromCart({{ $cartItem['id'] }})"
                            class="p-2 text-red-500 hover:text-red-700 dark:hover:text-red-400">
                            ✕
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-400 dark:text-gray-500 mt-20">Your cart is empty.</p>
            @endforelse
        </div>

        {{-- checkout start --}}
        <div class="flex-shrink-0 mt-6 space-y-4">
            <div class="space-y-2">
                <div>
                    <label for="customer"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                    <select wire:model="customer_id" id="customer" class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm
                            focus:border-blue-500 focus:ring-blue-500
                            dark:bg-neutral-900 dark:border-neutral-700
                            dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600">
                        <option value="">Select a customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="payment-method"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method</label>
                    <select wire:model="payment_method_id" id="payment-method" class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm
                            focus:border-blue-500 focus:ring-blue-500
                            dark:bg-neutral-900 dark:border-neutral-700
                            dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600">
                        <option value="">Select a payment method</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-3 mb-4" x-data="{ customMode: false }">
                    @foreach ([1000, 500, 200, 100, 50, 20, 10, 5] as $bill)
                        <button type="button"
                            wire:click="$set('paid_amount', {{ $bill }})"
                            x-on:click="customMode = false"
                            class="w-28 h-20 rounded-xl text-base font-semibold border border-gray-200
                                   bg-white text-gray-800 hover:bg-gray-50 shadow-sm
                                   dark:bg-neutral-900 dark:text-gray-100 dark:border-neutral-700 dark:hover:bg-neutral-800">
                            {{ $bill === 1000 ? '1k' : number_format($bill, 0, '.', ',') }}
                        </button>
                    @endforeach
                    <button type="button"
                        x-on:click="customMode = true; $wire.set('paid_amount', null); $nextTick(() => $refs.customPaid?.focus())"
                        class="w-28 h-20 rounded-xl text-base font-semibold border border-gray-200
                               bg-white text-gray-800 hover:bg-gray-50 shadow-sm
                               dark:bg-neutral-900 dark:text-gray-100 dark:border-neutral-700 dark:hover:bg-neutral-800">
                        Custom
                    </button>
                </div>
                <div x-show="customMode" class="mb-4">
                    <input type="number" min="0" step="1" placeholder="Enter amount"
                        x-ref="customPaid"
                        wire:model.live.debounce.300ms="paid_amount"
                        class="py-2.5 sm:py-3 px-4 block w-full border-gray-200 rounded-lg sm:text-sm
                               focus:border-blue-500 focus:ring-blue-500
                               dark:bg-neutral-900 dark:border-neutral-700
                               dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600">
                </div>
            </div>

            <!-- REMOVED: Discount input section -->

            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-neutral-700">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Payment:</span>
                    <span class="font-medium text-gray-800 dark:text-gray-100">PHP
                        <!-- Display subtotal with 2 decimal places -->
                        {{ number_format($this->paid_amount, 2, '.', ',') }}</span>
                </div>
                <div
                    class="flex justify-between items-center text-xl font-bold mt-2 border-t border-gray-200 dark:border-neutral-700 pt-2">
                    <span>Total:</span>
                    <span>PHP
                        <!-- Display total with 2 decimal places -->
                        {{ number_format($this->subtotal, 2, '.', ',') }}</span>
                </div>
                <div
                    class="flex justify-between items-center text-lg font-bold mt-2 border-t border-gray-200 dark:border-neutral-700 pt-2">
                    <span>Change Given:</span>
                    <span>PHP
                        <!-- Display change with 2 decimal places -->
                        {{ number_format($this->change, 2, '.', ',') }}</span>
                </div>
            </div>
        </div>

        <div class="flex-shrink-0 mt-6">

            <button wire:click="checkout" wire:loading.attr="disabled" class="w-full py-4 bg-green-600 text-white font-bold text-lg rounded-xl
                       transition-colors duration-200 hover:bg-green-700 disabled:opacity-50
                       disabled:cursor-not-allowed shadow-lg">
                Complete Sale
            </button>
        </div>
    </div>
     <script>

        function printReceipt(url) {
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = url;

        iframe.onload = function () {
            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }, 500); // Small delay to ensure content is fully rendered
        };

        document.body.appendChild(iframe);
    }
    </script>
</div>
