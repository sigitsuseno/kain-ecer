<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toPng } from 'html-to-image' // Library yang anda install
import Swal from 'sweetalert2' // Library alert

const props = defineProps({
    penjualan: Object, // Mengacu pada model
})

// --- STATE MANAGEMENT ---
const activeTab = ref('cash-tf')
const isProcessing = ref(false)

const tabs = [
    { id: 'cash-tf', label: 'Tunai / TF' },
    { id: 'split', label: 'Split' },
    { id: 'dp', label: 'DP / Hutang' },
    { id: 'pengaturan', label: 'Setting' },
]

// Form Input Pembayaran
const form = reactive({
    metode_1: 'cash',
    bayar_1: 0, // Nominal utama
    metode_2: 'transfer', // Nominal kedua (untuk split)
    bayar_2: 0,
    catatan: ''
})

// --- COMPUTED LOGIC (Otomatis Hitung) ---

// Total Tagihan dari Backend
const grandTotal = computed(() => {
    // Menggunakan field dari model Penjualan
    // Asumsi: total_penjualan sudah termasuk pajak/diskon. Jika belum, sesuaikan rumusnya.
    return parseFloat(props.penjualan.total_penjualan || 0)
})

// Total yang diinput user saat ini
const totalInputBayar = computed(() => {
    let total = 0
    if (activeTab.value === 'split') {
        total = parseFloat(form.bayar_1 || 0) + parseFloat(form.bayar_2 || 0)
    } else {
        total = parseFloat(form.bayar_1 || 0)
    }
    return total
})

// Hitung Kembalian
const kembalian = computed(() => {
    const sisa = totalInputBayar.value - grandTotal.value
    return sisa > 0 ? sisa : 0
})

// Hitung Sisa Piutang (Kurang Bayar)
const sisaPiutang = computed(() => {
    const sisa = grandTotal.value - totalInputBayar.value
    return sisa > 0 ? sisa : 0
})

// Tentukan Status Akhir untuk Backend
const statusAkhir = computed(() => {
    // Jika tab DP aktif, paksa status partial (walaupun input lunas)
    // Atau jika bayar kurang dari total, otomatis partial
    if (activeTab.value === 'dp' || totalInputBayar.value < grandTotal.value) {
        return 'partial' // Belum lunas
    }
    return 'paid' // Lunas
})

// Format Rupiah
const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID').format(val || 0)
}

// --- ACTIONS ---

// 1. Fungsi Export Gambar (html-to-image)
const downloadStruk = async () => {
    const node = document.getElementById('printable-receipt')

    if (!node) return

    try {
        const dataUrl = await toPng(node, {
            quality: 1.0,
            backgroundColor: '#ffffff', // Penting agar background putih, bukan transparan
            pixelRatio: 2 // Agar gambar tajam
        })

        const link = document.createElement('a')
        link.download = `Nota-${props.penjualan.nomer}.png`
        link.href = dataUrl
        link.click()
    } catch (error) {
        console.error('Gagal generate gambar:', error)
        Swal.fire('Error', 'Gagal membuat gambar nota', 'error')
    }
}

// 2. Fungsi Print Browser
const printStruk = async () => {
    try {
        // Tampilkan loading Swall
        Swal.fire({
            title: 'Mencetak...',
            didOpen: () => { Swal.showLoading() },
            allowOutsideClick: false
        });

        const response = await axios.post(`/grosir/penjualan/direct-print/${props.penjualan.id}`);

        Swal.close();
        if (response.status === 200) {
            // Berhasil
        }
    } catch (error) {
        Swal.fire('Gagal', 'Printer tidak merespon atau tidak terhubung.', 'error');
    }
}

