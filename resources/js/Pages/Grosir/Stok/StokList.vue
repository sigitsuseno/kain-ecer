<template>
    <div class="w-full h-[calc(100vh-40px)] bg-gray-50 flex flex-col">
        <div class="w-full h-16 bg-white border-b flex items-center justify-between px-6 shadow-sm">
            <h1 class="font-bold text-xl text-slate-800">Data Stok</h1>
            <Link href="/ecer/stok/create"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium transition">
                Transfer</Link>
        </div>

        <div class="p-6 bg-white border-b flex flex-wrap items-end gap-4">
            <div class="flex flex-col gap-1">
                <span class="text-xs font-semibold text-slate-500 uppercase">Cari Produk</span>
                <input v-model="filter.search" @keyup.enter="submitSearch" type="text" placeholder="Ketik lalu enter..."
                    class="border rounded-md px-3 py-2 text-sm w-64 focus:ring-2 focus:ring-blue-500 outline-none" />
            </div>

            <div class="flex flex-col gap-1">
                <span class="text-xs font-semibold text-slate-500 uppercase">Baris</span>
                <select v-model="filter.perPage" @change="submitSearch"
                    class="border rounded-md px-3 py-2 text-sm bg-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            <button @click="submitSearch"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm font-medium transition">
                Terapkan Filter
            </button>
        </div>

        <div class="flex-1 overflow-auto p-6">
            <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-bottom">
                        <tr class="text-xs font-bold uppercase text-slate-600">
                            <th class="p-4 w-12 text-center">No</th>
                            <th class="p-4">Nomor Stok</th>
                            <th class="p-4">Nama Produk</th>
                            <th class="p-4">Varian</th>
                            <th class="p-4 text-right">Total Qty</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <tr v-for="(item, index) in stoks.data" :key="item.id"
                            class="border-b hover:bg-blue-50/50 transition">
                            <td class="p-4 text-center text-slate-400">{{ stoks.from + index }}</td>
                            <td class="p-4 font-mono font-bold text-blue-600">{{ item.nomer }}</td>
                            <td class="p-4 font-semibold text-slate-700">{{ item.varian?.produk?.nama || '-' }}</td>
                            <td class="p-4 italic text-slate-500">{{ item.varian?.nama || '-' }}</td>
                            <td class="p-4 text-right font-bold text-slate-800">
                                {{ formatQty(item.details) }}
                            </td>
                        </tr>
                        <tr v-if="stoks.data.length === 0">
                            <td colspan="5" class="p-10 text-center text-slate-400 italic">
                                Belum ada data stok yang tersedia.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-between items-center text-sm text-slate-500" v-if="stoks.last_page > 1">
                <div>Menampilkan {{ stoks.from }} sampai {{ stoks.to }} dari {{ stoks.total }} data</div>
                <div class="flex gap-2">
                    <Link v-if="stoks.prev_page_url" :href="stoks.prev_page_url"
                        class="px-4 py-2 border rounded bg-white hover:bg-gray-50">
                        Sebelumnya
                    </Link>
                    <Link v-if="stoks.next_page_url" :href="stoks.next_page_url"
                        class="px-4 py-2 border rounded bg-white hover:bg-gray-50">
                        Selanjutnya
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'

// Pastikan props 'stoks' dikirim dari controller menggunakan ->paginate()
const props = defineProps({
    stoks: {
        type: Object,
        default: () => ({ data: [] })
    }
})

// State untuk filter
const filter = reactive({
    search: '',
    perPage: 10
})

// Fungsi untuk hitung total qty dari detail stok
const formatQty = (details) => {
    if (!details || !Array.isArray(details)) return 0
    return details.reduce((acc, curr) => acc + Number(curr.qty || 0), 0)
}

// Fungsi pencarian
const submitSearch = () => {
    router.get(window.location.pathname, {
        search: filter.search,
        perPage: filter.perPage
    }, {
        preserveState: true,
        replace: true
    })
}
</script>