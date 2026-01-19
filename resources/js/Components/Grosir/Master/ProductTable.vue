<script setup>
import { ref } from 'vue';

const emit = defineEmits(['create', 'edit', 'view', 'search']);

const props = defineProps({
    products: Object,
    categories: Array,
    kategoriNama: Array,
});

const search = ref('');
const kategori = ref('');
const perPage = ref('10'); // Default 10

function submitSearch(pageUrl = null) {
    // Jika ada pageUrl (dari klik nomor halaman), kita bisa mengekstrak nomor pagenya saja
    // Namun cara paling simpel adalah mengirim objek filter lengkap
    emit('search', {
        search: search.value,
        kategori: kategori.value,
        perPage: perPage.value,
        page: typeof pageUrl === 'string' ? pageUrl : null
    });
}

function handlePagination(link) {
    if (!link.url || link.active) return;

    const url = new URL(link.url);
    const page = url.searchParams.get('page');

    emit('search', {
        search: search.value,
        kategori: kategori.value,
        perPage: perPage.value,
        page: page
    });
}
function resetFilter() {
    search.value = '';
    kategori.value = '';
    emit('search', {});
}
</script>

<template>
    <div class="space-y-4 p-1">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <select v-model="perPage" @change="submitSearch"
                    class="px-4 py-2 bg-white border border-slate-200 rounded-md focus:ring-2 focus:ring-indigo-500/20 outline-none text-sm cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>

                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input v-model="search" @keyup.enter="submitSearch" placeholder="Cari kain atau kode..."
                        class="pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-md focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all w-64 text-sm" />
                </div>

                <select v-model="kategori" @change="submitSearch"
                    class="px-4 py-2 bg-white border border-slate-200 rounded-md focus:ring-2 focus:ring-indigo-500/20 outline-none text-sm cursor-pointer">
                    <option value="">Kategori</option>
                    <option v-for="nps in kategoriNama" :key="nps.id" :value="nps.id">
                        {{ nps.nama_kategori }}
                    </option>
                </select>

                <button @click="submitSearch"
                    class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-md transition-all active:scale-95 shadow-sm">
                    Filter
                </button>
                <button v-if="search || kategori" @click="resetFilter"
                    class="px-5 py-2 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-semibold rounded-md transition-all active:scale-95">
                    Reset
                </button>
            </div>

            <button @click="$emit('create')"
                class="flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-md transition-all active:scale-95 shadow-lg shadow-indigo-200">
                <span class="text-lg">+</span> Tambah Produk
            </button>

        </div>

        <div
            class="w-full h-[calc(100vh-230px)] bg-white rounded-2xl border border-slate-200 shadow-sm overflow-y-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-200">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Produk &
                            Spek</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Rincian Produk
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Jumlah Roll
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Panjang
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">

                    <tr v-for="p in products.data" :key="p.id" class="hover:bg-indigo-50/30 transition-colors group">

                        <td class="px-6 py-4">
                            <div class="font-semibold text-slate-800 group-hover:text-indigo-700 transition-colors">
                                {{ p.produk.nama_produk }}
                            </div>
                            <div class="text-xs text-slate-900 font-mono uppercase tracking-tighter">
                                {{ p.nama_varian }}
                            </div>
                        </td>

                        <td class="px-6 py-4 max-w-125">
                            <div class="flex flex-wrap gap-2">
                                <div v-for="rl in p.stok_fisiks" :key="rl.id"
                                    class="border p-2 rounded bg-white shadow-sm">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="det in rl.detail_pakets" :key="det.id"
                                            class="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded font-bold">
                                            {{ det.qty }} Yd
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 font-medium text-center">
                            {{ p.total_qty ?? 0 }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 font-medium text-center">
                            {{ p.total_panjang ?? 0 }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button @click="$emit('view', p)"
                                class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold rounded-lg transition-all">
                                Detail
                            </button>
                            <button @click="$emit('edit', p)"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white text-xs font-semibold rounded-lg transition-all">
                                Edit
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between px-2">
            <p class="text-xs text-slate-500">
                Menampilkan <span class="font-bold">{{ products.from }}</span> sampai <span class="font-bold">{{
                    products.to }}</span> dari <span class="font-bold">{{ products.total }}</span> produk
            </p>
            <div class="flex gap-1">
                <button v-for="link in products.links" :key="link.label" :disabled="!link.url || link.active"
                    @click="emit('search', { page: link.label })" v-html="link.label"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all" :class="[
                        link.active
                            ? 'bg-indigo-600 text-white shadow-md'
                            : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30'
                    ]" />
            </div>
        </div>
    </div>
</template>