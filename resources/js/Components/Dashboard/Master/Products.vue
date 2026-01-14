<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ProductTable from './ProductTable.vue';
import ProductModal from './ProductModal.vue';


const props = defineProps({
    products: Object,
    categories: Array,
    gudangs: Array,
    allProducts: Array,
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
    router.get(route('dashboard.master.products.index'), params, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <div class="p-4 relative">
        <ProductTable :products="products" :categories="categories" @create="openCreate" @edit="openEdit"
            @view="openView" @search="search" />

        <ProductModal v-if="modalOpen" :mode="modalMode" :product="selectedProduct" :categories="categories"
            :gudangs="gudangs" :all-products="allProducts" @close="modalOpen = false" />

    </div>
</template>
