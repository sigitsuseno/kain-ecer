<template>
    <div class="w-full">
        <LinkLaporan />

        <div class="w-full px-6">
            <div class="h-12 flex items-end justify-between gap-1">
                <h2 class="font-bold text-slate-700">Catatan Keuangan Manual</h2>
                <div class="text-xs text-slate-500 italic">* Tekan Save untuk menambah catatan baru</div>
            </div>

            <div class="mt-2 border h-[calc(100vh-210px)] overflow-auto rounded-lg shadow-sm bg-white">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="sticky top-0 z-10 shadow-sm">
                            <th class="px-2 py-2 bg-slate-600 text-white w-12 text-xs">#</th>
                            <th class="px-2 py-2 bg-slate-600 text-white w-40 text-xs">Tanggal</th>
                            <th class="px-2 py-2 bg-slate-600 text-white w-40 text-xs">Jenis</th>
                            <th class="px-2 py-2 bg-slate-600 text-white w-40 text-xs">Ref</th>
                            <th class="px-2 py-2 bg-slate-600 text-white text-xs">Keterangan</th>
                            <th class="px-2 py-2 bg-slate-600 text-white w-44 text-xs text-right">Nominal</th>
                            <th class="px-2 py-2 bg-slate-600 text-white w-24 text-xs text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-blue-50/50">
                            <td class="px-2 py-2 border-b border-blue-100 text-center text-blue-400">
                                <i class='bx bx-plus-circle text-xl'></i>
                            </td>
                            <td class="px-2 py-2 border-b border-blue-100">
                                <input type="date" v-model="form.tanggal"
                                    class="px-2 w-full h-9 rounded border border-slate-300 focus:ring ring-primary/30 outline-none text-sm">
                            </td>
                            <td class="px-2 py-2 border-b border-blue-100">
                                <select v-model="form.jenis"
                                    class="px-2 w-full h-9 rounded border border-slate-300 focus:ring ring-primary/30 outline-none text-sm">
                                    <option value="SALDO">SALDO</option>
                                    <option value="PENGELUARAN">PENGELUARAN</option>
                                    <option value="CATATAN">CATATAN</option>
                                    <option value="PEMBAYARAN">PEMBAYARAN</option>
                                    <option value="LAIN-LAIN">LAIN-LAIN</option>
                                </select>
                            </td>
                            <td class="px-2 py-2 border-b border-blue-100">
                                <select v-model="form.ref"
                                    class="px-2 w-full h-9 rounded border border-slate-300 focus:ring ring-primary/30 outline-none text-sm">
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <option v-for="pl in pelanggan" :value="pl.id" :key="pl.id">{{ pl.nama }}</option>

                                </select>
                            </td>
                            <td class="px-2 py-2 border-b border-blue-100">
                                <input type="text" v-model="form.ket" placeholder="Contoh: Bayar Listrik"
                                    class="px-3 w-full h-9 rounded border border-slate-300 focus:ring ring-primary/30 outline-none text-sm">
                            </td>
                            <td class="px-2 py-2 border-b border-blue-100">
                                <input type="number" v-model="form.nominal"
                                    class="px-3 w-full h-9 rounded border border-slate-300 focus:ring ring-primary/30 outline-none text-sm font-bold text-right">
                            </td>
                            <td class="px-2 py-2 border-b border-blue-100 text-center">
                                <button @click="submit" :disabled="form.processing"
                                    class="w-full h-9 rounded bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold transition disabled:opacity-50">
                                    {{ form.processing ? '...' : 'SAVE' }}
                                </button>
                            </td>
                        </tr>

                        <tr v-for="(note, index) in notes" :key="note.id" class="hover:bg-slate-50 transition">
                            <td class="px-2 py-2 border-b border-primary/10 text-center text-xs text-slate-400">
                                {{ index + 1 }}
                            </td>
                            <td class="px-2 py-2 border-b border-primary/10 text-sm">
                                {{ note.tanggal }}
                            </td>
                            <td class="px-2 py-2 border-b border-primary/10">
                                <span :class="getJenisClass(note.jenis)">
                                    {{ note.jenis }}
                                </span>
                            </td>
                            <td class="px-2 py-2 border-b border-primary/10 text-sm italic text-slate-600">
                                {{ note.pelanggan?.nama || '-' }}
                            </td>
                            <td class="px-2 py-2 border-b border-primary/10 text-sm italic text-slate-600">
                                {{ note.ket || '-' }}
                            </td>
                            <td class="px-2 py-2 border-b border-primary/10 text-sm font-bold text-right">
                                {{ formatNumber(note.nominal) }}
                            </td>
                            <td class="px-2 py-2 border-b border-primary/10 text-center">
                                <button @click="deleteNote(note.id)"
                                    class="p-1 text-red-500 hover:bg-red-50 rounded transition">
                                    <i class='bx bx-trash text-lg'></i>
                                </button>
                            </td>
                        </tr>

                        <tr v-if="notes.length === 0">
                            <td colspan="6" class="p-10 text-center text-slate-400 italic text-sm">
                                Belum ada data catatan untuk hari ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import LinkLaporan from '../../../Components/Eceran/LinkLaporan.vue';

const props = defineProps({
    notes: Array,
    pelanggan: Array
});

// Form state menggunakan Inertia useForm
const form = useForm({
    tanggal: new Date().toISOString().split('T')[0],
    jenis: 'PENGELUARAN',
    ref: '',
    ket: '',
    nominal: 0
});

// Submit data baru
const submit = () => {
    form.post('/ecer/laporan', {
        onSuccess: () => {
            form.reset('ket', 'nominal');
        }
    });
};

// Hapus data
const deleteNote = (id) => {
    if (confirm('Hapus catatan ini?')) {
        // ID sudah dimasukkan ke dalam template literal (URL)
        form.delete(`/ecer/laporan/${id}`, {
            onSuccess: () => {
                // Berhasil dihapus
            }
        });
    }
};

// Helper warna badge jenis
const getJenisClass = (jenis) => {
    const base = "px-2 py-0.5 rounded-full text-[10px] font-bold ";
    if (jenis === 'SALDO') return base + "bg-green-100 text-green-700";
    if (jenis === 'PENGELUARAN') return base + "bg-red-100 text-red-700";
    if (jenis === 'BIAYA') return base + "bg-amber-100 text-amber-700";
    return base + "bg-slate-100 text-slate-700";
}

const formatNumber = (val) => {
    return new Intl.NumberFormat('id-ID').format(val || 0)
}

</script>