@php
    // Pre-fill the Alpine item rows from existing items (edit) or one blank row (create).
    $initialItems = $order->exists
        ? $order->items->map(fn ($i) => [
            'id' => $i->id,
            'fuel_product' => $i->fuel_product,
            'unit_price' => (string) $i->unit_price,
            'quantity_liters' => (string) $i->quantity_liters,
            'remarks' => $i->remarks,
          ])->values()
        : collect([['id' => null, 'fuel_product' => '', 'unit_price' => '', 'quantity_liters' => '', 'remarks' => '']]);
@endphp

<div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mb-6">
    <h2 class="font-semibold text-slate-900 dark:text-white mb-4">Sales Order Information</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date Ordered</label>
            <input type="date" name="date_ordered" required value="{{ old('date_ordered', optional($order->date_ordered)->format('Y-m-d')) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sales Order No.</label>
            <input type="text" name="sales_order_no" required readonly value="{{ old('sales_order_no', $order->sales_order_no) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Supplier</label>
            <input type="text" name="supplier" required value="{{ old('supplier', $order->supplier) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">EWT Rate</label>
            <input type="number" step="0.00001" name="ewt_rate" required value="{{ old('ewt_rate', $order->ewt_rate) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">0.005 = 0.5%</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">VAT Divisor</label>
            <input type="number" step="0.01" name="vat_divisor" required value="{{ old('vat_divisor', $order->vat_divisor) }}"
                   class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">
        </div>
        <div class="md:col-span-3">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Remarks</label>
            <textarea name="remarks" rows="2" class="w-full rounded-lg border-slate-300 dark:border-slate-600 focus:border-amber-500 focus:ring-amber-500">{{ old('remarks', $order->remarks) }}</textarea>
        </div>
    </div>
</div>

<div x-data='{ items: @json($initialItems) }' class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="font-semibold text-slate-900 dark:text-white">Fuel Products</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Add DIESEL, REGULAR, or PREMIUM items under this sales order.</p>
        </div>
        <button type="button" @click="items.push({id: null, fuel_product: '', unit_price: '', quantity_liters: '', remarks: ''})"
                class="flex items-center gap-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            <x-icon name="plus" /> Add Fuel Product
        </button>
    </div>

    <template x-for="(item, index) in items" :key="index">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-start border-t border-slate-100 dark:border-slate-800 pt-4 mt-4 first:border-t-0 first:pt-0 first:mt-0">
            <input type="hidden" :name="`items[${index}][id]`" x-model="item.id">
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
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Price / Liter</label>
                <input type="number" step="0.001" :name="`items[${index}][unit_price]`" x-model="item.unit_price" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Total Liters</label>
                <input type="number" step="0.01" :name="`items[${index}][quantity_liters]`" x-model="item.quantity_liters" required class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Line Total</label>
                <p class="pt-2 text-sm font-semibold text-slate-700 dark:text-slate-300" x-text="'₱' + ((parseFloat(item.unit_price || 0) * parseFloat(item.quantity_liters || 0)).toFixed(2))"></p>
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Remarks</label>
                    <input type="text" :name="`items[${index}][remarks]`" x-model="item.remarks" class="w-full rounded-lg border-slate-300 dark:border-slate-600 text-sm">
                </div>
                <button type="button" @click="items.splice(index, 1)" x-show="items.length > 1"
                        class="rounded-lg border border-red-200 dark:border-red-900 p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
                    <x-icon name="trash" />
                </button>
            </div>
        </div>
    </template>
</div>

<div class="flex gap-3">
    <button type="submit" data-loading-text="Saving order…" class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-amber-600 disabled:opacity-70 disabled:cursor-wait transition-colors duration-200">Save Sales Order</button>
    <a href="{{ route('fuel-sales-orders.index') }}" class="rounded-lg border border-slate-300 dark:border-slate-600 px-5 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Cancel</a>
</div>
