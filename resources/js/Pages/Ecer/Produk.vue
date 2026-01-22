<script setup>
// Tambahkan router di sini
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue'; // Tambahkan watch
// import debounce from 'lodash/debounce';
import ProductModal from '../../Components/Eceran/ProductModal.vue';

const props = defineProps({
    varians: Object,
    categories: Array,
    satuan: Array,
    gudangs: Array,
    allProducts: Array,
    filters: Object,
});

// State untuk filter
const search = ref(props.filters?.search || '');
const kategori = ref(props.filters?.kategori || '');
const perPage = ref(props.filters?.perPage || '10');

function debounce(fn, wait) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };
}

const submitSearch = debounce(() => {
    router.get(window.location.pathname, {
        search: search.value,
        kategori: kategori.value,
        perPage: perPage.value,
        page: 1
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 500); // Tunggu 500ms

// Pantau perubahan variabel
watch([search, kategori, perPage], () => {
    submitSearch();
});

function resetFilter() {
    search.value = '';
    kategori.value = '';
    router.get(window.location.pathname);
}

const modalOpen = ref(false);
const modalMode = ref('create');
const selectedProduk = ref(null);

function openCreate() {
    modalMode.value = 'create';
    selectedProduk.value = null;
    modalOpen.value = true;
}

function openEdit(produk) {
    modalMode.value = 'edit';
    selectedProduk.value = produk;
    modalOpen.value = true;
}

function openView(produk) {
    modalMode.value = 'view';
    selectedProduk.value = produk;
    modalOpen.value = true;
}
</script>

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
                        <i class='bx bx-plus text-3xl'></i>
                    </div>
                    <span class="text-xs text-center">Kategori</span>
                </Link>
            </div>
        </div>

        <div class="w-full h-[calc(100vh-120px)] bg-indigo-100">
            <div class="w-full h-20 border-b flex items-center justify-start gap-4 px-4">
                <label class="flex flex-col w-1/12">
                    <span class="text-xs mb-1">Per Halaman</span>
                    <select v-model="perPage" @change="submitSearch"
                        class="bg-white px-4 h-8 rounded-sm outline-none ring ring-primary/30 text-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </label>

                <label class="flex flex-col w-1/5">
                    <span class="text-xs mb-1">Cari Produk</span>
                    <input v-model="search" @keyup.enter="submitSearch" type="search"
                        class="bg-white px-4 h-8 rounded-sm outline-none ring ring-primary/30 text-sm"
                        placeholder="Cari Produk nama atau warna">
                </label>

                <label class="flex flex-col w-1/5">
                    <span class="text-xs mb-1">Produk By Kategori</span>
                    <select v-model="kategori" @change="submitSearch"
                        class="bg-white px-4 h-8 rounded-sm outline-none ring ring-primary/30 text-sm">
                        <option value="">--Pilih Kategori</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nama_kategori }}</option>
                    </select>
                </label>
                <button @click="submitSearch"
                    class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold rounded-md transition-all active:scale-95 shadow-sm mt-5">
                    Filter
                </button>
                <button v-if="search || kategori" @click="resetFilter"
                    class="px-5 py-2 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-semibold rounded-md transition-all active:scale-95 mt-5">
                    Reset
                </button>
            </div>

            <div class="w-full h-[calc(100vh-200px)] overflow-y-auto">
                <div class="w-full p-4 overflow-x-auto">

                    <div
                        class="grid grid-cols-[50px_200px_100px_100px_1fr_100px] xl:grid-cols-[50px_250px_150px_120px_120px_1fr_100px] min-w-200 items-center text-center bg-primary text-white font-semibold">
                        <div class="px-2 py-2 border-r border-white/20">No.</div>
                        <div class="px-2 py-2 border-r border-white/20 text-left">Nama Produk</div>
                        <div class="px-2 py-2 border-r border-white/20">Warna</div>
                        <div class="px-2 py-2 border-r border-white/20">Tipe</div>
                        <div class="px-2 py-2 border-r border-white/20">Panjang</div>

                        <div class="px-2 py-2">Aksi</div>
                    </div>

                    <div v-for="(prd, index) in (varians.data || varians)" :key="prd.id"
                        class="grid grid-cols-[50px_200px_100px_100px_1fr_100px] xl:grid-cols-[50px_250px_150px_120px_120px_1fr_100px] min-w-200 items-center text-center bg-white hover:bg-gray-50 border-b border-primary/30">

                        <div class="h-12 flex items-center justify-center">{{ index + 1 }}</div>
                        <div class="h-12 px-2 flex items-center text-left truncate">{{ prd.produk.nama
                            }}</div>
                        <div class="h-12 px-2 flex items-center justify-center">{{ prd.nama }}</div>
                        <div class="h-12 px-2 flex items-center justify-center capitalize">{{ prd.tipe }}</div>
                        <div class="h-12 px-2 flex items-center justify-center">
                            <div v-for="stc in prd.details" :key="stc.id"
                                class="w-fit flex items-center px-2 py-1 gap-2 justify-center shadow ring rounded-sm ml-2">
                                {{ stc.qty }}
                            </div>
                        </div>

                        <div class="h-12 flex items-center justify-center gap-1">
                            <button @click="openView(prd)"
                                class="p-1.5 bg-blue-500 text-white rounded shadow-sm hover:bg-blue-600">
                                <i class='bx bx-show'></i>
                            </button>
                            <button @click="openEdit(prd)"
                                class="p-1.5 bg-amber-500 text-white rounded shadow-sm hover:bg-amber-600">
                                <i class='bx bx-edit'></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between px-4 mb-4">
                    <p class="text-xs text-slate-500">
                        Menampilkan <span class="font-bold">{{ varians.from }}</span> sampai <span class="font-bold">{{
                            varians.to }}</span> dari <span class="font-bold">{{ varians.total }}</span> produk
                    </p>
                    <div class="flex gap-1">
                        <button v-for="link in varians.links" :key="link.label" :disabled="!link.url || link.active"
                            @click="router.get(link.url, { search, kategori, perPage }, { preserveState: true })"
                            v-html="link.label" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white shadow-md'
                                    : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-30'
                            ]" />
                    </div>
                </div>
            </div>

        </div>
        <ProductModal v-if="modalOpen" :mode="modalMode" :varians="selectedProduk" :satuan="satuan"
            :categories="categories" :gudangs="gudangs" :all-products="allProducts" @close="modalOpen = false" />

    </div>
</template>