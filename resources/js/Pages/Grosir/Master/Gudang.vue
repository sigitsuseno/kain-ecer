<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { debounce } from 'lodash-es';

const props = defineProps({
    gudangs: Array,
    filters: Object,
});

const editingId = ref(null);
const search = ref(props.filters?.search || '');

// Form sesuai Model Gudang
const form = useForm({
    nama_gudang: '',
    lokasi: '',
    deskripsi: '',
});

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

// Fitur Search Reaktif
watch(search, debounce((value) => {
    router.get(route('grosir.master.gudang.index'), { search: value }, {
        preserveState: true,
        replace: true
    });
}, 300));

function submit() {
    if (editingId.value) {
        form.put(route('grosir.master.gudang.update', editingId.value), {
            onSuccess: () => {
                editingId.value = null;
                form.reset();
                Toast.fire({ icon: 'success', title: 'Data gudang diperbarui' });
            }
        });
    } else {
        form.post(route('grosir.master.gudang.store'), {
            onSuccess: () => {
                form.reset();
                Toast.fire({ icon: 'success', title: 'Gudang berhasil ditambah' });
            }
        });
    }
}

function startEdit(g) {
    editingId.value = g.id;
    form.nama_gudang = g.nama_gudang;
    form.lokasi = g.lokasi;
    form.deskripsi = g.deskripsi;
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function handleDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Gudang?',
        text: `Gudang "${nama}" akan dihapus dari sistem!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('grosir.master.gudang.destroy', id), {
                onSuccess: () => Toast.fire({ icon: 'success', title: 'Gudang telah dihapus' })
            });
        }
    });
}
</script>

<template>
    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="w-full bg-white rounded-lg shadow-sm border border-slate-200 h-fit">
            <div class="px-4 h-12 flex items-center font-bold border-b border-primary/20 bg-slate-50 rounded-t-lg">
                <h2 class="text-slate-700 uppercase text-xs tracking-widest">
                    {{ editingId ? 'Update Data Gudang' : 'Input Gudang Baru' }}
                </h2>
            </div>
            <div class="p-5">
                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Nama Gudang</label>
                        <input v-model="form.nama_gudang" type="text" placeholder="Gudang Utama / Cabang..."
                            class="w-full px-3 py-2 border rounded-md outline-none focus:ring-2 focus:ring-primary/30 text-sm transition-all" />
                        <p v-if="form.errors.nama_gudang" class="text-red-500 text-[10px] mt-1">{{
                            form.errors.nama_gudang }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Lokasi</label>
                        <input v-model="form.lokasi" type="text" placeholder="Kota / Alamat singkat..."
                            class="w-full px-3 py-2 border rounded-md outline-none focus:ring-2 focus:ring-primary/30 text-sm" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Deskripsi</label>
                        <textarea v-model="form.deskripsi" rows="3" placeholder="Keterangan tambahan..."
                            class="w-full px-3 py-2 border rounded-md outline-none focus:ring-2 focus:ring-primary/30 text-sm"></textarea>
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-3">
                        <button v-if="editingId" @click="cancelEdit" type="button"
                            class="text-slate-400 text-xs font-bold uppercase hover:text-slate-600">Batal</button>
                        <button type="submit" :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-md text-xs uppercase tracking-widest transition-all disabled:opacity-50">
                            {{ editingId ? 'Simpan Perubahan' : 'Simpan Gudang' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="w-full bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 h-12 flex items-center justify-between border-b border-primary/20 bg-slate-50">
                <h2 class="text-slate-700 uppercase text-xs tracking-widest font-bold">Daftar Gudang</h2>
                <div class="relative w-48">
                    <input v-model="search" type="search" placeholder="Cari gudang..."
                        class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-full text-xs outline-none focus:ring-2 focus:ring-indigo-500/20 text-sm transition-all">
                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-100/50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase">Nama Gudang
                            </th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase">Lokasi</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black text-slate-400 uppercase w-20">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="g in gudangs" :key="g.id" class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-700">{{ g.nama_gudang }}</div>
                                <div class="text-[10px] text-slate-400 italic truncate max-w-[150px]">{{ g.deskripsi ||
                                    '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ g.lokasi }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="startEdit(g)"
                                        class="p-1.5 text-slate-400 hover:text-amber-600 transition-all rounded-md hover:bg-amber-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="handleDelete(g.id, g.nama_gudang)"
                                        class="p-1.5 text-slate-400 hover:text-red-600 transition-all rounded-md hover:bg-red-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="gudangs.length === 0">
                            <td colspan="3" class="p-8 text-center text-slate-400 italic">Data tidak ditemukan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>