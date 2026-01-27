<template>
    <div class="w-full h-[calc(100vh-40px)] overflow-hidden bg-slate-50 relative text-slate-700">

        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-600 p-2 rounded-lg text-white shadow-lg">
                        <i class='bx bxs-package text-xl'></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">PRODUK</h1>
                        <p class="text-xs text-slate-500">Manajemen stok varian produk</p>
                    </div>
                </div>
                <button @click="openCreate"
                    class="h-10 flex items-center gap-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all active:scale-95 shadow-lg shadow-indigo-200">
                    <i class='bx bx-plus text-lg'></i> Tambah Produk
                </button>
            </div>

            <div
                class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-3 flex-1 min-w-[300px]">
                    <div class="relative flex-1">
                        <i class='bx bx-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg'></i>
                        <input v-model="params.search" type="text" placeholder="Cari nama produk atau kode warna..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <select v-model="params.per_page"
                        class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                        <option value="10">10 Baris</option>
                        <option value="25">25 Baris</option>
                        <option value="50">50 Baris</option>
                        <option value="100">100 Baris</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="px-6 pb-6 h-[calc(100vh-230px)]">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm h-full flex flex-col overflow-hidden">
                <div class="flex-1 overflow-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-slate-50 z-10 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase w-40">Produk & Spek</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase">Rincian Stok</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase text-center w-20">Roll
                                </th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase text-center w-20">
                                    Panjang
                                </th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in products.data" :key="p.id"
                                class="hover:bg-indigo-50/30 transition-colors group">
                                <td class="px-3 py-2">
                                    <div class="font-bold text-slate-800">{{ p.produk?.nama || 'Tanpa Nama' }}</div>
                                    <div class="text-xs font-mono text-slate-800 uppercase tracking-tighter">{{
                                        p.nama }}</div>
                                    <!-- <div class="text-xs font-mono text-slate-800 uppercase tracking-tighter">Satuan : {{
                                        p.produk.satuan.nama_satuan }}</div> -->
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="det in p.details" :key="det.id"
                                            class="w-12 flex items-center justify-center  bg-blue-50 text-blue-700 text-xs  py-0.5 rounded border border-blue-300 font-bold"
                                            :class="det.status === 'terjual' ? 'text-red-500' : ''">
                                            {{ det.qty }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center font-semibold text-sm">{{ p.jumlah ?? 0 }}</td>
                                <td class="px-3 py-2 text-center font-semibold text-sm">{{ p.panjang ?? 0 }} {{
                                    p.produk.satuan.nama_satuan }}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex justify-end gap-1">
                                        <button @click="openView(p)"
                                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-white rounded-lg transition-all text-xl">
                                            <i class='bx bx-show'></i>
                                        </button>
                                        <button @click="openEdit(p)"
                                            class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-white rounded-lg transition-all text-xl">
                                            <i class='bx bx-edit'></i>
                                        </button>
                                        <!-- <button @click="confirmDelete(p)"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-white rounded-lg transition-all text-xl"
                                            title="Hapus">
                                            <i class='bx bx-trash'></i>
                                        </button> -->
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400 italic">Data tidak
                                    ditemukan...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t bg-slate-50/50 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Menampilkan <b>{{ products.from || 0 }}</b> - <b>{{ products.to || 0 }}</b> dari <b>{{
                            products.total }}</b> produk
                    </span>
                    <div class="flex gap-1">
                        <button v-for="link in products.links" :key="link.label" @click="changePage(link.url)"
                            :disabled="!link.url || link.active" v-html="link.label"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                            :class="link.active ? 'bg-indigo-600 text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30'" />
                    </div>
                </div>
            </div>
        </div>

        <ProductModal v-if="modalOpen" :show="modalOpen" :mode="modalMode" :categories="categories" :gudangs="gudangs"
            :varian="varian" :product="selectedProduct" :satuan="satuan" @close="closeModal" @save="handleSave"
            @update="handleUpdate" />
    </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
// --- BARIS IMPORT DI BAWAH INI SANGAT PENTING ---
import ProductModal from '../../../Components/Grosir/Master/ProductModal.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    gudangs: Array,
    varian: Array,
    filters: Object,
    satuan: Array,
});

const params = reactive({
    search: props.filters?.search || '',
    per_page: props.filters?.per_page || '10',
});

let timeout;
const handleQuery = () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(window.location.pathname, {
            search: params.search,
            per_page: params.per_page
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        });
    }, 500);
};

watch(() => params.search, handleQuery);
watch(() => params.per_page, () => {
    router.get(window.location.pathname, { ...params, page: 1 }, {
        preserveState: true,
        preserveScroll: true
    });
});

const changePage = (url) => {
    if (url) router.visit(url, { preserveState: true, preserveScroll: true });
};

// MODAL STATE
const modalOpen = ref(false);
const modalMode = ref('create');
const selectedProduct = ref(null);

function openCreate() {
    modalMode.value = 'create';
    selectedProduct.value = { details: [] };
    modalOpen.value = true;
}

function openEdit(product) {
    modalMode.value = 'edit';
    selectedProduct.value = JSON.parse(JSON.stringify(product));
    modalOpen.value = true;
}

function openView(product) {
    modalMode.value = 'view';
    selectedProduct.value = product;
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    selectedProduct.value = null;
}

function handleSave(event) {
    modalOpen.value = false;
}

function handleUpdate(event) {
    modalOpen.value = false;
}

function confirmDelete(product) {
    // Kita gunakan konfirmasi browser sederhana, 
    // atau kamu bisa buat modal konfirmasi sendiri nantinya.
    if (confirm(`Apakah Anda yakin ingin menghapus varian "${product.nama}"?`)) {
        router.delete(route('grosir.master.products.destroy', product.id), {
            preserveScroll: true,
            onSuccess: () => {
                // Kamu bisa tambahkan notifikasi sukses di sini
                console.log('Produk berhasil dihapus');
            },
            onError: (errors) => {
                alert('Gagal menghapus produk. Mungkin data ini masih digunakan di transaksi lain.');
            }
        });
    }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
</style>