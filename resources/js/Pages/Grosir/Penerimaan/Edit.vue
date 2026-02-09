<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100 grid grid-cols-[1fr_300px]">
        <div class="w-full h-full p-6 overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3 text-2xl font-semibold text-primary">
                    <i class='bx bxs-edit'></i> <span>Edit Nota Penerimaan Barang</span>
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
                            class="w-full h-8 rounded-md border px-3 text-sm bg-gray-50 focus:ring focus:ring-primary/30 outline-none">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Tanggal</label>
                        <input type="date" v-model="form.tanggal_transaksi"
                            class="w-full h-8 rounded-md border px-3 text-sm focus:ring focus:ring-primary/30 outline-none">
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="bg-amber-50 px-4 py-2 border-b">
                        <h2 class="text-lg font-bold text-amber-700">Daftar Produk Terinput</h2>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-3 text-left w-[250px]">Info Produk</th>
                                <th class="py-2 px-3 text-left">Daftar Roll (Yd)</th>
                                <th class="py-2 px-3 text-center w-24">Total</th>
                                <th class="py-2 px-3 text-center w-14">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index"
                                class="border-b hover:bg-gray-50 transition">
                                <td class="p-3">
                                    <div class="font-bold text-primary">{{ item.nama_produk }}</div>
                                    <div class="text-xs text-gray-600">Warna: {{ item.kode_warna }}</div>
                                    <div v-if="item.tipe" class="text-[10px] text-gray-400 italic">{{ item.tipe }}</div>
                                </td>

                                <td class="p-3">
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        <div v-for="(roll, rIdx) in item.details" :key="rIdx"
                                            class="bg-blue-50 border border-blue-200 text-[10px] px-2 py-0.5 rounded flex items-center gap-1 group">
                                            <span class="font-medium">{{ roll.panjang_isi }}</span>
                                            <button type="button" @click="removeRoll(index, rIdx)"
                                                class="text-red-400 hover:text-red-600 font-bold">×</button>
                                        </div>
                                    </div>
                                    <input type="text" placeholder="Input cepat: 40*10 atau 35"
                                        @keydown.enter.prevent="handleQuickInput($event, index)"
                                        class="w-full h-8 border border-dashed rounded px-2 text-xs focus:border-blue-500 outline-none bg-blue-50/50">
                                </td>

                                <td class="p-3 text-center">
                                    <div class="font-bold text-blue-600 text-base">{{ item.details.length }} Roll</div>
                                    <div class="text-xs font-bold text-amber-600 bg-amber-50 rounded px-1">{{
                                        calculateItemTotal(index) }} Yd</div>
                                </td>

                                <td class="p-3 text-center">
                                    <button type="button" @click="removeItem(index)"
                                        class="text-red-500 hover:bg-red-50 p-2 rounded-full transition">
                                        <i class='bx bx-trash text-lg'></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="form.items.length === 0">
                                <td colspan="4" class="py-12 text-center text-gray-400 italic">
                                    Belum ada produk. Silahkan pilih dari daftar produk di sebelah kanan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" :disabled="form.processing"
                        class="bg-amber-600 px-10 py-3 text-white rounded-lg font-bold hover:bg-amber-700 shadow-lg disabled:opacity-50 transition-all flex items-center gap-2">
                        <i class='bx bx-save text-xl'></i>
                        {{ form.processing ? 'MENYIMPAN...' : 'UPDATE NOTA PENERIMAAN' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="w-full bg-slate-50 border-l shadow-2xl flex flex-col">
            <div class="flex h-12 border-b bg-white">
                <button @click="sideTab = 'cari'"
                    :class="sideTab === 'cari' ? 'border-b-2 border-primary text-primary font-bold' : 'text-gray-400'"
                    class="flex-1 text-xs transition-all tracking-wider">CARI PRODUK</button>
                <button @click="sideTab = 'buat'"
                    :class="sideTab === 'buat' ? 'border-b-2 border-primary text-primary font-bold' : 'text-gray-400'"
                    class="flex-1 text-xs transition-all tracking-wider">PRODUK BARU</button>
            </div>

            <div v-if="sideTab === 'cari'" class="p-4 flex flex-col h-full overflow-hidden">
                <CariProduk @tambah-produk="addProduct" />
            </div>

            <div v-if="sideTab === 'buat'" class="h-full overflow-y-auto">
                <ProdukOffsideTab :product="produk_by_color" :varian="produk" :satuan="satuan"
                    :categories="categories" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ProdukOffsideTab from '../../../Components/Grosir/ProdukOffsideTab.vue';
import CariProduk from '../../../Components/Grosir/CariProduk.vue';

const props = defineProps({
    pembelian: Object,
    produk_by_color: Object,
    produk: Array,
    satuan: Array,
    categories: Array
});

const sideTab = ref('cari');

// Inisialisasi Form dengan Mapping Data Lama
const form = useForm({
    _method: 'PUT', // Spoofing method agar Laravel menerima sebagai PUT
    nomer_pembelian: props.pembelian.nomer,
    tanggal_transaksi: props.pembelian.tanggal,
    items: props.pembelian.items.map(it => ({
        varian_id: it.varian_id,
        nama_produk: it.varian?.produk?.nama || 'Unknown',
        kode_warna: it.varian?.nama || '-',
        satuan_id: it.satuan_id,
        tipe: it.varian?.tipe,
        details: it.details.map(det => ({
            panjang_isi: Number(det.qty), // Pastikan angka
            lot_number: det.lot_number || ''
        }))
    }))
});



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

        // Opsional: Berikan feedback suara atau toast kecil
        // Toast.fire({ icon: 'info', title: 'Produk ditambahkan ke list' });
    } else {
        alert('Produk sudah ada di daftar');
    }
};

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
    event.target.value = '';
};

const calculateItemTotal = (index) => {
    const total = form.items[index].details.reduce((acc, curr) => {
        return acc + (parseFloat(curr.panjang_isi) || 0);
    }, 0);
    return total.toFixed(2);
};

const removeRoll = (itemIdx, rollIdx) => {
    form.items[itemIdx].details.splice(rollIdx, 1);
};

const removeItem = (index) => {
    Swal.fire({
        title: 'Hapus item?',
        text: "Item ini akan dihapus dari daftar nota ini",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            form.items.splice(index, 1);
        }
    });
};

const submit = () => {
    if (form.items.length === 0) {
        Swal.fire('Error', 'Minimal harus ada satu produk!', 'error');
        return;
    }

    // Kirim menggunakan form.post dengan method spoofing _method: 'PUT'
    // agar kompatibel dengan data yang mungkin mengandung file/multipart
    form.post(`/grosir/penerimaan/${props.pembelian.id}`, {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Nota Berhasil Diupdate',
                showConfirmButton: false,
                timer: 2000
            });
        },
        onError: (err) => {
            console.error(err);
            Swal.fire('Gagal', 'Terjadi kesalahan saat menyimpan data', 'error');
        }
    });
};

</script>

<style scoped>
.custom-scroll::-webkit-scrollbar {
    width: 5px;
}

.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>