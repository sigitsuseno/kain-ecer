<script setup lang="ts">
import { ref } from 'vue';
import Categories from '../../Components/Grosir/Master/Categories.vue';
import Pelanggan from '../../Components/Grosir/Master/Pelanggan.vue';
import Suppliers from '../../Components/Grosir/Master/Suppliers.vue';
import Gudang from '../../Components/Grosir/Master/Gudang.vue';
import Products from '../../Components/Grosir/Master/Products.vue';

// FUNGSI TAB MENU MASTER
const tabs = [
    { id: 'products', name: 'Products' },
    { id: 'categories', name: 'Categories' },
    { id: 'suppliers', name: 'Suppliers' },
    { id: 'pelanggan', name: 'Pelanggan' },
    { id: 'gudang', name: 'Gudang' },
];
const activeTab = ref('products');

const props = defineProps({
    products: Object,
    categories: Array,
    gudangs: Array,
    semua_produk: Array,
    suppliers: Array,
    pelanggans: Array,
    nama_produks: Array,
    satuan: Array,
});

</script>
<template>
    <div class="w-full h-[calc(100vh-48px)]">
        <div class="w-full h-10 bg-amber-200 border-b-4 border-blue-900 flex items-end px-4 gap-1">

            <button v-for="tab in tabs" :key="tab.id" type="button" @click="activeTab = tab.id" :class="[
                'cursor-pointer px-4 py-1 rounded-t-lg transition-all duration-200 text-sm font-bold uppercase tracking-tight',
                activeTab === tab.id
                    ? 'bg-blue-900 text-white shadow-lg border-t border-x border-blue-900/20'
                    : 'bg-white/50 text-blue-900 hover:bg-white hover:text-black border-t border-x border-blue-900/20'
            ]">{{ tab.name }}</button>

        </div>
        <div class="w-full h-[calc(100vh-88px)] bg-indigo-100">
            <div v-if="activeTab === 'products'" class="w-full h-full overflow-hidden">
                <Products :products="products" :categories="categories" :gudangs="gudangs" :all-products="semua_produk"
                    :kategoriNama="nama_produks" :satuan="satuan" />
            </div>
            <div v-if="activeTab === 'categories'" class="w-full h-full overflow-hidden">
                <Categories :categories="categories" />
            </div>
            <div v-if="activeTab === 'suppliers'" class="w-full h-full overflow-hidden">
                <Suppliers :suppliers="suppliers" />
            </div>
            <div v-if="activeTab === 'pelanggan'" class="w-full h-full overflow-hidden">
                <Pelanggan :pelanggans="pelanggans" />
            </div>
            <div v-if="activeTab === 'gudang'" class="w-full h-full overflow-hidden">
                <Gudang :gudangs="gudangs" />
            </div>
        </div>
    </div>
</template>
