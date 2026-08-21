<template>
    <div class="w-full">
        <LinkLaporan />
        <div class="w-full px-6">
            <LaporanCatatan :laporan="laporan" :filters="filters" />
        </div>
    </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LinkLaporan from '../../../Components/Eceran/LinkLaporan.vue';
import LaporanCatatan from '../../../Components/Eceran/LaporanCatatan.vue';

const props = defineProps({
    laporan: Object,   // Data penjualan yang sudah dikelompokkan
    filters: Object    // Data tanggal filter
});

</script>