<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100">
        <div class="text-2xl font-semibold text-primary py-2 px-6 mt-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class='bx bxs-package'></i> <span>Tambah ke Produk Eceran</span>
            </div>
            <div>
                <Link href="/dashboard/penerimaan-barang"
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
</template>

<script setup>
import { Link } from '@inertiajs/vue3';


</script>

<style lang="scss" scoped></style>
