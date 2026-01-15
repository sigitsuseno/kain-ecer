<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100 grid grid-cols-[1fr_350px]">
        <div class="w-full h-full">
            <div class="text-2xl font-semibold text-primary py-2 px-6 mt-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class='bx bxs-package'></i> <span>Nota Penerimaan Barang</span>
                </div>
                <div>
                    <Link href="/grosir/penerimaan"
                        class="px-2 h-8 flex items-center justify-center text-sm bg-button active:scale-90 text-white rounded-sm">
                        <i class='bx bx-arrow-back'></i>
                        Kembali
                    </Link>
                </div>
            </div>
            <div class="px-4 w-full h-[calc(100vh-120px)] overflow-y-auto">
                <div class="bg-white p-4 rounded-lg">
                    test
                </div>
            </div>
        </div>
        <div class="w-full bg-white">
            <div class="w-full h-10 px-4 flex items-end shadow gap-2">
                <button class="px-4 h-8 flex items-center rounded-t-md bg-button text-white">Cari Produk</button>
                <button class="px-4 h-8 flex items-center rounded-t-md bg-button text-white">Buat Produk</button>
            </div>
            <div class="w-full">

            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';


</script>

<style lang="scss" scoped></style>
