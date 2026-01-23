<template>
    <div class="w-full h-[calc(100vh-48px)] relative">
        <div class="w-full h-20 bg-amber-200 border-b-4 border-blue-900 flex items-center justify-between px-4 gap-1">
            <div class="flex items-center gap-2">
                <button @click="openCreate"
                    class="w-16 h-16 bg-white rounded-sm shadow hover:shadow-lg hover:bg-white/50 flex flex-col items-center justify-center gap-2">
                    <div class="w-10 h-8 flex items-center justify-center">
                        <i class='bx bx-plus text-3xl'></i>
                    </div>
                    <span class="text-xs text-center">Produk</span>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <Link href="#"
                    class="w-16 h-16 bg-white rounded-sm shadow hover:shadow-lg hover:bg-white/50 flex flex-col items-center justify-center gap-2">
                    <div class="w-10 h-8 flex items-center justify-center">
                        <i class='bx bx-category text-3xl'></i>
                    </div>
                    <span class="text-xs text-center">Kategori</span>
                </Link>
            </div>
        </div>

        <div class="w-full h-[calc(100vh-120px)] bg-indigo-100 overflow-hidden flex flex-col">
            <div class="w-full h-20 border-b flex items-center justify-start gap-4 px-4 bg-white/50">
                <label class="flex flex-col w-32">
                    <span class="text-[10px] uppercase font-bold mb-1">Per Halaman</span>
                    <select v-model="filter.perPage" @change="submitSearch"
                        class="bg-white px-2 h-8 rounded-sm outline-none border text-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </label>
                <label class="flex flex-col w-1/4">
                    <span class="text-[10px] uppercase font-bold mb-1">Cari Nota / Pelanggan</span>
                    <input v-model="filter.search" @keyup.enter="submitSearch" type="search"
                        class="bg-white px-4 h-8 rounded-sm outline-none border text-sm"
                        placeholder="Ketik lalu enter...">
                </label>
                <button @click="submitSearch"
                    class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded shadow-sm mt-4 uppercase">
                    Filter
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <div class="min-w-2xl bg-white rounded shadow-sm overflow-hidden">
                    <div
                        class="grid grid-cols-[50px_180px_1fr_150px_150px_120px_100px] items-center text-center bg-blue-900 text-white font-semibold text-xs uppercase">
                        <div class="py-3 border-r border-white/10">No.</div>
                        <div class="py-3 border-r border-white/10 text-left px-4">Nomer Nota</div>
                        <div class="py-3 border-r border-white/10 text-left px-4">Pelanggan</div>
                        <div class="py-3 border-r border-white/10 text-left px-4">Marketing</div>
                        <div class="py-3 border-r border-white/10">Nominal</div>
                        <div class="py-3 border-r border-white/10">Status</div>
                        <div class="py-3">Aksi</div>
                    </div>

                    <div v-for="(pnj, index) in penjualan" :key="pnj.id"
                        class="grid grid-cols-[50px_180px_1fr_150px_150px_120px_100px] items-center text-center border-b hover:bg-slate-50 transition-colors">
                        <div class="py-3 text-xs text-slate-400 font-mono">{{ index + 1 }}</div>
                        <div class="py-3 px-4 text-left font-bold text-blue-700 text-xs">{{ pnj.nomer }}</div>
                        <div class="py-3 px-4 text-left text-xs uppercase">{{ pnj.pelanggan?.nama || 'Umum' }}</div>
                        <div class="py-3 px-4 text-left text-xs italic">{{ pnj.marketing?.nama || '-' }}</div>
                        <div class="py-3 text-xs font-bold">{{ format(pnj.total_penjualan) }}</div>
                        <div class="py-3 flex justify-center">
                            <span
                                class="px-2 py-0.5 bg-red-100 text-red-600 rounded-full text-[10px] font-bold border border-red-200">
                                {{ pnj.status }}
                            </span>
                        </div>
                        <div class="py-3 flex justify-center">
                            <Link v-if="pnj.status === 'UNPAID'" :href="`/ecer/penjualan/pembayaran/${pnj.id}`"
                                class="px-3 py-1 bg-blue-600 text-white rounded text-[10px] font-bold hover:bg-blue-700 shadow-sm transition-all active:scale-95">
                                BAYAR
                            </Link>
                            <Link v-if="pnj.status === 'DP'" :href="`/ecer/penjualan/pembayaran/${pnj.id}`"
                                class="px-3 py-1 bg-blue-600 text-white rounded text-[10px] font-bold hover:bg-blue-700 shadow-sm transition-all active:scale-95">
                                BAYAR
                            </Link>
                            <Link v-if="pnj.status === 'LUNAS'" :href="`/ecer/penjualan/pembayaran/print/${pnj.id}`"
                                class="px-3 py-1 bg-blue-600 text-white rounded text-[10px] font-bold hover:bg-blue-700 shadow-sm transition-all active:scale-95">
                                {{ pnj.status }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="penjualan.length === 0" class="p-10 text-center text-slate-400 italic">
                        Tidak ada data penjualan UNPAID.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    penjualan: Array, // Karena di controller menggunakan ->get(), maka ini Array
})

const filter = reactive({
    search: '',
    perPage: '25',
})

const format = (n) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
}).format(n || 0)

const submitSearch = () => {
    router.get(route('ecer.penjualan.pembayaran.index'), filter, {
        preserveState: true,
        replace: true
    })
}

const openCreate = () => {
    router.visit(route('ecer.penjualan.transaksi.index'))
}
</script>