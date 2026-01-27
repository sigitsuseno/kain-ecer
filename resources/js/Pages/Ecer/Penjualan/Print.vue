<template>
    <div id="forOffset" class="w-full h-screen bg-slate-100 text-slate-800">
        <header class="h-10 px-4 flex items-center justify-between bg-white border-b print:hidden">
            <Link href="/ecer/penjualan"
                class="px-3 h-7 rounded bg-slate-200 hover:bg-slate-300 flex items-center justify-center text-slate-700 text-sm transition">
                Back
            </Link>
            <h1 class="font-bold text-sm text-blue-600">POS Penjualan Eceran</h1>
            <span class="text-xs">{{ today }}</span>
        </header>
        <div class="grid grid-cols-[65%_35%] h-[calc(100vh-40px)] print:block">
            <div class="w-full flex flex-col bg-slate-100 relative print:bg-white overflow-y-auto">
                <div class="flex flex-col items-center justify-center py-10 print:py-0">
                    <h2 class="font-bold text-lg mb-4 print:hidden">Pratinjau Nota</h2>

                    <div id="printable-receipt"
                        class="w-[80mm] shadow bg-white p-4 print:shadow-none print:p-0 font-mono">
                        <div class="receipt-header text-xs border-b border-dashed pb-2 text-center">
                            <h2 class="store-name font-bold text-base">UTEX</h2>
                            <p>Jl. Musi No.12, RT.05/RW.02</p>
                            <p>Sidomulyo, Kec. Ungaran Tim., Kab. Semarang</p>
                        </div>

                        <div class="receipt-info text-xs grid grid-cols-[30%_5%_1fr] py-2 border-b border-dashed">
                            <span>Nota</span><span>:</span><span>{{ dataNota.nomer }}</span>
                            <span>Tanggal</span><span>:</span><span>{{ tanggal_indo }}</span>
                            <span>Pelanggan</span><span>:</span><span>{{ dataNota.pelanggan }}</span>
                            <span>PJ</span><span>:</span><span>{{ dataNota.marketing }}</span>
                        </div>

                        <div class="receipt-items py-2 border-b border-dashed">
                            <div v-for="item in penjualan.items" :key="item.id" class="mb-2">
                                <div class="item-name text-xs font-bold uppercase">{{ item.varian?.produk?.nama }} - {{
                                    item.varian?.nama }}</div>
                                <div v-for="dtp in item.details" :key="dtp.id"
                                    class="grid grid-cols-[1fr_25%] text-[11px]">
                                    <div class="text-start text-[10px]">
                                        {{ dtp.qty }} {{ dtp.satuan?.inisial }} x @{{ formatNumber(dtp.harga) }}
                                    </div>
                                    <div class="text-end">{{ formatNumber(dtp.jumlah_harga) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="receipt-total py-2 space-y-1 text-xs font-mono">
                            <div class="total-row flex justify-between font-bold text-sm">
                                <span>TOTAL</span>
                                <span>{{ formatNumber(dataNota.total) }}</span>
                            </div>

                            <div v-if="dataNota.ongkir > 0 || dataNota.diskon > 0"
                                class="total-row flex justify-between italic text-[10px]">
                                <span>Ongkir / Diskon</span>
                                <span>{{ formatNumber(dataNota.ongkir) }} / -{{ formatNumber(dataNota.diskon) }}</span>
                            </div>

                            <div v-for="(pay, pIdx) in listPembayaran" :key="pIdx"
                                class="total-row flex justify-between">
                                <span class="uppercase">BAYAR ({{ pay.metode }})</span>
                                <span>{{ formatNumber(pay.nominal) }}</span>
                            </div>

                            <div v-if="dataNota.kembalian > 0"
                                class="total-row flex justify-between border-t border-dashed pt-1">
                                <span>KEMBALI</span>
                                <span>{{ formatNumber(dataNota.kembalian) }}</span>
                            </div>
                        </div>

                        <div v-if="dataNota.sisaPiutang > 0"
                            class="total-row flex justify-between border-t border-double pt-1 text-[#ff0000] text-sm font-bold">
                            <span>SISA PIUTANG</span>
                            <span>{{ formatNumber(dataNota.sisaPiutang) }}</span>
                        </div>

                        <div class="receipt-footer text-center mt-6 text-[9px] uppercase leading-tight">
                            <p>*** Terima Kasih ***</p>
                            <p>Barang yang sudah dipotong</p>
                            <p>tidak dapat dikembalikan / ditukar.</p>
                            <p class="mt-2 font-mono italic">{{ today }} {{ currentTime }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border-l flex flex-col relative print:hidden">
                <div class="w-full h-[calc(100vh-210px)] overflow-y-auto p-4 space-y-4">
                    <h3 class="font-bold border-b pb-2 text-slate-600 flex items-center gap-2">
                        <span class="w-2 h-5 bg-blue-600 rounded-full"></span>
                        Informasi Penjualan
                    </h3>
                    <div class="text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Status:</span>
                            <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-bold text-xs">{{
                                penjualan.status }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Tagihan:</span>
                            <span class="font-bold">{{ formatNumber(dataNota.total) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Terbayar:</span>
                            <span class="font-bold text-green-600">{{ formatNumber(dataNota.totalTerbayar) }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 mt-auto">
                    <div class="bg-slate-50 mt-auto space-y-2 grid grid-cols-2 gap-4">
                        <button @click="HandlePrint"
                            class="col-span-2 w-full h-full bg-blue-600 text-white py-3 rounded-lg font-bold shadow-md hover:bg-blue-700 flex items-center justify-center gap-2">
                            CETAK DESKTOP
                        </button>

                        <button @click="exportToImage"
                            class="col-span-2 w-full h-full bg-orange-500 text-white py-3 rounded-lg font-bold shadow-md hover:bg-orange-600 flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            SIMPAN GAMBAR (PNG)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AuthLayout from '../../../Layouts/AuthLayout.vue'
import axios from 'axios';
// Hapus import html2canvas
import { toPng } from 'html-to-image';


defineOptions({ layout: AuthLayout })

const props = defineProps({
    penjualan: Object,
    tanggal_indo: String,
})

const today = new Date().toLocaleDateString('id-ID', { dateStyle: 'long' })
const currentTime = new Date().toLocaleTimeString('id-ID', { hour12: false })
const formatNumber = (val) => new Intl.NumberFormat('id-ID').format(val || 0)

// 1. Mapping Data Utama Nota
const dataNota = computed(() => {
    const p = props.penjualan;
    const totalBayar = listPembayaran.value.reduce((acc, curr) => acc + curr.nominal, 0);

    return {
        nomer: p.nomer,
        pelanggan: p.pelanggan?.nama || 'Umum',
        marketing: p.marketing?.nama || '-',
        total: parseFloat(p.total_penjualan || 0),
        ongkir: parseFloat(p.ongkir || 0),
        diskon: parseFloat(p.diskon || 0),
        kembalian: parseFloat(p.kembalian || 0),
        totalTerbayar: totalBayar,
        sisaPiutang: Math.max(0, parseFloat(p.total_penjualan || 0) - totalBayar)
    }
})

// 2. Mapping List Pembayaran
const listPembayaran = computed(() => {
    if (!props.penjualan.pembayaran) return [];

    let flattendPayments = [];
    props.penjualan.pembayaran.forEach(pay => {
        pay.details.forEach(det => {
            flattendPayments.push({
                metode: det.metode_bayar || pay.metode,
                nominal: parseFloat(det.nominal || 0)
            });
        });
    });
    return flattendPayments;
})



const exportToImage = async () => {
    const element = document.getElementById('printable-receipt');
    if (!element) return;

    try {
        // Konversi langsung ke Data URL (Base64)
        const dataUrl = await toPng(element, {
            cacheBust: true,
            pixelRatio: 2, // Sama dengan scale: 2 di html2canvas agar tajam
            backgroundColor: '#ffffff',
        });

        // Buat link download
        const link = document.createElement('a');
        link.download = `Nota-${props.penjualan.nomer}.png`;
        link.href = dataUrl;
        link.click();
    } catch (error) {
        console.error("Gagal export gambar:", error);
        alert("Gagal menyimpan gambar.");
    }
};

const HandlePrint = async () => {
    const element = document.getElementById('printable-receipt');

    try {
        // Ubah HTML menjadi gambar Base64
        const imageBase64 = await toPng(element, { pixelRatio: 2 });

        const response = await axios.post(route('print-thermal'), {
            ...props.penjualan,
            image_nota: imageBase64 // Kirim gambar ke PHP
        });

        if (response.data.success) {
            console.log("Berhasil mencetak");
        } else {
            alert("Gagal mencetak: " + response.data.message);
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Gagal memproses cetakan.");
    }
};

</script>

<style scoped>
.font-mono {
    font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
}

@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }

    body * {
        visibility: hidden;
    }

    #printable-receipt,
    #printable-receipt * {
        visibility: visible;
    }

    #printable-receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm;
        color: black;
        font-size: medium;
    }
}
</style>