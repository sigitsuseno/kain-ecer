<template>
    <div class="w-full h-[calc(100vh-48px)] relative">

        <!-- HEADER -->
        <div class="w-full h-20 bg-amber-200 border-b-4 border-blue-900 flex items-center justify-between px-4">
            <div class="font-bold text-lg">
                DATA STOK
            </div>
        </div>

        <!-- FILTER -->
        <div class="w-full h-20 border-b flex items-center gap-4 px-4 bg-white/50">
            <label class="flex flex-col w-32">
                <span class="text-[10px] uppercase font-bold mb-1">Per Halaman</span>
                <select v-model="filter.perPage" @change="submitSearch"
                    class="bg-white px-2 h-8 rounded-sm border text-sm">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </label>

            <label class="flex flex-col w-1/3">
                <span class="text-[10px] uppercase font-bold mb-1">
                    Cari Nomor / Produk / Varian
                </span>
                <input v-model="filter.search" @keyup.enter="submitSearch" type="search"
                    class="bg-white px-4 h-8 rounded-sm border text-sm" placeholder="Ketik lalu enter...">
            </label>

            <button @click="submitSearch"
                class="px-5 py-2 bg-slate-800 text-white text-xs font-bold rounded shadow mt-4">
                Filter
            </button>
        </div>

        <!-- TABLE -->
        <div class="flex-1 overflow-y-auto p-4">
            <div class="min-w-2xl bg-white rounded shadow-sm overflow-auto">

                <!-- HEADER TABLE -->
                <div
                    class="grid grid-cols-[50px_150px_1fr_1fr_120px] bg-blue-900 text-white text-xs font-semibold uppercase">
                    <div class="py-3 text-center">No</div>
                    <div class="py-3 px-4 text-left">Nomor</div>
                    <div class="py-3 px-4 text-left">Produk</div>
                    <div class="py-3 px-4 text-left">Varian</div>
                    <div class="py-3 text-right px-4">Qty</div>
                </div>

                <!-- ROW -->
                <div v-for="(stok, index) in stoks.data" :key="stok.id"
                    class="grid grid-cols-[50px_150px_1fr_1fr_120px] border-b hover:bg-slate-50 text-xs">

                    <div class="py-3 text-center text-slate-400 font-mono">
                        {{ stoks.from + index }}
                    </div>

                    <div class="py-3 px-4 font-bold text-blue-700">
                        {{ stok.nomor }}
                    </div>

                    <div class="py-3 px-4 uppercase">
                        {{ stok.varian?.produk?.nama ?? '-' }}
                    </div>

                    <div class="py-3 px-4 italic">
                        {{ stok.varian?.nama ?? '-' }}
                    </div>

                    <div class="py-3 px-4 text-right font-bold">
                        {{ totalQty(stok.details) }}
                    </div>
                </div>

                <div v-if="stoks.data.length === 0" class="p-10 text-center text-slate-400 italic">
                    Tidak ada data stok.
                </div>
            </div>

            <!-- PAGINATION (AMAN) -->
            <div class="flex justify-end mt-4" v-if="stoks.last_page > 1">
                <div class="flex gap-1">
                    <Link v-if="stoks.prev_page_url" :href="stoks.prev_page_url"
                        class="px-3 py-1 text-xs border rounded">
                        ← Prev
                    </Link>

                    <span class="px-3 py-1 text-xs border rounded bg-blue-600 text-white">
                        {{ stoks.current_page }}
                    </span>

                    <Link v-if="stoks.next_page_url" :href="stoks.next_page_url"
                        class="px-3 py-1 text-xs border rounded">
                        Next →
                    </Link>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    stoks: Object,
})

const filter = reactive({
    search: usePage().props.ziggy?.query?.search ?? '',
    perPage: usePage().props.ziggy?.query?.perPage ?? 25,
})

const submitSearch = () => {
    router.get(route('ecer.stok.index'), filter, {
        preserveState: true,
        replace: true,
    })
}

const totalQty = (details = []) => {
    return details.reduce((sum, d) => sum + Number(d.qty || 0), 0)
}
</script>
