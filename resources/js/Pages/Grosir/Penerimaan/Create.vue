<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100 grid grid-cols-[1fr_300px]">

        <div class="w-full h-full p-6 overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3 text-2xl font-semibold text-primary">
                    <i class='bx bxs-package'></i> <span>Nota Penerimaan Barang</span>
                </div>
                <Link href="/grosir/penerimaan" class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm">
                    <i class='bx bx-arrow-back'></i> Kembali
                </Link>
            </div>

            <form @submit.prevent="submit">
                <div class="bg-white rounded-xl shadow-lg p-4 mb-6 grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium">No Nota</label>
                        <input type="text" v-model="form.nomer_pembelian"
                            class="w-full h-8 rounded-md border px-3 text-sm bg-gray-50">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Tanggal</label>
                        <input type="date" v-model="form.tanggal_transaksi"
                            class="w-full h-8 rounded-md border px-3 text-sm focus:ring focus:ring-primary/30">

                    </div>
                </div>

                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="bg-accent1/50 px-4 py-2 border-b">
                        <h2 class="text-lg font-bold text-primary">Produk & Roll Masuk</h2>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-primary/10">
                            <tr>
                                <th class="py-2 px-3 text-left w-[250px]">Info Produk</th>
                                <th class="py-2 px-3 text-left">Daftar Roll</th>
                                <th class="py-2 px-3 text-center w-24">Total</th>
                                <th class="py-2 px-3 text-center w-14">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index" class="border-b hover:bg-gray-50">
                                <td class="p-3">
                                    <div class="font-bold text-primary">{{ item.nama_produk }}</div>
                                    <div class="text-xs text-gray-800">{{ item.kode_warna }}</div>
                                    <!-- <div class="text-xs text-gray-800">{{ item.satuan_id }}</div> -->
                                </td>

                                <td class="p-3">
                                    <div class="flex flex-wrap gap-1 max-w-[300px]">
                                        <div v-for="(roll, rIdx) in item.details" :key="rIdx"
                                            class="bg-gray-100 border text-[10px] px-1.5 py-0.5 rounded flex items-center gap-1 group">
                                            <span>{{ roll.panjang_isi }}</span>
                                            <button type="button" @click="removeRoll(index, rIdx)"
                                                class="text-red-400 hover:text-red-600">×</button>
                                        </div>
                                        <input type="text" placeholder="Contoh: 40*10 atau 35"
                                            @keydown.enter.prevent="handleQuickInput($event, index)"
                                            class="w-full h-8 border rounded px-2 text-xs focus:border-blue-500 outline-none bg-blue-50">
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="font-bold text-blue-600">{{ item.details.length }} Rl</div>
                                    <div class="text-[10px] text-amber-900">{{ calculateItemTotal(index) }} Yd</div>
                                </td>
                                <td class="p-3 text-center">
                                    <button type="button" @click="removeItem(index)"
                                        class="text-red-500 hover:scale-110">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="form.items.length === 0">
                                <td colspan="5" class="py-10 text-center text-gray-400 italic">
                                    Belum ada produk. Silahkan cari produk di panel kanan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" :disabled="form.processing"
                        class="bg-indigo-600 px-10 py-2.5 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-lg disabled:opacity-50">
                        SIMPAN NOTA PENERIMAAN
                    </button>
                </div>
            </form>
        </div>

        <div class="w-full bg-slate-200 border-l shadow-2xl flex flex-col">
            <div class="flex h-12 border-b">
                <button @click="sideTab = 'cari'"
                    :class="sideTab === 'cari' ? 'border-b-2 border-primary text-primary font-bold' : 'text-gray-400'"
                    class="flex-1 text-sm transition-all">CARI PRODUK</button>
                <button @click="sideTab = 'buat'"
                    :class="sideTab === 'buat' ? 'border-b-2 border-primary text-primary font-bold' : 'text-gray-400'"
                    class="flex-1 text-sm transition-all">BUAT PRODUK</button>
            </div>

            <div v-if="sideTab === 'cari'" class="p-4 flex flex-col h-full overflow-hidden">
                <div class="relative mb-4">
                    <i class='bx bx-search absolute left-3 top-2 text-gray-400'></i>
                    <input type="search" v-model="searchQuery"
                        class="w-full h-9 pl-9 pr-4 rounded-full border border-gray-200 text-sm focus:ring-1 focus:ring-primary"
                        placeholder="Cari kain atau warna...">
                </div>

                <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                    <div v-for="p in filteredProducts" :key="p.id" @click="addProduct(p)"
                        class="p-3 border bg-white rounded-xl hover:bg-blue-50 cursor-pointer transition group border-gray-100 shadow-sm">
                        <div class="flex justify-between items-start">
                            <span class="font-bold text-gray-800 text-sm">{{ p.produk.nama }}</span>
                            <span class="text-[10px] bg-primary/10 text-primary px-2 rounded-full">
                                {{ p.tipe || 'No Type' }}
                            </span>
                        </div>
                        <div class="text-sm text-blue-900 mt-1 flex items-center justify-between">Warna: {{
                            p.nama }}

                            <i
                                class='bx bx-plus-circle text-lg text-primary opacity-0 group-hover:opacity-100 transition'></i>
                        </div>
                        <!-- <div class="flex justify-between mt-2 text-[10px] text-gray-400 border-t pt-2">
                            <span>Stok: {{ p.total_qty || 0 }} Roll</span>
                        </div> -->
                    </div>
                </div>
            </div>

            <div v-if="sideTab === 'buat'">
                <ProdukOffsideTab :product="produk_by_color" :varian="produk" :satuan="satuan"
                    :categories="categories" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ProdukOffsideTab from '../../../Components/Grosir/ProdukOffsideTab.vue';

