<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100 relative">
        <div class="text-2xl font-semibold text-primary py-2 px-6 mt-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class='bx bxs-package'></i> <span>Riwayat Penerimaan Barang</span>
            </div>
            <div class="flex items-center gap-4">
                <Link href="/grosir/penerimaan/update-data"
                    class="px-4 h-9 flex items-center gap-2 text-sm bg-indigo-600 hover:bg-indigo-700 active:scale-90 text-white rounded shadow-md transition-all">
                    <i class='bx bx-plus'></i>
                    UPDATE DATA
                </Link>
                <Link href="/grosir/penerimaan/create"
                    class="px-4 h-9 flex items-center gap-2 text-sm bg-indigo-600 hover:bg-indigo-700 active:scale-90 text-white rounded shadow-md transition-all">
                    <i class='bx bx-plus'></i>
                    Buat Nota Baru
                </Link>
            </div>
        </div>

        <div class="px-4 w-full h-[calc(100vh-120px)] overflow-y-auto">
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="w-full flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <select v-model="params.per_page"
                            class="w-20 h-9 px-2 rounded border border-gray-300 text-sm outline-none focus:border-primary">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <div class="relative">
                            <i class='bx bx-search absolute left-3 top-2.5 text-gray-400'></i>
                            <input type="search" v-model="params.search"
                                class="w-72 h-9 pl-9 pr-4 text-sm border border-gray-300 rounded outline-none focus:border-primary"
                                placeholder="Cari No. Nota...">
                        </div>
                    </div>
                </div>

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-20 bg-slate-200 text-gray-800 uppercase text-xs">
                                <th class="px-2 py-1 text-left border-b w-10">#</th>
                                <th class="px-2 py-1 text-left border-b w-40">No. Nota</th>
                                <th class="px-2 py-1 text-left border-b">Ringkasan Produk</th>
                                <th class="px-2 py-1 text-center border-b">Total Roll</th>
                                <th class="px-2 py-1 text-center border-b">Total Panjang</th>
                                <th class="px-2 py-1 text-center border-b">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-for="(p, pIndex) in pembelians.data" :key="p.id">
                                <tr v-for="(it, itIndex) in p.items" :key="it.id"
                                    class="hover:bg-blue-50/50 transition border border-slate-400">

                                    <td v-if="itIndex === 0" :rowspan="p.items.length"
                                        class="px-2 py-2 text-gray-500 align-top text-center border border-slate-400">
                                        {{ (pembelians.current_page - 1) * pembelians.per_page + pIndex + 1 }}
                                    </td>

                                    <td v-if="itIndex === 0" :rowspan="p.items.length"
                                        class="px-2 py-2 font-bold text-primary align-top border border-slate-400">
                                        <div class="flex flex-col">
                                            <span class="text-sm">{{ p.nomer }}</span>
                                            <span class="text-xs text-gray-600 font-normal">{{ p.tanggal }}</span>
                                        </div>
                                    </td>

                                    <td class="px-2 py-2 ">
                                        <div class="flex flex-col">
                                            <div class="font-bold text-gray-800">
                                                {{ it.varian?.produk?.nama }} - {{ it.varian?.nama }}
                                            </div>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span v-for="det in it.details" :key="det.id"
                                                    class="w-12 flex items-center justify-center text-xs  py-0.5 bg-gray-100 rounded shadow-sm border"
                                                    :class="det.status === 'terjual' ? 'text-red-500' : ''">
                                                    {{ det.qty }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-2 py-2 text-center font-bold text-indigo-600 ">
                                        {{ it.total_jumlah }} Roll
                                    </td>

                                    <td class="px-2 py-2 text-center font-bold text-blue-600 ">
                                        {{ it.total_qty }} Yd
                                    </td>

                                    <td v-if="itIndex === 0" :rowspan="p.items.length"
                                        class="px-2 py-2 text-center border-l align-top border border-slate-400">
                                        <div class="flex items-center justify-center gap-2">
                                            <Link :href="`/grosir/penerimaan/${p.id}/edit`"
                                                class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200"
                                                title="Edit / Detail">
                                                <i class='bx bx-edit text-lg'></i>
                                            </Link>

                                            <button @click="deleteNota(p.id, p.nomer)"
                                                class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200"
                                                title="Hapus">
                                                <i class='bx bx-trash text-lg'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="pembelians.data.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-gray-400 italic">Data tidak ditemukan
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ pembelians.from }} sampai {{ pembelians.to }} dari {{ pembelians.total }} data
                    </div>
                    <div class="flex gap-1">
                        <Link v-for="link in pembelians.links" :key="link.label" :href="link.url || '#'"
                            class="px-3 py-1 text-xs border rounded transition-all" :class="{
                                'bg-primary text-white border-primary': link.active,
                                'text-gray-400 pointer-events-none': !link.url,
                                'hover:bg-gray-100': link.url && !link.active
                            }" v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    pembelians: Object,
    filters: Object
});

const params = ref({
    search: props.filters.search || '',
    per_page: props.filters.per_page || '10'
});


// --- FUNGSI DELETE ---
const deleteNota = (id, nomer) => {
    Swal.fire({
        title: 'Hapus Nota?',
        text: `Apakah Anda yakin ingin menghapus nota ${nomer}? Tindakan ini akan mengembalikan (mengurangi) stok yang sudah masuk secara otomatis.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                router.delete(`/grosir/penerimaan/${id}`, {
                    onSuccess: () => {
                        Swal.fire('Terhapus!', 'Nota dan stok terkait berhasil dihapus.', 'success');
                        resolve();
                    },
                    onError: () => {
                        Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                        resolve();
                    }
                });
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    });
};

// Fungsi debounce manual tanpa lodash
let timeout = null;
watch(params, (newParams) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/grosir/penerimaan', newParams, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    }, 500);
}, { deep: true });
</script>>