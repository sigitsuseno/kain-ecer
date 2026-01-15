<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    categories: Array,
});

// State untuk melacak baris mana yang sedang diedit
const editingId = ref(null);

// Form untuk Tambah & Edit
const form = useForm({
    nama_kategori: '',
    status: 'nama',
});


const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

// 1. Fungsi Tambah Kategori
function handleCreate() {
    form.post(route('grosir.master.categories.store'), {
        onSuccess: () => {
            form.reset();
            Toast.fire({
                icon: 'success',
                title: 'Kategori berhasil disimpan'
            });
        },
    });
}

function startEdit(category) {
    editingId.value = category.id;
    form.nama_kategori = category.nama_kategori;
}

function handleUpdate(id) {
    if (!form.nama_kategori) return;

    router.put(`/grosir/master/categories/${id}`, {
        nama_kategori: form.nama_kategori
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            form.reset();
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memperbarui',
                text: errors.nama_kategori || 'Terjadi kesalahan sistem',
            });
        }
    });
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function handleDelete(id, nama_kategori) {
    Swal.fire({
        text: `Data "${nama_kategori}" akan dihapus permanen!`,
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/grosir/master/categories/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Kategori telah berhasil dihapus.', 'success');
                }
            });
        }
    });
}

</script>

<template>
    <div class="p-6 h-full flex flex-col gap-6">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Tambah Kategori Baru</h3>
            <div class="flex gap-2">
                <div class="w-full grid grid-cols-[1fr_250px] gap-2">
                    <div class="w-full">
                        <input v-model="form.nama_kategori" @keyup.enter="handleCreate" type="text"
                            placeholder="Contoh: Rayon Twill" :disabled="editingId !== null"
                            class="w-full flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all text-sm" />
                        <div v-if="form.errors.nama_kategori" class="text-red-500 text-[10px] mt-1">
                            {{ form.errors.nama_kategori }}
                        </div>
                    </div>
                    <div class="w-full">
                        <select v-model="form.status" @keyup.enter="handleCreate" type="text"
                            :disabled="editingId !== null"
                            class="w-full flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all text-sm">
                            <option value="nama">Nama</option>
                            <option value="warna">Warna</option>
                        </select>
                        <div v-if="form.errors.status" class="text-red-500 text-[10px] mt-1">
                            {{ form.errors.status }}
                        </div>
                    </div>
                </div>
                <button @click="handleCreate" :disabled="form.processing || editingId !== null"
                    class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-all active:scale-95 disabled:opacity-50 font-sans">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </button>
            </div>
            <p v-if="form.errors.nama_kategori" class="text-red-500 text-xs mt-1">{{ form.errors.nama_kategori }}</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex-1">
            <div class="h-full overflow-y-auto font-sans">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-slate-50 z-10">
                        <tr class="border-b border-slate-200">
                            <th
                                class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest w-16 text-center">
                                No</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Nama
                                Kategori</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Status</th>
                            <th
                                class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(c, index) in categories" :key="c.id"
                            class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-6 py-4 text-sm text-slate-400 font-mono text-center">{{ index + 1 }}</td>

                            <td class="px-6 py-4">
                                <div v-if="editingId === c.id" class="flex items-center gap-2">
                                    <div>
                                        <input v-model="form.nama_kategori" @keyup.enter="handleUpdate(c.id)"
                                            @keyup.esc="cancelEdit" type="text"
                                            class="flex-1 px-3 py-1.5 bg-white border border-indigo-400 rounded-lg outline-none text-sm shadow-sm ring-2 ring-indigo-500/10"
                                            auto-focus />
                                        <div v-if="form.errors.nama_kategori" class="text-red-500 text-[10px] mt-1">
                                            {{ form.errors.nama_kategori }}
                                        </div>
                                    </div>
                                    <button @click="handleUpdate(c.id)"
                                        class="text-xs font-bold text-green-600 hover:text-green-700">Simpan</button>
                                    <button @click="cancelEdit"
                                        class="text-xs font-bold text-slate-400 hover:text-slate-600">Batal</button>
                                </div>

                                <span v-else class="font-semibold text-slate-700 uppercase tracking-tight text-sm">
                                    {{ c.nama_kategori }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400 font-mono text-center">{{ c.status }}</td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <template v-if="editingId !== c.id">
                                    <button @click="startEdit(c)"
                                        class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="handleDelete(c.id, c.nama_kategori)"
                                        class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>