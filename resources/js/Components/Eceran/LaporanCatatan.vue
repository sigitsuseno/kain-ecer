<template>
    <div>
        <div class="h-10 flex items-center gap-2 mb-4">
            <div class="flex items-center gap-2">
                <input type="date" v-model="filter.tanggal" @change="updateFilter"
                    class="px-3 h-8 border rounded shadow-sm">
            </div>
            <button @click="printReport"
                class="text-sm min-w-20 px-4 rounded-sm h-8 flex items-center justify-center bg-indigo-600 text-white hover:bg-indigo-700">
                <i class='bx bx-printer mr-1'></i> Print
            </button>
            <button @click="exportReportImage"
                class="text-sm min-w-20 px-4 rounded-sm h-8 flex items-center justify-center bg-emerald-600 text-white hover:bg-emerald-700">
                <i class='bx bx-image-add mr-1'></i> Export Image
            </button>
        </div>

        <div
            class="w-full h-[calc(100vh-180px)] overflow-auto clear-both bg-slate-200 rounded-lg border border-dashed border-primary/30 p-4">

            <div id="print_area" class="w-full text-[10pt] max-w-[210mm] bg-white mx-auto shadow p-[10mm]">
                <template v-for="(data, idx, lain) in laporan">
                    <div v-if="idx !== 'SALDO'" class="mt-12">
                        <div class="mb-6 text-center">
                            <h1 class="font-bold text-[14pt] uppercase">Laporan {{ idx }}</h1>
                            <p class="text-sm">Tanggal: {{ formatDate(filter.tanggal) }}</p>
                        </div>
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-slate-100 text-[9pt] uppercase">
                                    <th class="border p-2 w-8">#</th>
                                    <th class="border p-2 text-left w-40">Tanggal</th>
                                    <th class="border p-2 text-left">Ket</th>
                                    <th class="border p-2 text-left w-40">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>

                                <template v-for="(nt, inx) in data" :key="inx">
                                    <tr class="border align-top">
                                        <td class="border p-2 text-center text-xs">{{ inx + 1 }}</td>

                                        <td class="border p-2">
                                            <div class="flex items-center gap-2">
                                                <div class="font-bold text-[#2839d4]">
                                                    {{ formatDate(nt.created_at) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="border p-2">
                                            <div class="flex items-center gap-2">
                                                <div class="font-bold text-[#2839d4]">
                                                    {{ nt.ket }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="border p-2">
                                            <div class="font-bold text-[#2839d4] text-end">
                                                {{ formatRupiah(nt.nominal) }}
                                            </div>
                                            <div class="text-[8pt] uppercase font-semibold">
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>

                        </table>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

import * as htmlToImage from 'html-to-image';

const props = defineProps({
    laporan: Object,
    filters: Object
});

const filter = ref({
    tanggal: props.filters?.tanggal || new Date().toISOString().split('T')[0]
});

const updateFilter = () => {
    router.get('/ecer/laporan/penjualan', { tanggal: filter.tanggal }, { preserveState: true });
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
};
const formatRoll = (number) => {
    const val = parseFloat(number) || 0;
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(val);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};


const grandTotal = computed(() => {
    return props.laporan.reduce((acc, item) => {
        // Pastikan nominal dikonversi ke float/number untuk menghindari error string
        return acc + parseFloat(item.qty || 0);
    }, 0); // 0 adalah nilai awal total
});

const printReport = () => {
    window.print();
};

const exportReportImage = () => {
    const node = document.getElementById('print_area');

    // Opsional: Tambahkan loading state jika perlu
    htmlToImage.toPng(node, {
        backgroundColor: '#ffffff', // Memastikan background tidak transparan
        style: {
            margin: '0',
        }
    })
        .then((dataUrl) => {
            const link = document.createElement('a');
            link.download = `Laporan-Barang-Terjual-${filter.value.tanggal}.png`;
            link.href = dataUrl;
            link.click();
        })
        .catch((error) => {
            console.error('oops, something went wrong!', error);
        });
};
</script>

<style scoped>
@media print {

    /* 1. Sembunyikan semua elemen di dalam body */
    body * {
        visibility: hidden;
    }

    /* 2. Tampilkan kembali hanya print_area dan isinya */
    #print_area,
    #print_area * {
        visibility: visible;
    }

    /* 3. Atur posisi print_area agar mulai dari pojok kiri atas kertas */
    #print_area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 10mm;
        box-shadow: none;
        height: 100%;
        /* Hilangkan bayangan saat diprint */
    }

    /* 4. Sembunyikan elemen navigasi atau tombol secara spesifik jika perlu */
    .print\:hidden {
        display: none !important;
    }
}
</style>