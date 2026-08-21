<template>
    <div>
        <div class="h-10 flex items-center gap-2 mb-4 print:hidden">
            <div class="flex items-center gap-2">
                <input type="date" v-model="filter.start" class="px-3 h-8 border rounded shadow-sm">
                <span>s/d</span>
                <input type="date" v-model="filter.end" class="px-3 h-8 border rounded shadow-sm">
                <button @click="updateFilter"
                    class="bg-blue-600 text-white px-4 h-8 rounded shadow-sm hover:bg-blue-700">
                    Cari
                </button>
            </div>
            <button @click="printReport"
                class="text-sm min-w-20 px-4 rounded-sm h-8 flex items-center justify-center bg-indigo-600 text-white">
                <i class='bx bx-printer mr-1'></i> Print
            </button>
        </div>

        <div class="w-full h-[calc(100vh-180px)] overflow-auto bg-slate-200 p-4 rounded-lg">
            <div id="print_area" class="w-full text-[10pt] max-w-[210mm] bg-white mx-auto shadow p-[10mm] min-h-screen">

                <div class="mb-6 text-center">
                    <h1 class="font-bold text-[14pt] uppercase">Laporan Bonus Marketing</h1>
                    <p class="text-sm">Periode: {{ formatDate(filter.start) }} - {{ formatDate(filter.end) }}</p>
                </div>

                <div v-for="(group, inx) in laporan" :key="inx" class="mb-8 break-inside-avoid">
                    <div class="bg-slate-100 p-2 font-bold border border-black flex justify-between">
                        <span>Marketing: {{ group.marketing }}</span>
                        <span>Total: {{ formatRupiah(group.total_penjualan) }}</span>
                    </div>
                    <table class="w-full border-collapse border border-black">
                        <thead>
                            <tr class="bg-slate-50 text-[9pt]">
                                <th class="border border-black p-2 w-8">#</th>
                                <th class="border border-black p-2 text-left">Nota</th>
                                <th class="border border-black p-2 text-left">Pelanggan</th>
                                <th class="border border-black p-2 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(tr, idx) in group.transaksi" :key="idx">
                                <td class="border border-black p-2 text-center">{{ idx + 1 }}</td>
                                <td class="border border-black p-2">{{ tr.nomer }}</td>
                                <td class="border border-black p-2">{{ tr.pelanggan?.nama || 'Umum' }}</td>
                                <td class="border border-black p-2 text-right">{{ formatRupiah(tr.total_penjualan) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="laporan.length === 0" class="text-center py-10 text-gray-500">
                    Tidak ada data pada periode ini.
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    laporan: Array,
    filters: Object
});

const filter = ref({
    start: props.filters.start,
    end: props.filters.end
});

const updateFilter = () => {
    router.get('/ecer/laporan/bonus', {
        start: filter.value.start,
        end: filter.value.end
    }, { preserveState: true });
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number || 0);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const printReport = () => window.print();
</script>

<style scoped>
@media print {
    body * {
        visibility: hidden;
    }

    #print_area,
    #print_area * {
        visibility: visible;
    }

    #print_area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        padding: 0;
    }

    .print\:hidden {
        display: none !important;
    }
}
</style>