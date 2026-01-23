<template>
    <div id="forOffset" class="w-full h-screen bg-slate-100 text-slate-800">
        <header class="h-10 px-4 flex items-center justify-between bg-white border-b print:hidden">
            <Link href="/ecer/penjualan/transaksi/list"
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
                        <div class="receipt-header text-sm border-b border-dashed pb-2 text-center">
                            <h2 class="store-name font-bold text-base">TOKO KAIN ECER</h2>
                            <p>Jl. Tekstil Utama No. 88, Semarang</p>
                            <p>Telp: 024-12345678</p>
                        </div>

                        <div class="receipt-info text-xs grid grid-cols-[30%_5%_1fr] py-2 border-b border-dashed">
                            <span>Nota</span><span>:</span><span>{{ dataNota.nomer }}</span>
                            <span>Tanggal</span><span>:</span><span>{{ tanggal_indo }}</span>
                            <span>Pelanggan</span><span>:</span><span>{{ dataNota.pelanggan }}</span>
                            <span>PJ</span><span>:</span><span>{{ dataNota.marketing }}</span>
                        </div>

                        <div class="receipt-items py-2 border-b border-dashed">
                            <div v-for="item in penjualan.items" :key="item.id" class="mb-2">
                                <div class="item-name text-xs font-bold uppercase">{{ item.varian?.produk?.nama }}</div>
                                <div v-for="dtp in item.details" :key="dtp.id"
                                    class="grid grid-cols-[40%_1fr_25%] text-[11px]">
                                    <div class="truncate">{{ item.varian?.nama }}</div>
                                    <div class="text-center text-[10px]">
                                        {{ dtp.qty }} {{ dtp.satuan?.inisial }} x {{ formatNumber(dtp.harga) }}
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
                            class="total-row flex justify-between border-t border-double pt-1 text-red-700 text-sm font-bold">
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
                    <div class=" bg-slate-50 mt-auto space-y-2 grid grid-cols-2 gap-4">
                        <button @click="HandlePrint"
                            class="w-full h-full bg-blue-600 text-white py-3 rounded-lg font-bold shadow-md hover:bg-blue-700 flex items-center justify-center gap-2">
                            CETAK DESKTOP
                        </button>

                        <button @click="printBluetooth"
                            class="w-full h-full bg-emerald-600 text-white py-3 rounded-lg font-bold shadow-md hover:bg-emerald-700 flex items-center justify-center gap-2">
                            CETAK BLUETOOTH (TABLET)
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

// 2. Mapping List Pembayaran (Menyederhanakan struktur nested pembayaran.details)
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



const HandlePrint = async () => {
    try {
        const response = await axios.post(route('print-thermal'), props.penjualan);
        if (response.data.success) {
            console.log("Berhasil mencetak");
        } else {
            alert("Gagal mencetak: " + response.data.message);
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Koneksi ke printer gagal. Pastikan printer menyala dan terhubung.");
    }
};
// Tambahkan fungsi untuk cetak Bluetooth
const printBluetooth = async () => {
    try {
        // Kita masukkan daftar UUID yang paling umum digunakan printer thermal Android
        const device = await navigator.bluetooth.requestDevice({
            filters: [
                { namePrefix: 'RPP' }, // Seringkali printer thermal berawalan nama RPP
                { namePrefix: 'BT' },  // Atau BT Printer
                { namePrefix: 'MTP' }
            ],
            optionalServices: [
                '000018f0-0000-1000-8000-00805f9b34fb', // Protokol Umum 1
                '0000ff00-0000-1000-8000-00805f9b34fb', // Protokol Umum 2 (Sering dipakai Thermer)
                '49535343-fe7d-4158-a24f-03ed6e144187'  // Protokol Umum 3
            ]
        });

        const server = await device.gatt.connect();

        // Coba cari service yang aktif dari list di atas
        let service;
        const availableServices = await server.getPrimaryServices();
        service = availableServices[0]; // Ambil service pertama yang ditemukan

        const characteristics = await service.getCharacteristics();
        const characteristic = characteristics.find(c => c.properties.write || c.properties.writeWithoutResponse);

        if (!characteristic) throw new Error("Karakteristik cetak tidak ditemukan");

        // ... proses penyusunan teks nota (sama seperti sebelumnya) ...
        const encoder = new TextEncoder();
        await characteristic.writeValue(encoder.encode(receiptText));

        alert("Berhasil mencetak!");
    } catch (error) {
        console.error("Detail Error:", error);
        alert("Gagal: " + error.message);
    }
};
</script>

<style scoped>
.font-mono {
    font-family: 'Courier New', Courier, monospace;
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
    }
}
</style>