<template>
    <div class="w-full h-[calc(100vh-48px)] relative">
        <EcerSettingTab :tap_panel="activeComponent" />
        <div class="w-full h-[calc(100vh-88px)] bg-indigo-100 overflow-hidden">
            <div class="w-full h-16 bg-white flex items-center justify-between px-4">
                <div class="text-2xl flex items-center gap-2 font-bold">
                    <i class='bx bx-spreadsheet'></i> Pengaturan Akun
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button v-if="akunMode !== 'create'" @click="openMode('create')"
                        class="px-3 py-1 bg-button text-white flex items-center justify-center gap-1">
                        <i class='bx bx-plus'></i> Tambah Akun
                    </button>
                </div>
            </div>

            <div class="w-full h-[calc(100vh-152px)] overflow-y-auto p-4">
                <div class="bg-white shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-800 text-white">
                                <th class="px-3 py-2 w-12 text-center">#</th>
                                <th class="px-3 py-2 w-40">Kode Akun</th>
                                <th class="px-3 py-2">Nama Akun</th>
                                <th class="px-3 py-2 w-48">Kategori</th>
                                <th class="px-3 py-2 w-32">Posisi</th>
                                <th class="px-3 py-2 w-32 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="akunMode === 'create'" class="bg-blue-50">
                                <td class="px-3 py-2 text-center">NEW</td>
                                <td class="px-3 py-2">
                                    <input v-model="form.kode_akun" type="text" placeholder="Contoh: 1101"
                                        class="w-full h-8 border border-blue-400 px-2 outline-none focus:ring-1 ring-blue-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input v-model="form.nama_akun" type="text" placeholder="Nama Akun"
                                        class="w-full h-8 border border-blue-400 px-2 outline-none focus:ring-1 ring-blue-500">
                                </td>
                                <td class="px-3 py-2">
                                    <select v-model="form.kategori" class="w-full h-8 border border-blue-400 px-1">
                                        <option value="Aset">Aset</option>
                                        <option value="Kewajiban">Kewajiban</option>
                                        <option value="Ekuitas">Ekuitas</option>
                                        <option value="Pendapatan">Pendapatan</option>
                                        <option value="Beban">Beban</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <select v-model="form.posisi_normal" class="w-full h-8 border border-blue-400 px-1">
                                        <option value="debit">Debit</option>
                                        <option value="kredit">Kredit</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2 flex items-center justify-center gap-1">
                                    <button @click="saveAkun" class="bg-green-600 text-white p-1 rounded"
                                        title="Simpan">
                                        <i class='bx bx-check'></i>
                                    </button>
                                    <button @click="cancelMode" class="bg-red-600 text-white p-1 rounded" title="Batal">
                                        <i class='bx bx-x'></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-for="(ak, idx) in akun" :key="ak.id"
                                :class="[editingId === ak.id ? 'bg-orange-50' : 'hover:bg-gray-50', 'border-b']">

                                <td class="px-3 py-2 text-center">{{ idx + 1 }}</td>

                                <td class="px-3 py-2">
                                    <input v-if="editingId === ak.id" v-model="form.kode_akun" type="text"
                                        class="w-full h-8 border border-orange-400 px-2 outline-none">
                                    <span v-else class="font-mono text-sm">{{ ak.kode_akun }}</span>
                                </td>

                                <td class="px-3 py-2">
                                    <input v-if="editingId === ak.id" v-model="form.nama_akun" type="text"
                                        class="w-full h-8 border border-orange-400 px-2 outline-none">
                                    <span v-else>{{ ak.nama_akun }}</span>
                                </td>

                                <td class="px-3 py-2">
                                    <select v-if="editingId === ak.id" v-model="form.kategori"
                                        class="w-full h-8 border border-orange-400 px-1">
                                        <option value="Aset">Aset</option>
                                        <option value="Kewajiban">Kewajiban</option>
                                        <option value="Ekuitas">Ekuitas</option>
                                        <option value="Pendapatan">Pendapatan</option>
                                        <option value="Beban">Beban</option>
                                    </select>
                                    <span v-else>{{ ak.kategori }}</span>
                                </td>

                                <td class="px-3 py-2">
                                    <select v-if="editingId === ak.id" v-model="form.posisi_normal"
                                        class="w-full h-8 border border-orange-400 px-1">
                                        <option value="debit">Debit</option>
                                        <option value="kredit">Kredit</option>
                                    </select>
                                    <span v-else
                                        :class="ak.posisi_normal === 'debit' ? 'text-blue-600' : 'text-red-600'"
                                        class="capitalize">
                                        {{ ak.posisi_normal }}
                                    </span>
                                </td>

                                <td class="px-3 py-2 text-center">
                                    <div v-if="editingId === ak.id" class="flex items-center justify-center gap-1">
                                        <button @click="saveAkun" class="w-8 h-8 bg-green-600 text-white p-1 rounded">
                                            <i class='bx bx-check'></i>
                                        </button>
                                        <button @click="cancelMode" class="w-8 h-8 bg-red-600 text-white p-1 rounded">
                                            <i class='bx bx-x'></i>
                                        </button>
                                    </div>
                                    <div v-else class="flex items-center justify-center gap-2">
                                        <button @click="editAkun(ak)"
                                            class="h-8 ring px-2 text-blue-600 hover:text-blue-800">
                                            <i class='bx bx-edit-alt'></i> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>

import { ref, computed } from 'vue';
import EcerSettingTab from '../../../Components/EcerSettingTab.vue';

import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage()
const activeComponent = computed(() => page.component)

const props = defineProps({
    akun: Array,
    tap_panel: String,
})

const akunMode = ref(''); // '' atau 'create' atau 'edit'
const editingId = ref(null); // Menyimpan ID akun yang sedang diedit

const form = useForm({
    id: null,
    kode_akun: '',
    nama_akun: '',
    kategori: '',
    posisi_normal: 'debit',
})

const openMode = (mode) => {
    akunMode.value = mode
    editingId.value = null
    form.reset()
}

const editAkun = (ak) => {
    akunMode.value = 'edit'
    editingId.value = ak.id
    // Isi form dengan data yang akan diedit
    form.id = ak.id
    form.kode_akun = ak.kode_akun
    form.nama_akun = ak.nama_akun
    form.kategori = ak.kategori
    form.posisi_normal = ak.posisi_normal
}

const cancelMode = () => {
    akunMode.value = ''
    editingId.value = null
    form.reset()
}

const saveAkun = () => {
    if (akunMode.value === 'create') {
        form.post('/grosir/settings/akun', { onSuccess: () => cancelMode() })
    } else {
        form.put(`/grosir/settings/akun/${form.id}`, { onSuccess: () => cancelMode() })
    }
}
</script>