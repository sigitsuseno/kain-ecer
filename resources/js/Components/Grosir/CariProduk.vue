<template>
    <div class="relative mb-4">
        <i class='bx bx-search absolute left-3 top-2 text-gray-400'></i>
        <input type="search" v-model="searchQuery"
            class="w-full h-9 pl-9 pr-4 rounded-full border border-gray-200 text-sm focus:ring-1 focus:ring-primary"
            placeholder="Cari kain atau warna...">
    </div>

    <div v-if="loading" class="text-center py-4 text-xs text-gray-500">
        Memuat produk...
    </div>

    <div v-else class="h-[calc(100vh-240px)] overflow-y-auto space-y-2 pr-1">
        <div v-for="p in listProduk.data" :key="p.id" @click="selectProduct(p)"
            class="p-3 border bg-white rounded-xl hover:bg-blue-50 cursor-pointer transition group border-gray-100 shadow-sm">
            <div class="flex justify-between items-start">
                <span class="font-bold text-gray-800 text-sm">{{ p.produk.nama }}</span>
                <span class="text-[10px] bg-primary/10 text-primary px-2 rounded-full">
                    {{ p.tipe || 'No Type' }}
                </span>
            </div>
            <div class="text-sm text-blue-900 mt-1 flex items-center justify-between">
                Warna: {{ p.nama }}
                <i class='bx bx-plus-circle text-lg text-primary opacity-0 group-hover:opacity-100 transition'></i>
            </div>
        </div>
    </div>

    <div v-if="listProduk.last_page > 1" class="flex justify-between items-center mt-4 px-2 pb-4">
        <button type="button" @click="fetchProduk(listProduk.current_page - 1)"
            :disabled="listProduk.current_page === 1"
            class="text-xs bg-white px-3 py-1 rounded border disabled:opacity-50">
            Prev
        </button>
        <span class="text-[10px] text-gray-500">
            {{ listProduk.current_page }} / {{ listProduk.last_page }}
        </span>
        <button type="button" @click="fetchProduk(listProduk.current_page + 1)"
            :disabled="listProduk.current_page === listProduk.last_page"
            class="text-xs bg-white px-3 py-1 rounded border disabled:opacity-50">
            Next
        </button>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

const emit = defineEmits(['tambah-produk']);

const searchQuery = ref('');
const listProduk = ref({ data: [] });
const loading = ref(false);

const fetchProduk = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/grosir/penerimaan/search-produk', {
            params: {
                search: searchQuery.value,
                page: page
            }
        });
        // Sesuai response controller: return response()->json(['varian' => $varian]);
        listProduk.value = response.data.varian;
    } catch (error) {
        console.error("Gagal ambil produk:", error);
    } finally {
        loading.value = false;
    }
};

const selectProduct = (p) => {
    emit('tambah-produk', p); // Kirim data produk ke parent
};

let timeout = null;
watch(searchQuery, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        fetchProduk(1);
    }, 500);
});

onMounted(() => fetchProduk());
</script>