<template>
    <div class="w-full">
        <LinkLaporan />
        <div class="w-full px-6">
            <LaporanOmset :laporan="laporan" :filters="filters" />
        </div>
    </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LaporanOmset from '../../../Components/Eceran/LaporanOmset.vue';
import LinkLaporan from '../../../Components/Eceran/LinkLaporan.vue';

const props = defineProps({
    laporan: Object,   // Data penjualan yang sudah dikelompokkan
    filters: Object    // Data tanggal filter
});

</script>