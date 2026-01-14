<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    suppliers: Array,
});

const editingId = ref(null);


const form = useForm({
    nama_pt: '',
    nama: '',
    phone: '',
    email: '',
    alamat: '',
});

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

function submit() {
    if (editingId.value) {
        form.put(route('grosir.master.suppliers.update', editingId.value), {
            onSuccess: () => {
                editingId.value = null;
                form.reset();
                Toast.fire({ icon: 'success', title: 'Supplier berhasil diupdate' });
            }
        });
    } else {
        form.post(route('grosir.master.suppliers.store'), {
            onSuccess: () => {
                form.reset();
                Toast.fire({ icon: 'success', title: 'Supplier berhasil ditambah' });
            }
        });
    }
}

function startEdit(s) {
    editingId.value = s.id;
    form.nama_pt = s.nama_pt;
    form.nama = s.nama;
    form.phone = s.phone;
    form.email = s.email;
    form.alamat = s.alamat;
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function handleDelete(id, nama_pt) {
    Swal.fire({
        title: 'Hapus Supplier?',
        text: `Seluruh data ${nama_pt} akan dihapus!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('grosir.master.suppliers.destroy', id), {
                onSuccess: () => Toast.fire({ icon: 'success', title: 'Berhasil dihapus' })
            });
        }
    });
}
</script>

<template>
    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 font-sans">
        <div class="w-full bg-white rounded-lg shadow-sm border border-slate-200 h-fit">
            <div class="px-4 h-12 flex items-center font-bold border-b border-primary/20 bg-slate-50 rounded-t-lg">
                <h2 class="text-slate-700 uppercase text-xs tracking-widest">
                    {{ editingId ? 'Update Data Supplier' : 'Input Supplier Baru' }}
                </h2>
            </div>
            <div class="p-5">

                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Nama PT /
                                Perusahaan</label>
                            <input v-model="form.nama_pt" type="text" placeholder="PT. Nama Maju..."
                                class="w-full px-3 py-2 border rounded-md outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary text-sm transition-all" />
                            <p v-if="form.errors.nama_pt" class="text-red-500 text-[10px] mt-1">{{ form.errors.nama_pt
                                }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Contact
                                    Person</label>
                                <input v-model="form.nama" type="text" placeholder="Nama Admin..."
                                    class="w-full px-3 py-2 border rounded-md outline-none focus:ring-2 focus:ring-primary/30 text-sm" />
                            </div>
                            <div>
                                <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">No.
                                    Telepon</label>
                                <input v-model="form.phone" type="text" placeholder="08..."
                                    class="w-full px-3 py-2 border rounded-md outline-none focus:ring-2 focus:ring-primary/30 text-sm" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Email</label>
                            <input v-model="form.email" type="email" placeholder="supplier@email.com"
                                class="w-full px-3 py-2 border rounded-md outline-none focus:ring-2 focus:ring-primary/30 text-sm" />
                        </div>

                        <div>
                            <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Alamat
                                Lengkap</label>
                            <textarea v-model="form.alamat" rows="3" placeholder="Jl. Raya No. 123..."
                                class="w-full px-3 py-2 border rounded-md outline-none focus:ring-2 focus:ring-primary/30 text-sm"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-3">
                        <button v-if="editingId" @click="cancelEdit" type="button"
                            class="text-slate-400 text-xs font-bold uppercase hover:text-slate-600">Batal</button>
                        <button type="submit" :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-md text-xs uppercase tracking-widest transition-all disabled:opacity-50">
                            {{ editingId ? 'Simpan Perubahan' : 'Add Supplier' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="w-full bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-4 h-12 flex items-center font-bold border-b border-primary/20 bg-slate-50">
                <h2 class="text-slate-700 uppercase text-xs tracking-widest">Daftar Supplier</h2>
            </div>
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-100/50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase">Supplier /
                                PT</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase">Contact</th>
                            <th class="px-4 py-3 text-left text-[10px] font-black text-slate-400 uppercase">Alamat</th>
                            <th class="px-4 py-3 text-center text-[10px] font-black text-slate-400 uppercase w-20">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in suppliers" :key="s.id" class="hover:bg-indigo-50/30 transition-colors group">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-700">{{ s.nama_pt }}</div>
                                <div class="text-[10px] text-slate-400">{{ s.email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-slate-600 font-medium">{{ s.nama }}</div>
                                <div class="text-[10px] font-mono text-slate-400">{{ s.phone }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs max-w-[120px] truncate">{{ s.alamat }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="startEdit(s)" class="p-1.5 text-slate-400 hover:text-amber-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="handleDelete(s.id, s.nama_pt)"
                                        class="p-1.5 text-slate-400 hover:text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>