<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100 relative">
        <div class="text-2xl font-semibold text-primary py-2 px-6 mt-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class='bx bxs-package'></i> <span>Master Data</span>
            </div>

        </div>

        <div class="px-4 w-full h-[calc(100vh-120px)] overflow-y-auto">
            <div class="bg-white p-4 rounded-lg shadow-sm flex items-center gap-4">
                <Link href="/grosir/master/products"
                    class="w-20 h-20 bg-indigo-600 hover:bg-indigo-700 active:scale-90 text-white rounded-lg shadow-md transition-all flex flex-col items-center justify-center gap-2">
                    <i class='bx bx-package text-2xl'></i>
                    <span class="text-xs font-medium">Produk</span>
                </Link>
                <Link href="/grosir/master/categories"
                    class="w-20 h-20 bg-indigo-600 hover:bg-indigo-700 active:scale-90 text-white rounded-lg shadow-md transition-all flex flex-col items-center justify-center gap-2">
                    <i class='bx bx-category text-2xl'></i>
                    <span class="text-xs font-medium">Kategori</span>
                </Link>
                <Link href="/grosir/master/suppliers"
                    class="w-20 h-20 bg-indigo-600 hover:bg-indigo-700 active:scale-90 text-white rounded-lg shadow-md transition-all flex flex-col items-center justify-center gap-2">
                    <i class='bx bx-user text-2xl'></i>
                    <span class="text-xs font-medium">Supplier</span>
                </Link>
                <Link href="/grosir/master/pelanggan"
                    class="w-20 h-20 bg-indigo-600 hover:bg-indigo-700 active:scale-90 text-white rounded-lg shadow-md transition-all flex flex-col items-center justify-center gap-2">
                    <i class='bx bx-user text-2xl'></i>
                    <span class="text-xs font-medium">Customer</span>
                </Link>
                <Link href="/grosir/master/gudang"
                    class="w-20 h-20 bg-indigo-600 hover:bg-indigo-700 active:scale-90 text-white rounded-lg shadow-md transition-all flex flex-col items-center justify-center gap-2">
                    <i class='bx bxs-user-voice text-2xl'></i>
                    <span class="text-xs font-medium">Gudang</span>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>>