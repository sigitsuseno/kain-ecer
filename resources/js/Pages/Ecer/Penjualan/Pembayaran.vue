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
            <div class="w-full flex flex-col bg-slate-100 relative print:bg-white">
                <div class="absolute top-0 right-0 p-2 z-10 print:hidden">
                    <OffsetPanel>
                        <div class="p-4">
                            <h3 class="font-bold mb-2">Informasi Tambahan</h3>
                            <p class="text-sm italic text-slate-500">
                                Gunakan panel ini untuk catatan internal atau detail pengiriman jika diperlukan.
                            </p>
                        </div>
                    </OffsetPanel>
                </div>

                <div class="flex flex-col items-center justify-center py-10 print:py-0 overflow-y-auto">
                    <h2 class="font-bold text-lg mb-4 print:hidden">Pratinjau Nota</h2>

                    <div id="printable-receipt" class="w-[80mm] shadow bg-white p-4 print:shadow-none print:p-0">
                        <div class="receipt-header text-sm border-b border-dashed pb-2 text-center">
                            <h2 class="store-name font-bold text-base">TOKO KAIN ECER</h2>
                            <p>Jl. Tekstil Utama No. 88, Semarang</p>
                            <p>Telp: 024-12345678</p>
                        </div>

                        <div class="receipt-info text-xs grid grid-cols-[30%_5%_1fr] py-2 border-b border-dashed">
                            <span>Nota</span><span>:</span><span>{{ penjualan.nomer }}</span>
                            <span>Tanggal</span><span>:</span><span>{{ penjualan.tanggal_indo }}</span>
                            <span>Pelanggan</span><span>:</span><span>{{ penjualan.pelanggan?.nama || 'Umum' }}</span>
                            <span>PJ</span><span>:</span><span>{{ penjualan.marketing?.nama || '-' }}</span>
                        </div>

                        <div class="receipt-items py-2 border-b border-dashed">
                            <div v-for="item in penjualan.items" :key="item.id" class="mb-2">
                                <div class="item-name text-xs font-bold uppercase">{{ item.varian?.produk?.nama }}</div>
                                <div v-for="dtp in item.details" :key="dtp.id"
                                    class="grid grid-cols-[40%_1fr_25%] text-xs">
                                    <div class="truncate">{{ item.varian?.nama }}</div>
                                    <div class="text-center text-[10px]">
                                        {{ dtp.qty }} {{ dtp.satuan.inisial }} x {{ formatNumber(dtp.harga) }}
                                    </div>
                                    <div class="text-end">{{ formatNumber(dtp.jumlah_harga) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="receipt-total py-2 space-y-1 text-sm font-bold font-mono">
                            <div class="total-row flex justify-between">
                                <span>TOTAL</span>
                                <span>{{ formatNumber(totalKeseluruhan) }}</span>
                            </div>

                            <div v-if="ongkir !== 0 || diskon !== 0"
                                class="total-row flex justify-between text-xs font-normal italic">
                                <span>Ongkir/Diskon</span>
                                <span>{{ formatNumber(ongkir) }} / -{{ formatNumber(diskon) }}</span>
                            </div>

                            <div v-if="penjualan.status === 'DP'"
                                class="total-row flex justify-between text-xs font-normal italic">
                                <span>DP</span>
                                <span>{{ formatNumber(totalSudahDibayar) }}</span>
                            </div>

                            <div class="total-row flex justify-between text-xs">
                                <span>BAYAR ({{ payment.metode }})</span>
                                <span>
                                    {{ formatNumber(payment.nominal1 + (payment.metode === 'SPLIT' ? payment.nominal2 :
                                        0)) }}
                                </span>
                            </div>

                            <div class="total-row flex justify-between border-t border-dashed pt-1 text-base">
                                <span>KEMBALI</span>
                                <span>{{ formatNumber(kembalian) }}</span>
                            </div>
                        </div>

                        <div v-if="sisaSetelahBayarIni > 0"
                            class="total-row flex justify-between border-t border-double pt-1 text-red-700 text-sm">
                            <span>SISA PIUTANG</span>
                            <span>{{ formatNumber(sisaSetelahBayarIni) }}</span>
                        </div>

                        <div class="receipt-footer text-center mt-6 text-[9px] uppercase leading-tight">
                            <p>*** Terima Kasih ***</p>
                            <p>Barang yang sudah dipotong</p>
                            <p>tidak dapat dikembalikan / ditukar.</p>
                            <p class="mt-2 font-mono italic">{{ today }} {{ new Date().toLocaleTimeString() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border-l flex flex-col relative print:hidden">
                <div class="w-full h-[calc(100vh-210px)] overflow-y-auto p-4 space-y-4">
                    <h3 class="font-bold border-b pb-2 text-slate-600 flex items-center gap-2">
                        <span class="w-2 h-5 bg-blue-600 rounded-full"></span>
                        Panel Pembayaran
                    </h3>

                    <div class="space-y-3">
                        <div class="grid grid-cols-[35%_1fr] items-center gap-2">
                            <label class="text-[10px] text-slate-500 font-bold uppercase">Tanggal Bayar</label>
                            <input type="date" v-model="payment.tanggal_bayar"
                                class="w-full h-10 outline-none rounded border border-slate-200 focus:border-blue-500 px-3 text-sm">
                        </div>

                        <div class="grid grid-cols-[35%_1fr] items-center gap-2">
                            <label class="text-[10px] text-slate-500 font-bold uppercase">Metode</label>
                            <select v-model="payment.metode"
                                class="w-full h-10 outline-none rounded border border-slate-200 focus:border-blue-500 px-3 text-sm font-bold bg-white">
                                <option value="CASH">CASH (TUNAI)</option>
                                <option value="TRANSFER">TRANSFER BANK</option>
                                <option value="EDC">MESIN EDC</option>
                                <option value="SPLIT">SPLIT PAYMENT</option>
                                <option value="DP">DOWN PAYMENT</option>
                            </select>
                        </div>

                        <div v-if="['TRANSFER', 'EDC', 'DP'].includes(payment.metode)"
                            class="space-y-2 bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <input v-model="payment.bank_asal" placeholder="Bank Asal (Contoh: BCA)"
                                class="w-full h-9 text-sm outline-none rounded border border-blue-200 bg-white px-3 focus:ring-1 ring-blue-400">
                            <input v-model="payment.bank_tujuan" placeholder="Ke Bank Mana? (Contoh: Mandiri Toko)"
                                class="w-full h-9 text-sm outline-none rounded border border-blue-200 bg-white px-3 focus:ring-1 ring-blue-400">
                            <input v-model="payment.ref" placeholder="No. Ref / Kode Trace"
                                class="w-full h-9 text-sm outline-none rounded border border-blue-200 bg-white px-3 focus:ring-1 ring-blue-400">
                        </div>

                        <div class="grid grid-cols-[35%_1fr] items-center gap-2 pt-2 border-t">
                            <label class="text-[10px] text-slate-500 font-bold uppercase">Ongkos Kirim</label>
                            <input type="number" v-model.number="ongkir"
                                class="w-full h-10 outline-none rounded border border-slate-200 px-3 font-bold text-blue-700">
                        </div>

                        <div class="grid grid-cols-[35%_1fr] items-center gap-2">
                            <label class="text-[10px] text-slate-500 font-bold uppercase">Diskon Nota</label>
                            <input type="number" v-model.number="diskon"
                                class="w-full h-10 outline-none rounded border border-slate-200 px-3 font-bold text-red-600">
                        </div>

                        <div v-if="historyPembayaran.length > 0"
                            class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                            <h4 class="text-[10px] font-bold text-amber-700 uppercase mb-2">Riwayat Pembayaran</h4>
                            <div class="space-y-1">
                                <div v-for="det in historyPembayaran" :key="det.id"
                                    class="flex justify-between text-xs">
                                    <span class="text-slate-600">{{ det.metode_bayar }}</span>
                                    <span class="font-mono font-bold text-amber-800">Rp {{ formatNumber(det.nominal)
                                    }}</span>
                                </div>
                                <div
                                    class="flex justify-between font-bold border-t border-amber-200 pt-1 mt-1 text-sm text-amber-900">
                                    <span>Total Masuk</span>
                                    <span>Rp {{ formatNumber(totalSudahDibayar) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="payment.metode !== 'SPLIT'" class="grid grid-cols-[35%_1fr] items-center gap-2 mt-4">
                            <label class="text-[10px] text-blue-800 font-bold uppercase">Nominal Bayar</label>
                            <div class="relative">
                                <input type="number" v-model.number="payment.nominal1"
                                    class="w-full h-12 outline-none rounded border-2 border-blue-500 px-3 font-bold text-green-700 text-xl shadow-sm">
                                <button v-if="penjualan.status === 'DP'" @click="payment.nominal1 = sisaTagihanDP"
                                    class="absolute right-2 top-2 text-[10px] bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200">
                                    LUNASKAN
                                </button>
                            </div>
                        </div>

                        <div v-if="sisaSetelahBayarIni > 0" class="mt-1 text-[10px] font-bold text-red-600 italic">
                            * Sisa piutang setelah bayar: Rp {{ formatNumber(sisaSetelahBayarIni) }}
                        </div>

                        <div v-if="payment.metode === 'SPLIT'" class="space-y-4 pt-2">
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-200 space-y-2">
                                <label class="text-[10px] font-bold text-blue-700">PEMBAYARAN 1</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <select v-model="payment.split1_metode" class="h-9 text-xs border rounded px-2">
                                        <option value="CASH">CASH</option>
                                        <option value="TRANSFER">TRANSFER</option>
                                        <option value="EDC">EDC</option>
                                    </select>
                                    <input type="number" v-model.number="payment.nominal1" placeholder="Nominal"
                                        class="h-9 text-sm border rounded px-2 font-bold focus:ring-2 ring-blue-400 outline-none">
                                </div>
                                <div v-if="sisaTagihanSplit > 0" class="text-[10px] text-orange-600 font-bold italic">
                                    * Kurang: Rp {{ formatNumber(sisaTagihanSplit) }}
                                </div>
                                <div v-if="payment.split1_metode !== 'CASH'" class="space-y-2">
                                    <input v-model="payment.bank_asal" placeholder="Bank Asal"
                                        class="w-full h-8 text-xs px-2 border rounded">
                                    <input v-model="payment.bank_tujuan" placeholder="Bank Tujuan"
                                        class="w-full h-8 text-xs px-2 border rounded">
                                    <input v-model="payment.ref" placeholder="No. Ref"
                                        class="w-full h-8 text-xs px-2 border rounded">
                                </div>
                            </div>

                            <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-200 space-y-2">
                                <div class="flex justify-between items-center">
                                    <label class="text-[10px] font-bold text-indigo-700">PEMBAYARAN 2</label>
                                    <button @click="payment.nominal2 = sisaTagihanSplit"
                                        class="text-[9px] bg-indigo-200 hover:bg-indigo-300 px-2 py-0.5 rounded text-indigo-800 transition">
                                        Gunakan Sisa
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <select v-model="payment.split2_metode" class="h-9 text-xs border rounded px-2">
                                        <option value="CASH">CASH</option>
                                        <option value="TRANSFER">TRANSFER</option>
                                        <option value="EDC">EDC</option>
                                    </select>
                                    <input type="number" v-model.number="payment.nominal2" placeholder="Nominal"
                                        class="h-9 text-sm border rounded px-2 font-bold focus:ring-2 ring-indigo-400 outline-none">
                                </div>
                                <div v-if="payment.split2_metode !== 'CASH'" class="space-y-2">
                                    <input v-model="payment.bank_asal2" placeholder="Bank Asal"
                                        class="w-full h-8 text-xs px-2 border rounded">
                                    <input v-model="payment.bank_tujuan2" placeholder="Bank Tujuan"
                                        class="w-full h-8 text-xs px-2 border rounded">
                                    <input v-model="payment.ref2" placeholder="No. Ref"
                                        class="w-full h-8 text-xs px-2 border rounded">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t bg-slate-50 mt-auto">
                    <div class="space-y-1 mb-4 text-sm">
                        <div class="flex justify-between text-slate-500">
                            <span>Subtotal</span>
                            <span>{{ formatNumber(penjualan.total_harga) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-xl pt-2 border-t">
                            <span>Total Akhir</span>
                            <span class="text-blue-600">{{ formatNumber(totalKeseluruhan) }}</span>
                        </div>
                    </div>
                    <button @click="handleProsesPembayaran"
                        class="w-full bg-blue-600 text-white py-4 rounded-lg font-bold shadow-lg hover:bg-blue-700 active:scale-95 transition flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
                                clip-rule="evenodd" />
                        </svg>
                        PROSES & CETAK NOTA
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthLayout from '../../../Layouts/AuthLayout.vue'
import OffsetPanel from '../../../Components/OffsetPanel.vue'

defineOptions({ layout: AuthLayout })

const props = defineProps({
    penjualan: Object,
    historyPembayaran: Array,
})

// -- State --
const today = new Date().toLocaleDateString('id-ID', { dateStyle: 'long' })
const ongkir = ref(0)
const diskon = ref(0)

const payment = ref({
    tanggal_bayar: new Date().toISOString().slice(0, 10),
    metode: 'CASH',
    // Pembayaran 1
    split1_metode: 'CASH',
    nominal1: 0,
    bank_asal: '',
    bank_tujuan: '',
    ref: '',
    // Pembayaran 2
    split2_metode: 'TRANSFER',
    nominal2: 0,
    bank_asal2: '',
    bank_tujuan2: '',
    ref2: '',
})

// -- Methods --
const formatNumber = (val) => {
    return new Intl.NumberFormat('id-ID').format(val || 0)
}

// -- Computeds --
const totalKeseluruhan = computed(() => {
    return (
        Number(props.penjualan.total_penjualan || 0) +
        Number(ongkir.value || 0) -
        Number(diskon.value || 0)
    )
})

const totalSudahDibayar = computed(() => {
    return props.historyPembayaran.reduce((acc, item) => acc + Number(item.nominal || 0), 0)
})

const sisaTagihanSplit = computed(() => {
    const sisa = totalKeseluruhan.value - payment.value.nominal1
    return sisa > 0 ? sisa : 0
})

const sisaTagihanDP = computed(() => {
    const sisa = totalKeseluruhan.value - totalSudahDibayar.value
    return sisa > 0 ? sisa : 0
})

const kembalian = computed(() => {
    const totalUangMasukSekarang = payment.value.metode === 'SPLIT'
        ? (payment.value.nominal1 + payment.value.nominal2)
        : payment.value.nominal1

    const targetBayar = props.penjualan.status === 'DP' ? sisaTagihanDP.value : totalKeseluruhan.value
    const sisa = totalUangMasukSekarang - targetBayar
    return sisa > 0 ? sisa : 0
})

const sisaSetelahBayarIni = computed(() => {
    const totalUangMasukSekarang = payment.value.metode === 'SPLIT'
        ? (payment.value.nominal1 + payment.value.nominal2)
        : payment.value.nominal1

    const target = props.penjualan.status === 'DP' ? sisaTagihanDP.value : totalKeseluruhan.value
    const sisa = target - totalUangMasukSekarang
    return sisa > 0 ? sisa : 0
})

// -- Handlers --
const handleProsesPembayaran = () => {
    const totalUangMasukSekarang = payment.value.metode === 'SPLIT'
        ? (payment.value.nominal1 + payment.value.nominal2)
        : payment.value.nominal1

    const targetYgHarusDibayar = props.penjualan.status === 'DP' ? sisaTagihanDP.value : totalKeseluruhan.value

    if (payment.value.metode !== 'DP') {
        if (totalUangMasukSekarang < targetYgHarusDibayar) {
            alert('Nominal kurang! Gunakan metode DP jika pelanggan ingin mencicil/hutang.')
            return
        }
    }

    const payload = {
        tanggal_bayar: payment.value.tanggal_bayar,
        metode_bayar: payment.value.metode,
        ongkir: ongkir.value,
        diskon: diskon.value,
        yangdibayar: totalUangMasukSekarang,
        total_bayar: totalKeseluruhan.value,
        status_penjualan: sisaSetelahBayarIni.value > 0 ? 'DP' : 'LUNAS',
        details: [],
    }

    if (payment.value.metode === 'SPLIT') {
        payload.details.push({
            metode_bayar: payment.value.split1_metode,
            nominal: payment.value.nominal1,
            bank_asal: payment.value.bank_asal,
            bank_tujuan: payment.value.bank_tujuan,
            ref_pembayaran: payment.value.ref
        })
        payload.details.push({
            metode_bayar: payment.value.split2_metode,
            nominal: payment.value.nominal2,
            bank_asal: payment.value.bank_asal2,
            bank_tujuan: payment.value.bank_tujuan2,
            ref_pembayaran: payment.value.ref2
        })
    } else {
        payload.details.push({
            metode_bayar: payment.value.metode,
            nominal: payment.value.nominal1,
            bank_asal: payment.value.bank_asal,
            bank_tujuan: payment.value.bank_tujuan,
            ref_pembayaran: payment.value.ref
        })
    }

    router.post(route('ecer.penjualan.pembayaran.bayar', props.penjualan.id), payload, {
        onSuccess: () => {
            // Pindah halaman ke preview nota di tab yang sama
            router.visit(route('ecer.penjualan.pembayaran.print', props.penjualan.id));
        },
        onError: (errors) => {
            alert("Gagal memproses pembayaran. Silakan cek kembali.");
        }
    });
}
</script>

<style scoped>
::-webkit-scrollbar {
    width: 5px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

@media print {
    #forOffset {
        background: white !important;
    }

    .print\:hidden {
        display: none !important;
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
        padding: 0;
        margin: 0;
        box-shadow: none;
    }

    @page {
        size: 80mm auto;
        margin: 0;
    }
}
</style>