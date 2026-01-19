<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100 relative">
        <div class="text-2xl font-semibold text-primary py-2 px-6 mt-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class='bx bxs-package'></i> <span>Riwayat Penerimaan Barang</span>
            </div>
            <div>
                <Link href="/grosir/penerimaan/create"
                    class="px-4 h-9 flex items-center gap-2 text-sm bg-indigo-600 hover:bg-indigo-700 active:scale-90 text-white rounded shadow-md transition-all">
                    <i class='bx bx-plus'></i>
                    Buat Nota Baru
                </Link>
            </div>
        </div>

        <div class="px-4 w-full h-[calc(100vh-120px)] overflow-y-auto">
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="w-full flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <select v-model="params.per_page"
                            class="w-20 h-9 px-2 rounded border border-gray-300 text-sm outline-none focus:border-primary">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <div class="relative">
                            <i class='bx bx-search absolute left-3 top-2.5 text-gray-400'></i>
                            <input type="search" v-model="params.search"
                                class="w-72 h-9 pl-9 pr-4 text-sm border border-gray-300 rounded outline-none focus:border-primary"
                                placeholder="Cari No. Nota...">
                        </div>
                    </div>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 uppercase text-xs">
                                <th class="px-2 py-1 text-left border-b w-12">#</th>
                                <th class="px-2 py-1 text-left border-b">No. Nota</th>
                                <th class="px-2 py-1 text-left border-b">Ringkasan Produk</th>
                                <th class="px-2 py-1 text-center border-b">Total Roll</th>
                                <th class="px-2 py-1 text-center border-b">Total Panjang</th>
                                <th class="px-2 py-1 text-center border-b">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(p, index) in pembelians.data" :key="p.id"
                                class="hover:bg-blue-50/50 transition">
                                <td class="px-2 py-1 text-gray-500">{{ (pembelians.current_page - 1) *
                                    pembelians.per_page + index + 1 }}</td>
                                <td class="px-2 py-1 font-bold text-primary">
                                    <div class="flex flex-col">
                                        <span>
                                            {{ p.nomer_pembelian }}
                                        </span>
                                        {{ p.tanggal_transaksi }}
                                    </div>
                                </td>
                                <td class="px-2 py-1">
                                    <div class="flex flex-col gap-1">
                                        <div class="space-y-2">
                                            <div v-for="item in p.items.slice(0, 2)" :key="item.id"
                                                class="text-[11px] bg-gray-100 px-2 py-0.5 rounded text-gray-600 ">
                                                <div class="text-sm bg-semibold text-black underline">{{
                                                    item.varian?.produk?.nama_produk }} - {{
                                                        item.varian?.nama_varian }}
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    <div v-for="detail in item.details" :key="detail.id">
                                                        <span class="w-16">{{ detail.qty }}</span>
                                                    </div>
                                                </div>

                                                <span v-if="p.items.length > 2"
                                                    class="text-[10px] text-gray-400 italic">+{{
                                                        p.items.length - 2 }} produk lainnya</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2 py-1 text-center font-bold text-indigo-600">{{ p.total_jumlah_qty }} Rl
                                </td>
                                <td class="px-2 py-1 text-center font-bold text-blue-600">{{ p.total_jumlah_panjang }}
                                    Yd
                                </td>

                                <td class="px-2 py-1 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200"
                                            title="Detail">
                                            <i class='bx bx-show text-lg'></i>
                                        </button>
                                        <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200"
                                            title="Hapus">
                                            <i class='bx bx-trash text-lg'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="pembelians.data.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-gray-400 italic">Data tidak ditemukan
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ pembelians.from }} sampai {{ pembelians.to }} dari {{ pembelians.total }} data
                    </div>
                    <div class="flex gap-1">
                        <Link v-for="link in pembelians.links" :key="link.label" :href="link.url || '#'"
                            class="px-3 py-1 text-xs border rounded transition-all" :class="{
                                'bg-primary text-white border-primary': link.active,
                                'text-gray-400 pointer-events-none': !link.url,
                                'hover:bg-gray-100': link.url && !link.active
                            }" v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    pembelians: Object,
    filters: Object
});

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || '10'
});

// Fungsi debounce manual tanpa lodash
let timeout = null;

watch(params, (newParams) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/grosir/penerimaan', newParams, {
            preserveState: true,
            replace: true,
            preserveScroll: true, // Agar scroll tidak balik ke atas
        });
    }, 500); // Tunggu 500ms setelah user berhenti mengetik
}, { deep: true });
</script>>