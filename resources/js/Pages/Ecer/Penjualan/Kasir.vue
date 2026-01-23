<template>
    <div class="w-full h-screen bg-slate-100 text-slate-800">
        <header class="h-10 px-4 flex items-center justify-between bg-white border-b">
            <Link href="/ecer/penjualan"
                class="px-3 h-7 rounded bg-slate-200 hover:bg-slate-300 flex items-center justify-center text-slate-700 text-sm transition">
                Back
            </Link>
            <h1 class="font-bold text-sm text-blue-600">POS Penjualan Eceran</h1>
            <span class="text-xs">{{ today }}</span>
        </header>

        <div class="grid grid-cols-[65%_35%] h-[calc(100vh-40px)]">

            <div class="w-full flex flex-col bg-slate-50">
                <div class="h-14 bg-white shadow-sm px-4 flex items-center gap-4 border-b">
                    <div class="relative w-full max-w-md">
                        <input type="text" placeholder="Cari nama kain atau kode varian..."
                            class="w-full pl-10 pr-4 py-2 border rounded-full text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none">
                        <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
                    </div>
                </div>
                <div class="w-full h-[calc(100vh-96px)] overflow-y-auto">

                    <div v-if="step === 1" class="w-full h-full flex items-center justify-center">
                        <h2 class="text-xl font-bold text-green-800">
                            Pilih atau Buat Pelanggan >
                        </h2>
                    </div>

                    <div v-if="step === 3" class="flex-1  p-6">
                        <div class="grid grid-cols-3 gap-4">
                            <div v-for="varian in produk" :key="varian.id" class="bg-white p-4 rounded-lg border">
                                <h3 class="font-bold text-sm">
                                    {{ varian.produk.nama }}
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Warna: {{ varian.nama }}
                                </p>

                                <div class="mt-3 space-y-2">
                                    <button v-for="roll in varian.details" :key="roll.id" @click="addRoll(varian, roll)"
                                        class="text-left w-full px-2 py-1 border rounded hover:bg-blue-50 transition">
                                        <span class="font-mono text-xs">
                                            {{ roll.qty }} {{ roll.satuan.nama_satuan }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="bg-white border-l flex flex-col relative">

                <div v-if="step === 1" class="p-4 space-y-4">
                    <h3 class="font-bold border-b pb-2">Data Penjualan</h3>
                    <div>
                        <label class="text-xs text-slate-400 font-bold">TANGGAL</label>
                        <input type="date" v-model="draftForm.tanggal_transaksi" class="w-full border p-2 rounded">
                    </div>

                    <div>
                        <label class="text-xs text-slate-400 font-bold">PELANGGAN</label>
                        <input v-model="draftForm.pelanggan" list="list-pelanggan" placeholder="Nama Pelanggan"
                            class="w-full border p-2 rounded">
                        <datalist id="list-pelanggan">
                            <option v-for="p in pelanggan" :key="p.id" :value="p.nama"></option>
                        </datalist>
                    </div>

                    <div>
                        <label class="text-xs text-slate-400 font-bold">PENANGGUNG JAWAB</label>
                        <input v-model="draftForm.marketing" list="list-marketing" placeholder="Marketing"
                            class="w-full border p-2 rounded">
                        <datalist id="list-marketing">
                            <option v-for="m in marketing" :key="m.id" :value="m.nama"></option>
                        </datalist>
                    </div>

                    <button @click="submitDraft"
                        class="w-full bg-blue-600 text-white py-3 rounded font-bold shadow-lg">SIMPAN</button>
                </div>

                <div v-if="step === 3" class="w-full h-[calc(100vh-165px)] overflow-y-auto p-4 space-y-3 pt-28">

                    <div class="w-full border-b px-4 text-sm absolute top-0 left-0 py-2 bg-white z-10 shadow-sm">
                        <h3 class="font-bold text-base">Keranjang Belanja</h3>
                        <div class="grid grid-cols-[30%_5%_1fr]">
                            <div>Nomer</div>
                            <div>:</div>
                            <div class="font-mono">{{ draft.nomer }}</div>
                        </div>
                        <div class="grid grid-cols-[30%_5%_1fr]">
                            <div>Tanggal</div>
                            <div>:</div>
                            <div>{{ draft.tanggal }}</div>
                        </div>
                        <div class="grid grid-cols-[30%_5%_1fr]">
                            <div>Pelanggan</div>
                            <div>:</div>
                            <div class="font-bold">{{ draft.pelanggan?.nama }} <span class="text-xs font-normal">/ Pj:
                                    {{ draft.marketing?.nama }}</span></div>
                        </div>
                        <div class="absolute top-6 right-4 space-y-2 flex flex-col">
                            <button type="button" @click="hapusPenjualan(draft.id)"
                                class="px-3 py-1 rounded-sm bg-red-500 font-bold text-white cursor-pointer hover:bg-red-600">Ganti</button>
                            <a href="/ecer/penjualan/transaksi"
                                class="px-3 py-1 rounded-sm bg-green-500 font-bold text-white text-center cursor-pointer hover:bg-green-600 text-xs">Refresh</a>
                        </div>
                    </div>

                    <div v-for="(item, index) in cart" :key="index"
                        class="border rounded-lg bg-white overflow-hidden shadow-sm border-blue-200">
                        <div class="bg-blue-50 px-3 py-2 flex justify-between items-center border-b border-blue-100">
                            <div>
                                <div class="font-bold text-blue-800 text-sm">{{ item.nama }}</div>
                                <div class="text-[11px] text-blue-600 font-medium">Warna: {{ item.warna }}</div>
                            </div>
                            <button @click="cart.splice(index, 1)" class="text-red-400 hover:text-red-600 text-xs">Hapus
                                Produk</button>
                        </div>

                        <div class="p-3 bg-white">
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Harga Per Meter/Yard</label>
                            <input type="number" v-model="item.harga"
                                class="w-full border rounded p-1 text-sm font-bold text-blue-700 bg-yellow-50 outline-none focus:border-yellow-400">
                        </div>

                        <div class="px-3 pb-3 space-y-2">
                            <div v-for="(roll, rIdx) in item.details" :key="rIdx"
                                class="flex items-center justify-between gap-2 bg-slate-50 p-2 rounded border border-dashed border-slate-300">
                                <div class="flex items-center gap-2">
                                    <input type="number" v-model="roll.qty" :disabled="roll.satuan_beli === 'Kg'"
                                        class="w-20 border rounded px-1 text-sm font-bold" step="0.01">

                                    <select v-model="roll.satuan_beli" @change="updateQtyBerdasarkanSatuan(roll)"
                                        class="text-xs border rounded bg-white p-1">
                                        <option value="Yard">Yard</option>
                                        <option value="Meter">Meter</option>
                                        <option value="Kg">KG</option>
                                    </select>
                                </div>

                                <div class="text-right">
                                    <div v-if="roll.satuan_stok !== roll.satuan_beli"
                                        class="text-[9px] text-red-500 italic">
                                        Harga Penyesuaian: {{ format(item.harga / roll.koefisien) }}/{{ roll.satuan_beli
                                        }}
                                    </div>
                                    <div class="text-[10px] text-slate-400">Subtotal</div>
                                    <div class="text-xs font-bold text-blue-600">
                                        {{ format(roll.qty * (item.harga / roll.koefisien)) }}
                                    </div>
                                    <span v-if="roll.qty > getMaxQty(roll)" class="text-[9px] text-red-600 font-bold">
                                        ⚠ Melebihi stok (Maks: {{ getMaxQty(roll) }} {{ roll.satuan_beli }})
                                    </span>
                                </div>
                                <button @click="item.details.splice(rIdx, 1)"
                                    class="text-slate-300 hover:text-red-500 px-1 font-bold">✕</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="step === 3" class="p-4 border-t bg-slate-50">
                    <div class="flex justify-between font-bold text-lg mb-3">
                        <span>Total Keseluruhan</span>
                        <span class="text-blue-600">{{ format(grandTotal) }}</span>
                    </div>
                    <button @click="handleProsesPembayaran"
                        class="w-full bg-blue-600 text-white py-3 rounded font-bold shadow-lg hover:bg-blue-700 active:scale-95 transition">
                        PROSES PEMBAYARAN
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthLayout from '../../../Layouts/AuthLayout.vue';

defineOptions({ layout: AuthLayout })

const props = defineProps({
    draft: Object,
    pelanggan: Array,
    marketing: Array,
    produk: Array,
})

const today = new Date().toLocaleDateString('id-ID')
const step = ref(props.draft ? 3 : 1)
const cart = ref([]);

const draftForm = ref({
    tanggal_transaksi: new Date().toISOString().slice(0, 10),
    pelanggan: '',
    marketing: '',
})

const payment = ref({
    tanggal_bayar: new Date().toISOString().slice(0, 10),
    metode: 'CASH',
    total_bayar: 0,
    bank_asal: '',
    ref: '',
})

// 1. Fungsi Konversi (Update Qty Langsung)
const updateQtyBerdasarkanSatuan = (roll) => {
    const asal = roll.qty_stok;

    if (roll.satuan_stok === 'Yard' && roll.satuan_beli === 'Meter') {
        // Yard ke Meter: Qty mengecil (x 0.9144), maka Harga harus membesar (/ 0.9144)
        roll.qty = parseFloat((asal * 0.9144).toFixed(2));
        roll.koefisien = 0.9144;
    }
    else if (roll.satuan_stok === 'Meter' && roll.satuan_beli === 'Yard') {
        // Meter ke Yard: Qty membesar (x 1.0936), maka Harga harus mengecil (/ 1.0936)
        roll.qty = parseFloat((asal * 1.0936).toFixed(2));
        roll.koefisien = 1.0936;
    }
    else {
        roll.qty = asal;
        roll.koefisien = 1;
    }
};
const roundUp500 = (amount) => {
    // Angka asli / 500, lalu bulatkan ke atas, lalu kali 500 lagi
    return Math.ceil(amount / 500) * 500;
};

const calculateItemTotal = (item) => {
    const totalUangAsli = item.details.reduce((sum, roll) => {
        const hargaKonversi = item.harga / (roll.koefisien || 1);
        return sum + (roll.qty * hargaKonversi);
    }, 0);

    // Terapkan pembulatan 500 ke atas
    return roundUp500(totalUangAsli);
}

// 3. Fungsi Tambah ke Keranjang
const addRoll = (varian, roll) => {
    let itemProduk = cart.value.find(item => item.variant_id === varian.id);
    const namaSatuanStok = roll.satuan?.nama_satuan || 'Yard';
    const qtyAwal = parseFloat(roll.qty);

    const detailBaru = {
        roll_id: roll.id,
        qty: qtyAwal,
        qty_stok: qtyAwal,
        satuan_id: roll.satuan_id,
        satuan_stok: namaSatuanStok,
        satuan_beli: namaSatuanStok,
        koefisien: 1 // Tambahkan ini agar tidak undefined saat pertama kali masuk
    };

    if (itemProduk) {
        const isRollExist = itemProduk.details.find(d => d.roll_id === roll.id);
        if (!isRollExist) {
            itemProduk.details.push(detailBaru);
        } else {
            alert('Roll ini sudah ada di keranjang');
        }
    } else {
        cart.value.push({
            variant_id: varian.id,
            nama: varian.produk.nama_produk || varian.produk.nama,
            warna: varian.nama_varian || varian.nama,
            harga: varian.harga_jual || 0,
            details: [detailBaru]
        });
    }
};

// Fungsi hitung grand total keseluruhan (dari nested cart)
const grandTotal = computed(() => {
    return cart.value.reduce((total, item) => total + calculateItemTotal(item), 0)
})

const submitDraft = () => {
    router.post('/ecer/penjualan/transaksi/draft', draftForm.value, {
        onSuccess: () => step.value = 3
    })
}

const hapusPenjualan = (id) => {
    if (confirm('Apakah Anda yakin ingin membatalkan transaksi ini dan memulai dari awal?')) {
        router.delete(`/ecer/penjualan/transaksi/${id}`, {
            onSuccess: () => {
                step.value = 1;
                cart.value = [];
                draftForm.value = {
                    tanggal_transaksi: new Date().toISOString().slice(0, 10),
                    pelanggan: '',
                    marketing: '',
                };
            }
        });
    }
}

const handleProsesPembayaran = () => {
    if (cart.value.length === 0) return alert("Keranjang masih kosong")

    // Sinkronkan total bayar dengan grand total
    payment.value.total_bayar = grandTotal.value;

    router.post('/ecer/penjualan/transaksi/item', {
        penjualan_id: props.draft.id,
        items: cart.value
    }, {

        onError: (errors) => {
            if (errors.cart) {
                alert("Gagal Memproses: " + errors.cart);
            } else {
                alert("Terjadi kesalahan sistem, silakan cek inputan Anda.");
            }
        },
        onSuccess: () => {
            step.value = 4;
        }
    });
}
const getMaxQty = (roll) => {
    // Menghitung batas input berdasarkan satuan yang dipilih
    if (roll.satuan_beli === 'Meter' && roll.satuan_stok === 'Yard') {
        return parseFloat((roll.qty_stok * 0.9144).toFixed(2));
    }
    if (roll.satuan_beli === 'Yard' && roll.satuan_stok === 'Meter') {
        return parseFloat((roll.qty_stok * 1.0936).toFixed(2));
    }
    return roll.qty_stok;
};


const format = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)

</script>

<style scoped>
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
</style>
