<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { debounce } from 'lodash-es';

const props = defineProps({
    pelanggans: Array,
    filters: Object,
});

const editingId = ref(null);
const search = ref(props.filters?.search || '');

// Form sesuai Model Pelanggan
const form = useForm({
    nama: '',
    phone: '',
    email: '',
    alamat: '',
    bank: '',
    no_rekening: '',
    nama_kartu: '',
});

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

// Fitur Search (Kirim data ke backend via Inertia)
watch(search, debounce((value) => {
    router.get(route('grosir.master.pelanggan.index'), { search: value }, {
        preserveState: true,
        replace: true
    });
}, 300));

function submit() {
    if (editingId.value) {
        form.put(route('grosir.master.pelanggan.update', editingId.value), {
            onSuccess: () => {
                editingId.value = null;
                form.reset();
                Toast.fire({ icon: 'success', title: 'Data pelanggan diperbarui' });
            }
        });
    } else {
        form.post(route('grosir.master.pelanggan.store'), {
            onSuccess: () => {
                form.reset();
                Toast.fire({ icon: 'success', title: 'Pelanggan berhasil disimpan' });
            }
        });
    }
}

function startEdit(p) {
    editingId.value = p.id;
    form.nama = p.nama;
    form.phone = p.phone;
    form.email = p.email;
    form.alamat = p.alamat;
    form.bank = p.bank;
    form.no_rekening = p.no_rekening;
    form.nama_kartu = p.nama_kartu;
}

function cancelEdit() {
    editingId.value = null;
    form.reset();
}

function handleDelete(id, nama) {
    Swal.fire({
        title: 'Hapus Pelanggan?',
        text: `Data "${nama}" akan dihapus permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('grosir.master.pelanggan.destroy', id), {
                onSuccess: () => Toast.fire({ icon: 'success', title: 'Pelanggan dihapus' })
            });
        }
    });
}
</script>

<template>
    <div class="p-6 grid grid-cols-2 xl:grid-cols-5 gap-6 font-sans">
        <div class="xl:col-span-2 w-full bg-white rounded-xl shadow-sm border border-slate-200 h-fit">
            <div class="px-5 h-12 flex items-center font-bold border-b border-indigo-50 bg-slate-50/50 rounded-t-xl">
                <h2 class="text-indigo-600 uppercase text-xs tracking-widest">
                    {{ editingId ? 'Edit Pelanggan' : 'Input Pelanggan' }}
                </h2>
            </div>
            <div class="p-5">
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Nama
                                Lengkap</label>
                            <input v-model="form.nama" type="text"
                                class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-indigo-500/20 text-sm"
                                placeholder="Nama pelanggan...">
                            <p v-if="form.errors.nama" class="text-red-500 text-[10px] mt-1">{{ form.errors.nama }}</p>
                        </div>
                        <div>
                            <label
                                class="block text-slate-500 text-[10px] font-black uppercase mb-1">WhatsApp/Phone</label>
                            <input v-model="form.phone" type="text"
                                class="w-full px-3 py-2 border rounded-lg outline-none text-sm" placeholder="08...">
                        </div>
                        <div>
                            <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Email</label>
                            <input v-model="form.email" type="email"
                                class="w-full px-3 py-2 border rounded-lg outline-none text-sm"
                                placeholder="mail@example.com">
                        </div>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 grid grid-cols-2 gap-3">
                        <div class="col-span-2 text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Informasi
                            Rekening (Opsional)</div>
                        <div class="col-span-2">
                            <label class="block text-slate-500 text-[10px] font-bold mb-1">Bank</label>
                            <input v-model="form.bank" type="text" class="w-full px-3 py-1.5 border rounded text-sm"
                                placeholder="BCA / Mandiri / BRI">
                        </div>
                        <div>
                            <label class="block text-slate-500 text-[10px] font-bold mb-1">No. Rekening</label>
                            <input v-model="form.no_rekening" type="text"
                                class="w-full px-3 py-1.5 border rounded text-sm" placeholder="12345...">
                        </div>
                        <div>
                            <label class="block text-slate-500 text-[10px] font-bold mb-1">A/N Kartu</label>
                            <input v-model="form.nama_kartu" type="text"
                                class="w-full px-3 py-1.5 border rounded text-sm" placeholder="Nama di buku tabungan">
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-500 text-[10px] font-black uppercase mb-1">Alamat</label>
                        <textarea v-model="form.alamat" rows="2"
                            class="w-full px-3 py-2 border rounded-lg outline-none text-sm"
                            placeholder="Alamat lengkap..."></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button v-if="editingId" @click="cancelEdit" type="button"
                            class="text-slate-400 text-xs font-bold uppercase hover:text-slate-600 transition-colors">Batal</button>
                        <button type="submit" :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg text-xs uppercase tracking-widest transition-all shadow-md shadow-indigo-100">
                            {{ editingId ? 'Update Data' : 'Simpan Pelanggan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="xl:col-span-3 w-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 h-12 flex items-center justify-between border-b border-indigo-50 bg-slate-50/50">
                <h2 class="text-slate-700 uppercase text-xs tracking-widest font-bold">Daftar Pelanggan</h2>
                <div class="relative w-48">
                    <input v-model="search" type="search" placeholder="Cari nama..."
                        class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-full text-xs outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2 text-slate-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-100">
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-wider">Info
                                Pelanggan</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-wider">Rekening
                            </th>
                            <th class="px-5 py-3 text-right text-[10px] font-black uppercase tracking-wider w-20">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="p in pelanggans" :key="p.id" class="hover:bg-indigo-50/20 transition-colors group">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-700 leading-tight">{{ p.nama }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ p.phone }} | {{ p.email || '-' }}
                                </div>
                                <div class="text-[10px] text-slate-400 italic mt-1 truncate max-w-[200px]">{{ p.alamat
                                }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div v-if="p.bank" class="text-xs text-slate-600 font-medium uppercase">{{ p.bank }}
                                </div>
                                <div v-if="p.no_rekening" class="text-[11px] text-slate-400 font-mono">{{ p.no_rekening
                                }}</div>
                                <div v-else class="text-[10px] text-slate-300 italic">No data bank</div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <button @click="startEdit(p)"
                                        class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="handleDelete(p.id, p.nama)"
                                        class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
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