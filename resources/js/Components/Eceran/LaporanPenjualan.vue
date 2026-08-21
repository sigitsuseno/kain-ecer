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

                <div class="mb-6 text-center">
                    <h1 class="font-bold text-[14pt] uppercase">Laporan Harian Penjualan Eceran</h1>
                    <p class="text-sm">Tanggal: {{ formatDate(filter.tanggal) }}</p>
                </div>

                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-[9pt] uppercase">
                            <th class="border p-2 w-8">#</th>
                            <th class="border p-2 text-left">Nota / Pelanggan</th>
                            <th class="border p-2 text-left">Rincian Barang</th>
                            <th class="border p-2 text-right">Bayar ({{ formatDate(filter.tanggal) }})</th>
                            <th class="border p-2">Detail Pembayaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(pj, index) in laporan" :key="pj.id" class="border align-top">
                            <td class="border p-2 text-center text-xs">{{ index + 1 }}</td>

                            <td class="border p-2">
                                <div class="font-bold text-[#2839d4]">{{ pj.nomer }}</div>
                                <div class="text-[8pt] uppercase font-semibold">{{ pj.pelanggan?.nama || 'Umum' }}
                                </div>
                            </td>

                            <td class="border p-2">
                                <div v-for="item in pj.items" :key="item.id"
                                    class="mb-2 last:mb-0 border-b last:border-0 border-dotted pb-1">
                                    <div class="font-medium text-[9pt]">
                                        {{ item.varian?.produk?.nama }}
                                        <span class="text-gray-500">({{ item.varian?.nama }})</span>
                                    </div>
                                    <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1">
                                        <span v-for="det in item.details" :key="det.id"
                                            class="text-[8pt] text-[#f1f1f8] px-1 border rounded">
                                            {{ det.qty }} {{ det.satuan?.inisial }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td class="border p-2 text-right font-bold text-green-700">
                                {{ formatRupiah(calculatePaidToday(pj)) }}
                                <div v-if="pj.total_penjualan > calculatePaidToday(pj)"
                                    class="text-[7pt] text-[#d42836] italic">
                                    Total Nota: {{ formatRupiah(pj.total_penjualan) }}
                                </div>
                            </td>

                            <td class="border p-2">
                                <template v-for="byr in pj.pembayaran" :key="byr.id">
                                    <div v-for="dtl in byr.details" class="grid grid-cols-[60%_40%]">
                                        <div>{{ dtl.metode_bayar }} - {{ dtl.bank_tujuan ?? '' }} </div>
                                        <div class="w-full">
                                            <div v-if="byr.metode !== 'SPLIT'" class="w-full flex justify-end">
                                                {{ formatRupiah(pj.total_penjualan) }}
                                            </div>
                                            <div class="border-b">

                                                <div v-if="byr.metode === 'SPLIT'" class="w-full flex justify-end">
                                                    {{ formatRupiah(dtl.nominal) }}
                                                </div>
                                                <div v-if="dtl.ongkir > 0"
                                                    class="w-full flex items-center justify-end gap-3 text-[#32a105]">
                                                    <span class="text-[10px]">Ongkir</span>{{ formatRupiah(dtl.ongkir)
                                                    }}
                                                </div>
                                                <div v-if="dtl.diskon > 0"
                                                    class="w-full flex items-center justify-end gap-3 text-[#da2c0e]">
                                                    <span class="text-[10px]">Diskon</span>{{ formatRupiah(dtl.diskon)
                                                    }}
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        {{ formatRupiah(byr.total) }}
                                    </div>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-800 text-white font-bold">
                            <td colspan="3" class="p-2 text-right uppercase text-xs">Total Penerimaan Hari Ini</td>
                            <td class="p-2 text-right">{{ formatRupiah(grandTotal) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

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

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};


const calculatePaidToday = (pj) => {
    return pj.pembayaran.reduce((acc, pay) => acc + parseFloat(pay.total), 0);
};

const grandTotal = computed(() => {
    return props.laporan.reduce((acc, pj) => acc + calculatePaidToday(pj), 0);
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
            link.download = `Laporan-Penjualan-${filter.value.tanggal}.png`;
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