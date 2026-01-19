<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ProductTable from './ProductTable.vue';
import ProductModal from './ProductModal.vue';


const props = defineProps({
    products: Object,
    categories: Array,
    kategoriNama: Array,
    gudangs: Array,
    allProducts: Array,
    satuan: Array,
});

const modalOpen = ref(false);
const modalMode = ref('create');
const selectedProduct = ref(null);

function openCreate() {
    modalMode.value = 'create';
    selectedProduct.value = null;
    modalOpen.value = true;
}

function openEdit(product) {
    modalMode.value = 'edit';
    selectedProduct.value = product;
    modalOpen.value = true;
}

function openView(product) {
    modalMode.value = 'view';
    selectedProduct.value = product;
    modalOpen.value = true;
}

function search(params) {
    router.get(route('grosir.master.products.index'), params, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <div class="w-full h-full p-4 relative">

        <ProductTable :products="products" :categories="categories" :kategoriNama="kategoriNama" @create="openCreate"
            @edit="openEdit" @view="openView" @search="search" />

        <ProductModal v-if="modalOpen" :mode="modalMode" :product="selectedProduct" :satuan="satuan"
            :categories="categories" :gudangs="gudangs" :all-products="allProducts" @close="modalOpen = false" />
    </div>
</template>
