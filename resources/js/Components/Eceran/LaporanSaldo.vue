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
                <div class="text-center mb-6">
                    <h1 class="text-xl font-bold uppercase">Laporan Saldo Pelanggan</h1>
                    <p>Tanggal: {{ formatDate(filter.tanggal) }}</p>
                </div>

                <table class="w-fit border-collapse border border-black">
                    <thead>
                        <tr class="bg-slate-100">
                            <th class="border border-black p-2 text-center w-10">No.</th>
                            <th class="border border-black p-2 text-left w-32">Tanggal</th>
                            <th class="border border-black p-2 text-left">Nama Customer</th>
                            <th class="border border-black p-2 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in laporan" :key="index" class="hover:bg-slate-50">
                            <td class="border border-black p-2 text-center">{{ index + 1 }}</td>
                            <td class="border border-black p-2 text-center">{{ formatDate(item.updated_at) }}</td>
                            <td class="border border-black p-2 font-bold uppercase">
                                {{ item.nama || 'Umum' }}
                            </td>
                            <td class="border border-black p-2 text-right font-semibold">
                                {{ formatMoney(item.saldo) }}
                            </td>
                        </tr>
                    </tbody>
                    <!-- <tfoot>
                        <tr class="bg-slate-100 font-bold">
                            <td colspan="3" class="border border-black p-2 text-right">GRAND TOTAL</td>
                            <td class="border border-black p-2 text-right">{{ formatMoney(grandTotal) }}</td>
                        </tr>
                    </tfoot> -->
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import * as htmlToImage from 'html-to-image';

const props = defineProps({
    laporan: Array, // Pastikan ini Array sesuai kiriman Controller
    filters: Object
});

const filter = ref({
    tanggal: props.filters.tanggal
});

// Helper Format Uang
const formatMoney = (val) => {
    return new Intl.NumberFormat('id-ID').format(val);
};

// Helper Format Tanggal
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

// Grand Total
const grandTotal = computed(() => {
    // Sekarang menjumlahkan dari field total_nominal
    return props.laporan.reduce((acc, item) => acc + parseFloat(item.total_nominal || 0), 0);
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
            link.download = `Laporan-Saldo-${filter.value.tanggal}.png`;
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