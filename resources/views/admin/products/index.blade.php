<x-app-layout>
    <x-slot name="title">
        {{ $pageTitle ?? config('app.name', 'Laravel') }}
    </x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 disabled:opacity-25 transition">
                {{ __('Create Product') }}
            </a>
        </h2>

        <!-- Dropdown Menu -->
        <div class="relative inline-block text-left float-right" x-data="{ open: false }">
            <button @click="open = !open" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Menu
                <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.292 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="open" @click.outside="open = false" x-transition class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                    <a href="{{ route('categories.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Categories</a>
                    <a href="{{ route('tags.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Tags</a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div id="toast" class="fixed top-0 right-0 mt-4 mr-4 bg-green-500 text-white text-sm rounded-lg p-4">
                            {{ session('success') }}
                        </div>
                        <script>
                            setTimeout(() => {
                                const toast = document.getElementById('toast');
                                toast.style.display = 'none';
                            }, 3000);
                        </script>
                    @endif

                    <!-- Filters and Sorting -->
                    <div class="mb-4">
                        <form method="GET" action="{{ route('products.index') }}">
                            <div class="grid grid-cols-6 gap-4 mb-4">
                                <input type="text" name="filter[name]" value="{{ $filter['name'] ?? '' }}" placeholder="Search by name" class="border rounded px-3 py-2 w-full" />
                                <input type="number" name="filter[min_price]" value="{{ $filter['min_price'] ?? '' }}" placeholder="Min price" class="border rounded px-3 py-2 w-full" />
                                <input type="number" name="filter[max_price]" value="{{ $filter['max_price'] ?? '' }}" placeholder="Max price" class="border rounded px-3 py-2 w-full" />
                                <select name="filter[status]" class="border rounded px-3 py-2 w-full">
                                    <option value="">{{ __('Filter by status') }}</option>
                                    <option value="draft" {{ ($filter['status'] ?? '') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                    <option value="published" {{ ($filter['status'] ?? '') == 'published' ? 'selected' : '' }}>{{ __('Published') }}</option>
                                    <option value="archived" {{ ($filter['status'] ?? '') == 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                                </select>
                                <select name="filter[category]" class="border rounded px-3 py-2 w-full">
                                    <option value="">{{ __('Filter by category') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ ($filter['category'] ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">{{ __('Filter') }}</button>
                                <a href="{{ route('products.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md">{{ __('Reset') }}</a>
                            </div>
                            <div class="flex items-center space-x-4 mb-4">
                                <select name="sort_by" class="border rounded px-3 py-2 w-1/3">
                                    <option value="">{{ __('Sort by') }}</option>
                                    <option value="name" {{ ($filter['sort_by'] ?? '') == 'name' ? 'selected' : '' }}>{{ __('Name') }}</option>
                                    <option value="price" {{ ($filter['sort_by'] ?? '') == 'price' ? 'selected' : '' }}>{{ __('Price') }}</option>
                                    <option value="created_at" {{ ($filter['sort_by'] ?? '') == 'created_at' ? 'selected' : '' }}>{{ __('Created At') }}</option>
                                </select>
                                
                                <select name="sort_direction" class="border rounded px-3 py-2 w-1/3">
                                    <option value="asc" {{ ($filter['sort_direction'] ?? 'asc') == 'asc' ? 'selected' : '' }}>{{ __('Ascending') }}</option>
                                    <option value="desc" {{ ($filter['sort_direction'] ?? 'asc') == 'desc' ? 'selected' : '' }}>{{ __('Descending') }}</option>
                                </select>
                                
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">{{ __('Sort') }}</button>
                            </div>
                            
                        </form>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="mt-4 mb-4">
                        {{ $products->links() }} <!-- This will render the pagination controls -->
                    </div>

                    <!-- Products Display -->
                    <div class="overflow-x-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            @foreach($products as $product)
                                <div class="border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-lg transition duration-150 ease-in-out bg-white">
                                    <div class="flex justify-center mb-4">
                                        @if($product->image_url)
                                            <div class="relative w-24 h-24 overflow-hidden rounded-md border border-gray-200">
                                                <img src="{{ asset($product->image_url) }}" alt="{{ $product->name }}" class="w-full h-full object-cover transition-transform duration-300 ease-in-out transform hover:scale-105">
                                            </div>
                                        @else
                                            <span class="text-gray-500">{{ __('No Image') }}</span>
                                        @endif
                                    </div>
                                    <h4 class="font-semibold text-lg text-gray-800 mb-1">
                                        <a href="{{ route('products.show', $product->id) }}" class="hover:underline">{{ $product->name }}</a>
                                    </h4>
                                    <p class="text-gray-600 text-sm mb-1">SKU: {{ $product->sku ?? 'N/A' }}</p>
                                    <p class="text-gray-800 font-bold text-sm mb-1">Price: ${{ number_format($product->price, 2) }}</p>
                                    <p class="text-gray-600 text-sm mb-1">Status: <span class="{{ $product->status == 'active' ? 'text-green-600' : 'text-red-600' }}">{{ ucfirst($product->status) }}</span></p>
                                    <p class="text-gray-600 text-sm">Stock: {{ $product->stock_quantity > 0 ? $product->stock_quantity . ' in stock' : 'Out of stock' }}</p>
                    
                                    <div class="mt-2 mb-4">
                                        @foreach($product->categories as $category)
                                            <span class="inline-block bg-blue-500 text-white text-xs font-semibold px-2 py-1 rounded-full mr-2">
                                                {{ $category->name }}
                                            </span>
                                        @endforeach
                                    </div>
                    
                                    <div class="mt-4 flex justify-between">
                                        <a href="{{ route('products.edit', $product->id) }}" class="text-blue-600 hover:underline text-xs flex items-center">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline-block" onsubmit="return confirmDelete(event, '{{ $product->name }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline text-xs flex items-center">
                                                <i class="fas fa-trash mr-1"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="mt-4">
                        {{ $products->links() }} <!-- This will render the pagination controls -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(event, productName) {
            event.preventDefault();
            if (confirm('Are you sure you want to delete the product "' + productName + '"?')) {
                const form = event.target;
                form.submit();
            }
        }
    </script>
</x-app-layout>
