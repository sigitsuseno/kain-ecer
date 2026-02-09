<template>
    <div class="w-full h-screen bg-slate-100 text-slate-800">
        <header class="h-10 px-4 flex items-center justify-between bg-indigo-900">
            <Link href="/ecer/penjualan"
                class="px-3 h-7 rounded bg-slate-200 hover:bg-slate-300 flex items-center justify-center text-slate-700 text-sm transition">
                Back
            </Link>
            <h1 class="font-bold text-sm text-white">POS Penjualan Eceran</h1>
            <span class="text-xs text-white">{{ today }}</span>
        </header>

        <div class="grid grid-cols-[65%_35%] h-[calc(100vh-40px)]">

            <div class="w-full flex flex-col bg-slate-50">
                <div class="h-14 bg-blue-200 shadow-sm px-4 flex items-center gap-4 border-b border-primary/10">
                    <div class="relative w-full max-w-md">
                        <input type="text" v-model="searchQuery" @input="handleSearch"
                            placeholder="Cari nama kain atau kode varian..."
                            class="bg-white outline-none w-full pl-10 pr-4 py-3 md:py-2 rounded-full text-base md:text-sm focus:ring-2 focus:ring-blue-400 border-none shadow-sm">
                        <span class="absolute left-3 top-3 md:top-2.5 text-slate-400">🔍</span>

                        <button v-if="searchQuery" @click="searchQuery = ''; handleSearch()"
                            class="absolute right-4 top-3 md:top-2.5 text-slate-400 hover:text-red-500">
                            ✕
                        </button>
                    </div>
                </div>
                <div class="w-full h-[calc(100vh-96px)] overflow-y-auto bg-blue-100">

                    <div v-if="step === 1" class="w-full h-full flex items-center justify-center">
                        <h2 class="text-xl font-bold text-green-800">
                            Pilih atau Buat Pelanggan >
                        </h2>
                    </div>

                    <div v-if="step === 3" class="flex-1  p-6">
                        <div class="grid grid-cols-4 gap-4 ">
                            <div v-for="varian in produk.data" :key="varian.id"
                                class="bg-white p-4 rounded-lg shadow border border-primary/10">
                                <h3 class="font-bold text-sm">
                                    {{ varian.produk.nama }}
                                </h3>
                                <p class="text-xs text-slate-500">
                                    Warna: {{ varian.nama }}
                                </p>

                                <div class="mt-3 space-y-2 grid grid-cols-1">
                                    <button v-for="roll in varian.details" :key="roll.id" @click="addRoll(varian, roll)"
                                        class="h-12 text-center w-full px-2 py-1 border rounded hover:bg-blue-50 transition">
                                        <span class="font-mono text-base font-bold">
                                            {{ roll.qty }} {{ roll.satuan.inisial }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 mb-10 flex justify-center gap-4">
                        <button v-if="produk.prev_page_url"
                            @click="router.get(produk.prev_page_url, {}, { preserveState: true, preserveScroll: true })"
                            class="px-8 py-4 bg-white border-2 border-indigo-600 text-indigo-600 rounded-xl font-bold active:scale-95 transition">
                            ⬅️ Sebelumnya
                        </button>

                        <button v-if="produk.next_page_url"
                            @click="router.get(produk.next_page_url, {}, { preserveState: true, preserveScroll: true })"
                            class="px-8 py-4 bg-indigo-600 text-white rounded-xl font-bold active:scale-95 transition">
                            Berikutnya ➡️
                        </button>
                    </div>
                </div>

            </div>

            <div class="bg-white border-l border-primary/30 flex flex-col relative h-full overflow-y-auto">

                <div v-if="step === 1" class="p-4 space-y-4">
                    <h3 class="font-bold border-b pb-2">Data Penjualan</h3>
                    <div>
                        <label class="text-xs text-slate-400 font-bold">TANGGAL</label>
                        <input type="date" v-model="draftForm.tanggal_transaksi" class="w-full border p-2 rounded">
                    </div>

                    <div class="relative">
                        <label class="text-sm font-medium text-gray-700">Pelanggan</label>
                        <div class="relative">
                            <input type="text" v-model="draftForm.pelanggan" @focus="showCustomerList = true"
                                @blur="handleBlurCustomer"
                                class="w-full h-11 md:h-8 rounded-md border px-3 text-sm focus:ring-2 focus:ring-blue-400 outline-none border-gray-300"
                                placeholder="Cari Pelanggan...">
                        </div>

                        <div v-if="showCustomerList && filteredCustomers.length > 0"
                            class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-md shadow-xl max-h-60 overflow-y-auto">
                            <div v-for="c in filteredCustomers" :key="c.id" @mousedown.prevent="selectCustomer(c.nama)"
                                class="px-4 py-3 text-sm hover:bg-blue-600 hover:text-white cursor-pointer transition-colors border-b border-gray-50 active:bg-blue-700">
                                <div class="font-semibold">{{ c.nama }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="relative">
                            <label class="text-sm font-medium text-gray-700">PJ </label>
                            <div class="relative">
                                <input type="text" v-model="draftForm.marketing" @focus="showMarketingList = true"
                                    @blur="handleBlurMarketing"
                                    class="w-full h-8 rounded-md border px-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none border-gray-300"
                                    placeholder="Cari PJ...">
                                <i class='bx bx-chevron-down absolute right-2 top-2 text-gray-400'></i>
                            </div>
                        </div>
                        <div v-if="showMarketingList && filteredMarketing.length > 0"
                            class="absolute z-[100] w-full mt-1 bg-white border border-gray-200 rounded-md shadow-xl max-h-48 overflow-y-auto box-border">
                            <div v-for="m in filteredMarketing" :key="m.id" @mousedown="selectMarketing(m.nama)"
                                class="px-3 py-2 text-sm hover:bg-primary hover:text-white cursor-pointer transition-colors border-b border-gray-50 last:border-none">
                                <div class="font-semibold">{{ m.nama }}</div>
                            </div>
                        </div>
                    </div>

                    <button @click="submitDraft"
                        class="w-full bg-blue-600 text-white py-3 rounded font-bold shadow-lg">SIMPAN</button>
                </div>

                <div v-if="step === 3" class="w-full h-[calc(100vh-165px)] overflow-y-auto p-4 space-y-3 pt-28">

                    <div
                        class="w-full border-b border-primary/20 px-4 text-sm absolute top-0 left-0 py-2 bg-white z-10 shadow-sm">
                        <h3 class="font-bold text-base">Keranjang Belanja</h3>

                        <div v-if="draft">
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
                                <div class="font-bold">
                                    {{ draft.pelanggan?.nama }}
                                    <span class="text-xs font-normal">/ Pj: {{ draft.marketing?.nama }}</span>
                                </div>
                            </div>
                            <div class="absolute top-2 right-4 space-y-2 flex flex-col">
                                <button type="button" @click="hapusPenjualan(draft.id)"
                                    class="w-16 h-10 flex items-center justify-center rounded-sm bg-red-500 font-bold text-white cursor-pointer hover:bg-red-600">Ganti</button>
                                <a href="/ecer/penjualan/transaksi"
                                    class="w-16 h-10 rounded-sm bg-green-500 font-bold text-white text-center cursor-pointer hover:bg-green-600 text-xs flex items-center justify-center">Refresh</a>
                            </div>
                        </div>

                        <div v-else class="text-xs text-red-500 italic py-2">
                            Sedang memuat data transaksi...
                        </div>
                    </div>

                    <div v-for="(item, index) in cart" :key="index"
                        class="p-4 border-b bg-white mb-3 rounded-xl shadow-sm">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-bold text-indigo-900">{{ item.produk_nama }} <span
                                    class="font-normal text-slate-500">- {{ item.varian_nama }}</span></h4>
                            <button @click="cart.splice(index, 1)" class="text-red-400 text-xs">Hapus</button>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(d, idx) in item.details" :key="idx"
                                class="grid grid-cols-12 gap-2 items-center bg-slate-50 p-2 rounded-lg border border-slate-200">

                                <div class="col-span-3 text-[10px] text-slate-500 font-mono">
                                    ID: {{ d.roll_id }}<br>
                                    Ori: {{ d.qty_stok_asli }} {{ d.satuan_asli }}
                                </div>

                                <div class="col-span-4">
                                    <input type="number" v-model="d.qty" @input="calculateTotal(item)"
                                        class="w-full h-9 text-center border-b-2 border-indigo-200 bg-transparent focus:border-indigo-500 outline-none font-bold text-sm">
                                </div>

                                <div class="col-span-3">
                                    <button v-if="d.satuan_asli !== 'Kg'" @click="toggleSatuan(item, d)"
                                        class="w-full py-1.5 bg-white border border-slate-300 rounded text-[10px] font-black active:bg-indigo-600 active:text-white">
                                        {{ d.satuan_jual }} 🔄
                                    </button>
                                    <span v-else class="text-xs font-bold text-slate-400 ml-2">Kg</span>
                                </div>

                                <div class="col-span-2 text-right">
                                    <button @click="item.details.splice(idx, 1)"
                                        class="text-slate-300 hover:text-red-500">✕</button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-end border-t pt-3">
                            <div class="w-1/2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase">Harga Jual
                                    (Manual)</label>
                                <div class="flex items-center">
                                    <span class="text-xs text-slate-400 mr-1">Rp</span>
                                    <input type="number" v-model="item.harga" @input="calculateTotal(item)"
                                        class="w-full h-8 border-b-2 border-indigo-100 focus:border-indigo-500 outline-none font-bold text-indigo-700">
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-slate-400 font-bold uppercase">Subtotal</p>
                                <p class="text-base font-black text-slate-800">{{ format(item.total || 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="step === 3" class="p-4 border-t border-primary/20 bg-slate-50">
                    <div class="flex justify-between font-bold text-lg mb-3">
                        <span>Total Keseluruhan</span>
                        <span class="text-blue-600">{{ format(grandTotal) }}</span>
                    </div>
                    <button @click="handleProsesPenjualan"
                        class="w-full bg-blue-600 text-white py-3 rounded font-bold shadow-lg hover:bg-blue-700 active:scale-95 transition">
                        PROSES PEMBAYARAN
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthLayout from '../../../Layouts/AuthLayout.vue';

defineOptions({ layout: AuthLayout })

const props = defineProps({
    draft: Object,
    pelanggan: Array,
    marketing: Array,
    produk: Object,
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

/* =========================
 * CUSTOM SELECT DROPDOWN
 * ========================= */
// ... import dan props tetap sama ...

const showCustomerList = ref(false)
const showMarketingList = ref(false)

// Filter Pelanggan - Tambahkan .value
const filteredCustomers = computed(() => {
    if (!draftForm.value.pelanggan) return props.pelanggan; // Tambahkan .value
    return props.pelanggan.filter(c =>
        c.nama.toLowerCase().includes(draftForm.value.pelanggan.toLowerCase()) // Tambahkan .value
    );
});

// Filter Marketing - Tambahkan .value
const filteredMarketing = computed(() => {
    const search = draftForm.value.marketing || ''; // Fallback jika null
    if (!search) return props.marketing;
    return props.marketing.filter(m =>
        m.nama && m.nama.toLowerCase().includes(search.toLowerCase())
    );
});

// Fungsi memilih data
const selectCustomer = (nama) => {
    draftForm.value.pelanggan = nama; // Pastikan menggunakan .value karena draftForm adalah ref
    showCustomerList.value = false;
    console.log(draftForm.value.pelanggan);
};


const selectMarketing = (nama) => {
    draftForm.value.marketing = nama; // Pastikan menggunakan .value
    showMarketingList.value = false;
};

// Menutup dropdown saat klik di luar (opsional tapi bagus)
const closeDropdowns = () => {
    showCustomerList.value = false
    showMarketingList.value = false
}

const handleBlurCustomer = () => {
    setTimeout(() => {
        showCustomerList.value = false
    }, 200)
}

const handleBlurMarketing = () => {
    setTimeout(() => {
        showMarketingList.value = false
    }, 200)
}

/* --------------------------
*  Search produk
* -------------------------- */

const searchQuery = ref('');
let debounceTimer = null;

const handleSearch = () => {
    // Hapus timer sebelumnya jika user masih mengetik
    clearTimeout(debounceTimer);

    // Set timer baru
    debounceTimer = setTimeout(() => {
        router.get('/ecer/penjualan/transaksi',
            { cari_produk: searchQuery.value },
            {
                preserveState: true,  // Sangat penting agar input tidak reset/hilang fokus
                preserveScroll: true, // Agar scroll tidak balik ke atas
                replace: true         // Agar tidak memenuhi history 'back' browser
            }
        );
    }, 500); // Tunggu 0.5 detik setelah berhenti mengetik
};


/* --------------------------
*  Fungsi Konversi
* -------------------------- */

const toggleSatuan = (itemVarian, rollDetail) => {
    if (rollDetail.satuan_asli === 'Kg') return;

    if (rollDetail.satuan_jual === 'Yard') {
        // Pindah ke METER
        rollDetail.satuan_jual = 'Meter';
        rollDetail.konversi = 0.9144; // Koefisien yard ke meter
        rollDetail.qty = parseFloat((rollDetail.qty_stok_asli * rollDetail.konversi).toFixed(2));
    } else {
        // Kembali ke YARD (Satuan asli)
        rollDetail.satuan_jual = 'Yard';
        rollDetail.konversi = 1; // Kembali ke normal
        rollDetail.qty = rollDetail.qty_stok_asli;
    }
    calculateTotal(itemVarian);
};

const calculateTotal = (item) => {
    const totalQtyJual = item.details.reduce((sum, d) => sum + parseFloat(d.qty || 0), 0);
    item.total = totalQtyJual * (item.harga || 0);
    return item.total;
};

const grandTotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + calculateTotal(item), 0);
});

const addRoll = (varian, roll) => {
    let existingVarian = cart.value.find(item => item.variant_id === varian.id);

    const newRollDetail = {
        roll_id: roll.id,
        konversi: 1,
        qty: roll.qty,
        qty_stok_asli: roll.qty,
        satuan_asli: roll.satuan.nama,
        satuan_jual: roll.satuan.nama
    };

    if (existingVarian) {
        const isRollExist = existingVarian.details.find(d => d.roll_id === roll.id);
        if (isRollExist) return alert("Roll ini sudah ada");
        existingVarian.details.push(newRollDetail);
    } else {
        cart.value.push({
            variant_id: varian.id,
            produk_nama: varian.produk.nama,
            varian_nama: varian.nama,
            harga: 0,
            details: [newRollDetail]
        });
    }
};

onMounted(() => {
    if (props.draft) {
        // Gunakan || '' untuk memastikan tidak pernah null
        draftForm.value.pelanggan = props.draft.pelanggan?.nama || '';
        draftForm.value.marketing = props.draft.marketing?.nama || '';
        draftForm.value.tanggal_transaksi = props.draft.tanggal || new Date().toISOString().slice(0, 10);
    }
});


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

const handleProsesPenjualan = () => {
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
