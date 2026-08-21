<template>
    <div class="w-full">
        <LinkLaporan />
        <div class="w-full px-6">
            <LaporanStok :laporan="laporan" :filters="filters" />
        </div>
    </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LaporanStok from '../../../Components/Eceran/LaporanStok.vue';
import LinkLaporan from '../../../Components/Eceran/LinkLaporan.vue';


const props = defineProps({
    laporan: Object,   // Data penjualan yang sudah dikelompokkan
    filters: Object    // Data tanggal filter
});

const jenisLaporan = ref('penjualan');

const tombolLaporan = [
    { slug: "penjualan", label: "Laporan Penjualan", color: "to-blue-500" },
    { slug: "saldo", label: "Laporan Saldo", color: "to-yellow-500" },
    { slug: "omset", label: "Laporan Omset", color: "to-pink-500" },
    { slug: "barang", label: "Laporan Barang", color: "to-orange-500" },
    { slug: "stok", label: "Laporan Stok", color: "to-green-500" },
]
</script>