const props = defineProps({
    produk_by_color: Object, // Dari Laravel Paginate [cite: 51]
    nomer_pembelian: String, // Contoh: INV012026SS- [cite: 51]
    produk: Array,
    satuan: Array,
    categories: Array
});

const sideTab = ref('cari');
const searchQuery = ref('');

// State Form berjenjang
const form = useForm({
    nomer_pembelian: props.nomer_pembelian,
    tanggal_transaksi: new Date().toISOString().substr(0, 10),
    items: [] // Level 2: pembelian_items
});

// Computed untuk Filter Produk di Sidebar
const filteredProducts = computed(() => {
    const list = props.produk_by_color?.data || [];
    if (!searchQuery.value) return list;

    return list.filter(p =>
        p.produk.nama.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        p.nama.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

// Fungsi Tambah Produk ke List Transaksi
const addProduct = (varian) => {
    const exists = form.items.find(i => i.varian_id === varian.id);
    if (!exists) {
        form.items.push({
            varian_id: varian.id,
            nama_produk: varian.produk.nama,
            kode_warna: varian.nama,
            satuan_id: varian.produk.satuan_id,
            tipe: varian.tipe,
            details: []
        });
        console.log(varian.nama);
    }

};

// Logika Input Cepat: 40*10 (Panjang 40 sebanyak 10 roll)
const handleQuickInput = (event, index) => {
    const val = event.target.value.trim();
    if (!val) return;

    if (val.includes('*')) {
        const parts = val.split('*');
        const panjang = parseFloat(parts[0]);
        const qty = parseInt(parts[1]);

        if (!isNaN(panjang) && !isNaN(qty)) {
            for (let i = 0; i < qty; i++) {
                form.items[index].details.push({ panjang_isi: panjang, lot_number: '' });
            }
        }
    } else {
        const panjang = parseFloat(val);
        if (!isNaN(panjang)) {
            form.items[index].details.push({ panjang_isi: panjang, lot_number: '' });
        }
    }
    event.target.value = ''; // Reset input text
};

const calculateItemTotal = (index) => {
    return form.items[index].details.reduce((acc, curr) => acc + curr.panjang_isi, 0).toFixed(2);
};

const removeRoll = (itemIdx, rollIdx) => {
    form.items[itemIdx].details.splice(rollIdx, 1);
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

const submit = () => {
    if (form.items.length === 0) {
        alert('Pilih minimal satu produk!');
        return;
    }
    form.post('/grosir/penerimaan/store', {
        onSuccess: () => {
            form.reset();
            Toast.fire({ icon: 'success', title: 'Penerimaan barang tersimpan' });
        }
    });
};
</script>

<style scoped>
/* Scrollbar halus untuk sidebar */
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>