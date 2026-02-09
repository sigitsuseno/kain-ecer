<script setup>
import ListProduk from '../../../Components/ListProduk.vue';


const props = defineProps({
    produk: Object,
    pelanggan: Array,
    marketing: Array,
    company: Array
})



</script>

<template>
    <div class="w-full h-[calc(100vh-40px)] bg-gray-100 overflow-hidden">

        <div class="grid grid-cols-[65%_35%] ">
            <!-- <ListProduk :produk="produk" /> -->
            <div class="p-4 rounded-xl text-white">
                <div class="flex justify-between items-center">
                    {{ produk }}
                    {{ pelanggan }}
                </div>
            </div>
        </div>
    </div>
</template>