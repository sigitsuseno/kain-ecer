<script setup lang="ts">


const props = defineProps({
    products: Object,

});

</script>
<template>
    <div class="w-full h-[calc(100vh-48px)]">
        <div class="w-full h-20 bg-amber-200 border-b-4 border-blue-900 flex items-center justify-between px-4 gap-1">
            <div class="w-full h-full flex items-center">
                <a href="#"
                    class="w-16 h-16 bg-white rounded-sm shadow hover:shadow-lg hover:bg-white/50 flex flex-col items-center justify-center gap-2">
                    <div class="w-10 h-8 flex items-center justify-center">
                        <i class='bx bx-plus text-2xl'></i>
                    </div>
                    <span class="text-xs text-center">Buat Produk</span>
                </a>
            </div>
        </div>
        <div class="w-full h-[calc(100vh-200px)] bg-indigo-100">
            test
        </div>
    </div>
</template>
