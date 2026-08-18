@php
    $initialItems = $purchase->exists
        ? $purchase->items->map(fn ($i) => [
            'id' => $i->id,
            'fuel_product' => $i->fuel_product,
            'liters' => (string) $i->liters,
            'freight_alwin' => (string) $i->freight_alwin,
            'freight_tanker' => (string) $i->freight_tanker,
            'freight_040' => (string) $i->freight_040,
            'amount_per_liter' => (string) $i->amount_per_liter,
            'selling_price' => (string) $i->selling_price,
            'ewt_rate' => (string) $i->ewt_rate,
            'remarks' => $i->remarks,
          ])->values()
        : collect([['id' => null, 'fuel_product' => '', 'liters' => '', 'freight_alwin' => '0', 'freight_tanker' => '0', 'freight_040' => '0.400', 'amount_per_liter' => '', 'selling_price' => '', 'ewt_rate' => '0.01', 'remarks' => '']]);

    $salesOrderOptions = $salesOrders->map(fn ($so) => [
        'id' => $so->id,
        'label' => $so->sales_order_no . ' - ' . $so->supplier . ' | ' . $so->stock_label,
    ])->values();
@endphp

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mb-6">
    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Sales Order Reference</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
        <div class="md:col-span-2"
             style="position: relative;"
             x-data='{
                open: false,
                search: "",
                options: @json($salesOrderOptions),
                selectedId: @js(old("fuel_sales_order_id", $purchase->fuel_sales_order_id)),
                get selectedLabel() {
                    const found = this.options.find(o => o.id == this.selectedId);
                    return found ? found.label : "";
                },
                get filteredOptions() {
                    if (!this.search) return this.options;
                    const q = this.search.toLowerCase();
                    return this.options.filter(o => o.label.toLowerCase().includes(q));
                },
                select(option) {
                    this.selectedId = option.id;
                    this.open = false;
                    this.search = "";
                },
                openDropdown() {
                    this.open = true;
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                }
             }'
             @click.outside="open = false"
             @keydown.escape.window="open = false">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sales Order</label>

            <input type="hidden" name="fuel_sales_order_id" :value="selectedId" required>

            <button type="button" @click="open ? (open = false) : openDropdown()"
                    class="w-full flex items-center justify-between rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-left text-sm focus:border-amber-500 focus:ring-amber-500">
                <span :class="selectedLabel ? 'text-slate-900 dark:text-white' : 'text-slate-400'"
                      x-text="selectedLabel || 'Select an option'"></span>
                <x-icon name="chevron-down" class="w-4 h-4 text-slate-400 shrink-0" />
            </button>

            <div x-show="open" x-cloak x-transition
                 class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg"
                 style="position: absolute; z-index: 20; top: 100%; left: 0; right: 0; margin-top: 4px;">
                <div class="p-2 border-b border-slate-100 dark:border-slate-800">
                    <input type="text" x-model="search" x-ref="searchInput"
                           placeholder="Start typing to search..."
                           class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm focus:border-amber-500 focus:ring-amber-500">
                </div>
                <ul class="max-h-64 overflow-y-auto py-1" style="max-height: 16rem; overflow-y: auto;">
                    <template x-for="option in filteredOptions" :key="option.id">
                        <li @click="select(option)"
                            class="cursor-pointer px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-amber-50 dark:hover:bg-slate-800"
                            :class="option.id == selectedId ? 'bg-amber-50 dark:bg-slate-800 font-medium' : ''"
                            x-text="option.label"></li>
                    </template>
                    <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-sm text-slate-400">No results found.</li>
                </ul>
            </div>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Only sales orders with remaining stock should be used for new products.</p>
        </div>
    </div>
</div>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mb-6">
    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Customer and Delivery Details</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Customer</label>
            <input type="text" name="customer" required placeholder="e.g. Dave Gas Station" value="{{ old('customer', $purchase->customer) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanker Details</label>
            <input type="text" name="tanker_details" placeholder="e.g. Tanker 3" value="{{ old('tanker_details', $purchase->tanker_details) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Filling this in auto-creates a tanker delivery record below once saved.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Order No. / Details</label>
            <input type="text" name="order_no_details" value="{{ old('order_no_details', $purchase->order_no_details) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ATL Date</label>
            <input type="date" name="atl_date" value="{{ old('atl_date', optional($purchase->atl_date)->format('Y-m-d')) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">ATL No.</label>
            <input type="text" name="atl_no" value="{{ old('atl_no', $purchase->atl_no) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div class="md:col-span-3">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Remarks</label>
            <textarea name="remarks" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">{{ old('remarks', $purchase->remarks) }}</textarea>
        </div>
    </div>
