<template>
    <div class="w-full h-[calc(100vh-48px)] relative">
        <!-- <div class="w-full h-20 bg-amber-200 border-b-4 border-blue-900 flex items-center justify-between px-4 gap-1">
            <div class="flex items-center gap-2">
                <button @click="openCreate"
                    class="w-16 h-16 bg-white rounded-sm shadow hover:shadow-lg hover:bg-white/50 flex flex-col items-center justify-center gap-2">
                    <div class="w-10 h-8 flex items-center justify-center">
                        <i class='bx bx-plus text-3xl'></i>
                    </div>
                    <span class="text-xs text-center">Kasir</span>
                </button>
            </div>
        </div> -->
        <div class="w-full h-14 bg-amber-200 flex items-end justify-start gap-1 px-4">
            <Link href="/ecer/penjualan" class="px-4 py-2 bg-button rounded-t-md text-white">Penjualan</Link>
            <Link href="/ecer/penjualan/pembayaran" class="px-4 py-2 bg-button rounded-t-md text-white">Pembayaran
            </Link>
        </div>

        <div class="w-full h-[calc(100vh-120px)] bg-indigo-100 overflow-hidden flex flex-col">
            <div class="flex justify-between bg-white/50 border-b-2 border-primary/50">
                <div class="w-full h-20 flex items-center justify-start gap-4 px-4 ">
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
                <!-- <div class="px-4 flex items-center justify-end">
                    <button @click="openCreate"
                        class="px-3 h-10 whitespace-nowrap bg-button flex items-center justify-center"><i
                            class='bx bx-plus text-2xl'></i> Nota Penjualan
                    </button>
                </div> -->
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <div class="min-w-2xl bg-white rounded shadow-sm overflow-auto">
                    <div
                        class="grid grid-cols-[50px_150px_1fr_150px_150px_120px_100px] items-center text-center bg-blue-900 text-white font-semibold text-xs uppercase">
                        <div class="py-3 border-r border-white/10">No.</div>
                        <div class="py-3 border-r border-white/10 text-left px-4">Nomer Nota</div>
                        <div class="py-3 border-r border-white/10 text-left px-4">Pelanggan</div>
                        <div class="py-3 border-r border-white/10 text-left px-4">PJ</div>
                        <div class="py-3 border-r border-white/10">Nominal</div>
                        <div class="py-3 border-r border-white/10">Status</div>
                        <div class="py-3">Aksi</div>
                    </div>

                    <div v-for="(pnj, index) in penjualan.data" :key="pnj.id"
                        class="grid grid-cols-[50px_150px_1fr_150px_150px_120px_100px] items-center text-center border-b hover:bg-slate-50">
                        <div class="py-3 text-xs text-slate-400 font-mono">
                            {{ penjualan.from + index }}
                        </div>

                        <div class="py-3 px-4 text-left font-bold text-blue-700 text-xs">
                            {{ pnj.nomer }}
                        </div>

                        <div class="py-3 px-4 text-left text-xs uppercase">
                            {{ pnj.pelanggan?.nama ?? 'Umum' }}
                        </div>

                        <div class="py-3 px-4 text-left text-xs italic">
                            {{ pnj.marketing?.nama ?? '-' }}
                        </div>

                        <div class="py-3 text-xs font-bold text-right">
                            {{ format(pnj.total_penjualan) }}
                        </div>

                        <div class="py-3 flex justify-center">
                            <span :class="[
                                'px-2 py-0.5 rounded-full text-[10px] font-bold border',
                                statusClass(pnj.status)
                            ]">
                                {{ pnj.status }}
                            </span>
                        </div>

                        <div class="py-3 flex justify-center gap-1">
                            <Link v-if="pnj.status === 'DP'" :href="`/ecer/penjualan/pembayaran/${pnj.id}`"
                                class="px-3 py-2 bg-blue-600 text-white rounded text-[10px] font-bold hover:bg-blue-700 shadow-sm transition-all active:scale-95">
                                BAYAR
                            </Link>
                            <div v-if="pnj.status === 'LUNAS'">
                                <Link :href="`/ecer/penjualan/pembayaran/print/${pnj.id}`"
                                    class="px-3 py-2 bg-blue-600 text-white rounded text-[10px] font-bold hover:bg-blue-700 shadow-sm transition-all active:scale-95">
                                    PRINT
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div v-if="penjualan.data.length === 0" class="p-10 text-center text-slate-400 italic">
                        Tidak ada data penjualan.
                    </div>
                </div>
                <div class="flex justify-end mt-4" v-if="penjualan.last_page > 1">
                    <div class="flex gap-1">

                        <Link v-if="penjualan.prev_page_url" :href="penjualan.prev_page_url"
                            class="px-3 py-1 text-xs border rounded hover:bg-slate-100">
                            ← Prev
                        </Link>

                        <span class="px-3 py-1 text-xs border rounded bg-blue-600 text-white">
                            {{ penjualan.current_page }}
                        </span>

                        <Link v-if="penjualan.next_page_url" :href="penjualan.next_page_url"
                            class="px-3 py-1 text-xs border rounded hover:bg-slate-100">
                            Next →
                        </Link>

                    </div>
                </div>


            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    penjualan: Object,
})

// Mengambil nilai filter dari URL saat ini
const urlParams = new URLSearchParams(window.location.search);

const filter = reactive({
    search: urlParams.get('search') ?? '',
    perPage: urlParams.get('perPage') ?? 10,
})

const submitSearch = () => {
    router.get(route('ecer.penjualan.index'), filter, {
        preserveState: true,
        replace: true,
    })
}

// Opsional: Otomatis search saat perPage diganti
watch(() => filter.perPage, () => {
    submitSearch();
});

const openCreate = () => {
    router.visit(route('ecer.penjualan.transaksi.index'))
}

const format = (n) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(n || 0)

const statusClass = (status) => {
    const classes = {
        UNPAID: 'bg-red-100 text-red-600 border-red-200',
        DP: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        LUNAS: 'bg-green-100 text-green-700 border-green-200',
    };
    return classes[status] ?? 'bg-slate-100 text-slate-500';
}
</script>