// 3. Proses Pembayaran ke Backend
const prosesBayar = () => {
    if (totalInputBayar.value <= 0) {
        Swal.fire('Warning', 'Nominal pembayaran belum diisi!', 'warning')
        return
    }

    if (activeTab.value !== 'dp' && sisaPiutang.value > 0) {
        Swal.fire('Warning', 'Pembayaran kurang! Gunakan tab DP jika ingin hutang.', 'warning')
        return
    }

    Swal.fire({
        title: 'Proses Pembayaran?',
        text: `Total Bayar: Rp ${formatCurrency(totalInputBayar.value)}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Bayar',
        confirmButtonColor: '#3085d6'
    }).then((result) => {
        if (result.isConfirmed) {
            submitKeBackend()
        }
    })
}

const submitKeBackend = () => {
    isProcessing.value = true

    // Siapkan payload array pembayaran
    let listPembayaran = []

    // Pembayaran 1
    if (form.bayar_1 > 0) {
        listPembayaran.push({
            metode: form.metode_1,
            jumlah: form.bayar_1
        })
    }

    // Pembayaran 2 (Khusus Split)
    if (activeTab.value === 'split' && form.bayar_2 > 0) {
        listPembayaran.push({
            metode: form.metode_2,
            jumlah: form.bayar_2
        })
    }

    router.post('/penjualan/bayar', {
        penjualan_id: props.penjualan.id,
        status: statusAkhir.value,
        pembayaran: listPembayaran,
        kembalian: kembalian.value,
        sisa_piutang: sisaPiutang.value // Opsional, backend bisa hitung ulang
    }, {
        onSuccess: () => {
            isProcessing.value = false
            Swal.fire('Berhasil', 'Pembayaran tersimpan!', 'success')
        },
        onError: () => {
            isProcessing.value = false
            Swal.fire('Gagal', 'Terjadi kesalahan sistem', 'error')
        }
    })
}

// --- HELPER FORMATTING ---
const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(number);
}

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }).format(date); // Hasil: 30-01-2026 10:30
}


// // --- LOGIC PRINT BLUETOOTH ---
// const isPrinting = ref(false)

// // Konstanta perintah ESC/POS (Bahasa Printer)
// const ESC = '\x1B';
// const GS = '\x1D';
// const COMMANDS = {
//     INIT: ESC + '@', // Reset Printer
//     CENTER: ESC + 'a' + '\x01', // Rata Tengah
//     LEFT: ESC + 'a' + '\x00', // Rata Kiri
//     RIGHT: ESC + 'a' + '\x02', // Rata Kanan
//     BOLD_ON: ESC + 'E' + '\x01', // Bold Nyala
//     BOLD_OFF: ESC + 'E' + '\x00', // Bold Mati
//     CUT: GS + 'V' + '\x41' + '\x00', // Potong Kertas (jika support)
//     FEED: ESC + 'd' + '\x02', // Feed 2 baris
// }

// // Helper untuk membuat spasi agar teks rata kanan/kiri rapi
// const padText = (left, right, width = 32) => {
//     // Default lebar kertas 58mm biasanya 32 karakter
//     let spaces = width - left.length - right.length;
//     if (spaces < 0) spaces = 1;
//     return left + ' '.repeat(spaces) + right + '\n';
// }

// const printBluetooth = async () => {
//     isPrinting.value = true;
//     try {
//         // 1. Request Device (Mencari Printer Bluetooth)
//         const device = await navigator.bluetooth.requestDevice({
//             filters: [
//                 { services: ['000018f0-0000-1000-8000-00805f9b34fb'] } // UUID standar printer thermal
//             ],
//             optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb']
//         });

//         // 2. Connect ke GATT Server
//         const server = await device.gatt.connect();
//         const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
//         const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

//         // 3. Susun Data Struk
//         let receipt = '';

//         // --- HEADER ---
//         receipt += COMMANDS.INIT;
//         receipt += COMMANDS.CENTER;
//         receipt += COMMANDS.BOLD_ON;
//         receipt += "UTEX\n"; // Ganti Nama Toko
//         receipt += COMMANDS.BOLD_OFF;
//         receipt += "Jl. Contoh No. 123, Kota\n";
//         receipt += "--------------------------------\n"; // 32 dash

//         receipt += COMMANDS.LEFT;
//         receipt += `No      : ${props.penjualan.nomer}\n`;
//         receipt += `Tgl     : ${props.penjualan.tanggal}\n`;
//         receipt += `Plg     : ${props.penjualan.pelanggan?.nama || 'Umum'}\n`;
//         receipt += "--------------------------------\n";

//         // --- ITEMS ---
//         // Looping item penjualan
//         if (props.penjualan.items && props.penjualan.items.length > 0) {
//             props.penjualan.items.forEach(item => {
//                 // Nama Produk
//                 let namaProduk = item.varian?.produk?.nama || 'Item';
//                 let namaVarian = item.varian?.nama || '';
//                 receipt += `${namaProduk} ${namaVarian}\n`;

//                 // Qty x Harga
//                 // Contoh: 2.5 m x 10.000 = 25.000
//                 let qtyInfo = `${parseFloat(item.qty)} ${item.satuan.inisial || ''} x ${formatRupiah(item.harga)}`;
//                 let subtotalItem = formatRupiah(item.jumlah_harga);

//                 receipt += padText(qtyInfo, subtotalItem);
//             });
//         }
//         receipt += "--------------------------------\n";

//         // --- TOTAL ---
//         receipt += padText("Subtotal", formatRupiah(props.penjualan.total_penjualan));
//         if (props.penjualan.diskon > 0) {
//             receipt += padText("Diskon", "-" + formatRupiah(props.penjualan.diskon));
//         }
//         if (props.penjualan.ongkir > 0) {
//             receipt += padText("Ongkir", formatRupiah(props.penjualan.ongkir));
//         }

//         receipt += COMMANDS.BOLD_ON;
//         receipt += padText("TOTAL", formatRupiah(grandTotal.value));
//         receipt += COMMANDS.BOLD_OFF;
//         receipt += "--------------------------------\n";

//         // --- FOOTER ---
//         receipt += COMMANDS.CENTER;
//         receipt += "Terima Kasih\n";
//         receipt += "Barang yang sudah dibeli\n";
//         receipt += "tidak dapat ditukar/dikembalikan\n";
//         receipt += COMMANDS.FEED; // Feed kertas agar mudah disobek

//         // 4. Encode ke Unit8Array dan Kirim ke Printer
//         const encoder = new TextEncoder();
//         const data = encoder.encode(receipt);

//         // Kirim data dalam chunk (max 512 bytes) untuk mencegah buffer overflow di printer murah
//         const chunkSize = 100;
//         for (let i = 0; i < data.length; i += chunkSize) {
//             const chunk = data.slice(i, i + chunkSize);
//             await characteristic.writeValue(chunk);
//         }

//         alert("Struk berhasil dicetak!");

//     } catch (error) {
//         console.error('Bluetooth Error:', error);
//         alert('Gagal mencetak: ' + error.message);
//     } finally {
//         isPrinting.value = false;
//     }
// }

</script>
<template>
    <div
        class="w-full h-[calc(100vh-40px)] overflow-hidden bg-blue-100 grid grid-cols-[1fr_400px] print:block print:bg-white print:h-auto print:overflow-visible">

        <div class="w-full flex flex-col bg-slate-100 relative print:bg-white overflow-y-auto print:overflow-visible">
            <div
                class="flex flex-col items-center justify-center py-10 print:py-0 print:w-full print:absolute print:top-0 print:left-0">

                <div class="flex gap-2 mb-4 print:hidden">
                    <button type="button" @click="printBluetooth" :disabled="isPrinting"
                        class="flex-1 py-3 px-4 bg-slate-700 text-white font-bold rounded shadow hover:bg-slate-800 disabled:opacity-50 flex items-center justify-center gap-2">

                        <span v-if="isPrinting">Connecting...</span>
                        <span v-else>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2">
                                </path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Print Struk
                        </span>
                    </button>
                    <button @click="printStruk"
                        class="flex items-center gap-2 bg-gray-800 text-white px-4 py-2 rounded shadow hover:bg-gray-700">
                        <i class='bx bx-printer'></i> Print
                    </button>
                    <button @click="downloadStruk"
                        class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-500">
                        <i class='bx bx-image'></i> Simpan JPG
                    </button>
                </div>

                <h2 class="font-bold text-lg mb-4 print:hidden">Pratinjau Nota</h2>

                <div id="printable-receipt"
                    class="w-[80mm] shadow-lg bg-white p-4 print:shadow-none print:p-0 font-mono text-black relative">

                    <div class="receipt-header text-xs border-b border-dashed pb-2 text-center">
                        <h2 class="store-name font-bold text-base">UTEX</h2>
                        <p>Jl. Musi No.12, RT.05/RW.02</p>
                        <p>Sidomulyo, Ungaran Timur</p>
                    </div>

                    <div class="receipt-info text-xs grid grid-cols-[30%_5%_1fr] py-2 border-b border-dashed">
                        <span>Nota</span><span>:</span><span>{{ penjualan.nomer }}</span>
                        <span>Tgl</span><span>:</span><span>{{ penjualan.tanggal }}</span>
                        <span>Plg</span><span>:</span><span>{{ penjualan.pelanggan?.nama || 'Umum' }}</span>
                        <span>Kasir</span><span>:</span><span>{{ penjualan.user?.name || 'Admin' }}</span>
                    </div>

                    <div class="receipt-items py-2 border-b border-dashed min-h-[50px]">
                        <template v-for="pi in penjualan.items" :key="pi.id">
                            <div class="mb-2">
                                <div class="item-name text-xs font-bold uppercase">
                                    {{ pi.varian?.produk?.nama }} {{ pi.varian?.nama }}
                                </div>
                                <div v-for="idet in pi.details" :key="idet.id"
                                    class="grid grid-cols-[1fr_25%] text-[11px]">
                                    <div class="text-start text-[10px]">
                                        {{ idet.qty }} {{ idet.satuan?.inisial }} x {{ formatCurrency(idet.harga) }}
                                    </div>
                                    <div class="text-end">{{ formatCurrency(idet.jumlah_harga) }}</div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="receipt-total py-2 space-y-1 text-xs font-mono">
                        <div class="total-row flex justify-between font-bold text-sm">
                            <span>TOTAL TAGIHAN</span>
                            <span>{{ formatCurrency(grandTotal) }}</span>
                        </div>

                        <div v-if="penjualan.ongkir > 0 || penjualan.diskon > 0"
                            class="total-row flex justify-between italic text-[10px] text-gray-500">
                            <span>Ongkir / Diskon</span>
                            <span>{{ formatCurrency(penjualan.ongkir) }} / -{{ formatCurrency(penjualan.diskon)
                            }}</span>
                        </div>

                        <div class="total-row flex justify-between border-t border-dashed mt-1 pt-1 font-bold">
                            <span class="uppercase">BAYAR ({{ activeTab === 'split' ? 'SPLIT' : form.metode_1 }})</span>
                            <span>{{ formatCurrency(totalInputBayar) }}</span>
                        </div>

                        <div v-if="kembalian > 0" class="total-row flex justify-between pt-1">
                            <span>KEMBALI</span>
                            <span>{{ formatCurrency(kembalian) }}</span>
                        </div>

                        <div v-if="sisaPiutang > 0"
                            class="total-row flex justify-between border-t border-double pt-1 text-red-600 font-bold text-sm">
                            <span>SISA PIUTANG</span>
                            <span>{{ formatCurrency(sisaPiutang) }}</span>
                        </div>
                    </div>

                    <div class="receipt-footer text-center mt-6 text-[9px] uppercase leading-tight">
                        <p>*** Terima Kasih ***</p>
                        <p>Barang yang sudah dibeli</p>
                        <p>tidak dapat ditukar/dikembalikan.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full h-[calc(100vh-40px)] bg-blue-200 relative print:hidden flex flex-col">

            <div class="w-full h-10 bg-blue-700/90 flex items-end justify-between px-2 pt-2">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                    'flex-1 mx-0.5 rounded-t-md py-2 text-xs font-bold uppercase transition-all',
                    activeTab === tab.id
                        ? 'bg-white text-blue-800 translate-y-[1px]' // Active
                        : 'bg-blue-800 text-blue-200 hover:bg-blue-600' // Inactive
                ]">
                    {{ tab.label }}
                </button>
            </div>

            <div class="flex-1 bg-white p-6 overflow-y-auto shadow-inner">

                <div v-if="activeTab === 'cash-tf'" class="space-y-4 animate-fade-in">
                    <div class="bg-blue-50 p-3 rounded border border-blue-100 mb-4">
                        <h3 class="font-bold text-blue-800">Pembayaran Langsung</h3>
                        <p class="text-xs text-blue-600">Pelunasan menggunakan satu metode pembayaran.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Metode</label>
                        <select v-model="form.metode_1" class="w-full border p-2 rounded bg-gray-50 cursor-pointer">
                            <option value="cash">TUNAI (CASH)</option>
                            <option value="transfer">TRANSFER BANK</option>
                            <option value="edc">MESIN EDC</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Nominal Diterima</label>
                        <input type="number" v-model="form.bayar_1"
                            class="w-full border p-3 rounded text-right text-2xl font-bold font-mono focus:ring-2 ring-blue-500 outline-none"
                            placeholder="0">
                    </div>

                    <div class="flex justify-end">
                        <button @click="form.bayar_1 = grandTotal" class="text-xs text-blue-600 hover:underline">Uang
                            Pas ({{
                                formatCurrency(grandTotal) }})</button>
                    </div>

                    <div class="border-t pt-4 mt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Kembalian</span>
                            <span class="text-xl font-bold text-green-600">{{ formatCurrency(kembalian) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'split'" class="space-y-4 animate-fade-in">
                    <div class="bg-indigo-50 p-3 rounded border border-indigo-100 mb-4">
                        <h3 class="font-bold text-indigo-800">Split Pembayaran</h3>
                        <p class="text-xs text-indigo-600">Gabungan Tunai & Transfer.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-500">Metode 1</label>
                            <div class="flex">
                                <span
                                    class="bg-gray-100 border border-r-0 p-2 rounded-l text-sm w-20 flex items-center justify-center font-bold">TUNAI</span>
                                <input type="number" v-model="form.bayar_1"
                                    class="w-full border p-2 rounded-r text-right font-mono font-bold" placeholder="0">
                            </div>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-500">Metode 2</label>
                            <div class="flex">
                                <select v-model="form.metode_2"
                                    class="bg-gray-100 border border-r-0 p-2 rounded-l text-sm w-20 outline-none">
                                    <option value="transfer">TF</option>
                                    <option value="edc">EDC</option>
                                    <option value="qris">QRIS</option>
                                </select>
                                <input type="number" v-model="form.bayar_2"
                                    class="w-full border p-2 rounded-r text-right font-mono font-bold" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-100 p-3 rounded mt-4">
                        <div class="flex justify-between text-sm">
                            <span>Total Masuk</span>
                            <span class="font-bold">{{ formatCurrency(totalInputBayar) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1 text-red-500" v-if="sisaPiutang > 0">
                            <span>Kurang</span>
                            <span class="font-bold">-{{ formatCurrency(sisaPiutang) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1 text-green-600" v-if="kembalian > 0">
                            <span>Kembali</span>
                            <span class="font-bold">{{ formatCurrency(kembalian) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="activeTab === 'dp'" class="space-y-4 animate-fade-in">
                    <div class="bg-orange-50 p-3 rounded border border-orange-100 mb-4">
                        <h3 class="font-bold text-orange-800">Down Payment (DP)</h3>
                        <p class="text-xs text-orange-600">Pembayaran sebagian, status menjadi piutang.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Metode DP</label>
                        <select v-model="form.metode_1" class="w-full border p-2 rounded bg-gray-50">
                            <option value="cash">TUNAI</option>
                            <option value="transfer">TRANSFER</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-500 mb-1">Nominal DP</label>
                        <input type="number" v-model="form.bayar_1"
                            class="w-full border p-3 rounded text-right text-2xl font-bold font-mono text-orange-600 focus:ring-2 ring-orange-500 outline-none"
                            placeholder="0">
                    </div>

                    <div class="bg-red-50 border border-red-200 p-4 rounded text-center mt-2">
                        <span class="text-xs uppercase text-red-500 font-bold block mb-1">Sisa Piutang</span>
                        <span class="text-3xl font-bold text-red-600 block">{{ formatCurrency(sisaPiutang) }}</span>
                    </div>
                </div>

                <div v-if="activeTab === 'pengaturan'" class="text-center text-gray-500 py-10">
                    <i class='bx bx-cog text-4xl mb-2'></i>
                    <p>Pengaturan printer thermal & ukuran kertas dapat ditambahkan di sini.</p>
                </div>

            </div>

            <div class="p-4 bg-white border-t" v-if="activeTab !== 'pengaturan'">
                <button @click="prosesBayar" :disabled="isProcessing"
                    class="w-full py-4 rounded-lg font-bold text-lg text-white shadow-lg transition-transform active:scale-95 flex justify-center items-center gap-2"
                    :class="[
                        isProcessing ? 'bg-gray-400 cursor-not-allowed' :
                            activeTab === 'dp' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-blue-600 hover:bg-blue-700'
                    ]">
                    <span v-if="isProcessing" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
                    <span v-else>
                        {{ activeTab === 'dp' ? 'SIMPAN DP' : 'BAYAR SEKARANG' }}
                    </span>
                </button>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Scrollbar Custom */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* Menghilangkan panah pada input number */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Animasi tab switch */
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Print Styles */
@media print {
    @page {
        margin: 0;
        size: auto;
    }

    body {
        background-color: white;
    }

    /* Pastikan background tercetak (untuk browser modern) */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>