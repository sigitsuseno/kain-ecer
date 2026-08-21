<template>
    <div class="w-full h-20 flex items-center gap-3 px-4">
        <Link :href="`/ecer/laporan`"
            :class="`w-16 h-16 bg-linear-to-tl from-button bg-blue-900 text-white rounded-md shadow`">
            <div class="w-full h-9 flex items-center justify-center">
                <i class='bx bx-box text-2xl'></i>
            </div>
            <div class="text-[11px]/3 text-center px-1">Catatan Keuangan</div>
        </Link>
        <Link v-for="(tl, index) in tombolLaporan" :key="tl.index" :href="`/ecer/laporan/${tl.slug}`"
            :class="`w-16 h-16 bg-linear-to-tl from-button ${tl.color} text-white rounded-md shadow`">
            <div class="w-full h-9 flex items-center justify-center">
                <i class='bx bx-box text-2xl'></i>
            </div>
            <div class="text-[11px]/3 text-center px-1">{{ tl.label }}</div>
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const jenisLaporan = ref('penjualan');

const tombolLaporan = [
    { slug: "penjualan", label: "Laporan Penjualan", color: "to-blue-500" },
    { slug: "saldo", label: "Laporan Saldo", color: "to-yellow-500" },
    { slug: "omset", label: "Laporan Omset", color: "to-pink-500" },
    { slug: "barang", label: "Laporan Barang", color: "to-orange-500" },
    { slug: "stok", label: "Laporan Stok", color: "to-green-500" },
    { slug: "catatan", label: "Catatan Kasir", color: "to-red-500" },
    { slug: "bonus", label: "Laporan PJ", color: "to-indigo-500" },
]
</script>

<style lang="scss" scoped></style>