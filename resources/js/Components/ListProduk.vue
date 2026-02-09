<template>
    <div class="w-full h-[calc(100vh-40px)] bg-blue-100">
        <div class="h-16 w-full border-b border-primary/20 px-4 flex items-center">
            <div class="relative">
                <i class='bx bx-search absolute left-3 top-2.5 text-gray-400'></i>
                <input type="search"
                    class="w-full h-10 pl-9 pr-4 bg-white outline-none rounded-full border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20"
                    placeholder="Cari kain atau warna...">
            </div>
        </div>
        <div class="w-full h-[calc(100vh-104px)] overflow-y-auto">
            <div class="w-full p-4 grid grid-cols-2 lg:grid-cols-4">
                {{ produk }}
                <template v-for="prd in produk">
                    {{ prd }}
                    <div class="w-full p-2 bg-white shadow rounded-md">
                        <h3>Nama_Produk</h3>
                        <h4>Warna</h4>
                        <div class="grid grid-cols-2 mt-2">
                            <div
                                class="w-full p-1 rounded-sm shadow border border-primary/50 flex items-center justify-center gap-1">
                                <span>30</span><span>Yd</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    produk: Object,
})

</script>

<style lang="scss" scoped></style>