</div>

<div x-data='{ items: @json($initialItems) }' class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-semibold text-slate-900 dark:text-white">Fuel Products</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Products sold from the selected sales order's remaining stock.</p>
        </div>
        <button type="button" @click="items.push({id: null, fuel_product: '', liters: '', freight_alwin: '0', freight_tanker: '0', freight_040: '0.400', amount_per_liter: '', selling_price: '', ewt_rate: '0.01', remarks: ''})"
                class="flex items-center gap-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            <x-icon name="plus" /> Add Fuel Product
        </button>
    </div>

    <template x-for="(item, index) in items" :key="index">
        <div class="border-t border-slate-100 dark:border-slate-800 pt-4 mt-4 first:border-t-0 first:pt-0 first:mt-0">
            <input type="hidden" :name="`items[${index}][id]`" x-model="item.id">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Fuel Product</label>
                    <select :name="`items[${index}][fuel_product]`" x-model="item.fuel_product" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                        <option value="">Select</option>
                        <option value="DIESEL">DIESEL</option>
                        <option value="REGULAR">REGULAR</option>
                        <option value="PREMIUM">PREMIUM</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Liters</label>
                    <input type="number" step="0.01" :name="`items[${index}][liters]`" x-model="item.liters" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Amount / Liter</label>
                    <input type="number" step="0.001" :name="`items[${index}][amount_per_liter]`" x-model="item.amount_per_liter" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Selling Price</label>
                    <input type="number" step="0.001" :name="`items[${index}][selling_price]`" x-model="item.selling_price" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Freight Alwin</label>
                    <input type="number" step="0.001" :name="`items[${index}][freight_alwin]`" x-model="item.freight_alwin" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Freight Tanker</label>
                    <input type="number" step="0.001" :name="`items[${index}][freight_tanker]`" x-model="item.freight_tanker" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">0.40 Freight</label>
                    <input type="number" step="0.001" :name="`items[${index}][freight_040]`" x-model="item.freight_040" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">EWT Rate</label>
                    <input type="number" step="0.00001" :name="`items[${index}][ewt_rate]`" x-model="item.ewt_rate" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Remarks</label>
                    <input type="text" :name="`items[${index}][remarks]`" x-model="item.remarks" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <div class="flex items-end justify-end">
                    <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1"
                            class="rounded-lg border border-red-200 dark:border-red-900 p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
                        <x-icon name="trash" />
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mb-6">
    <h2 class="font-semibold text-slate-900 dark:text-white mb-1">Deductions / Expenses</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Net Income = Payables − Sub-total w/ Freight − Expenses.</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Garage</label>
            <input type="number" step="0.01" name="garage" value="{{ old('garage', $purchase->garage ?? 0) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Agent Comm</label>
            <input type="number" step="0.01" name="agent_comm" value="{{ old('agent_comm', $purchase->agent_comm ?? 0) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Receiver</label>
            <input type="number" step="0.01" name="receiver" value="{{ old('receiver', $purchase->receiver ?? 0) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Others Amount</label>
            <input type="number" step="0.01" name="others_amount" value="{{ old('others_amount', $purchase->others_amount ?? 0) }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
        </div>
        <div class="md:col-span-4">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Others Comment</label>
            <textarea name="others_comment" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">{{ old('others_comment', $purchase->others_comment) }}</textarea>
        </div>
    </div>
</div>

<div class="flex gap-3">
    <button type="submit" data-loading-text="Saving purchase…" class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-70 disabled:cursor-wait transition-colors duration-200">Save Customer Purchase</button>
    <a href="{{ route('fuel-customer-purchases.index') }}" class="rounded-lg border border-slate-300 dark:border-slate-600 px-5 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Cancel</a>
</div>