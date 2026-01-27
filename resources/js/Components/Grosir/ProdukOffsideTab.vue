<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

const props = defineProps({
    product: Object,    // Untuk Edit (jika ada)
    categories: Array,  // List Kategori dari Backend
    varian: Array,      // List Produk/Varian yang sudah ada untuk Auto-complete
    satuan: Array,      // List Satuan (Yard, Meter, Roll, dll)
})

const emit = defineEmits(['close'])

const showDropdown = ref(false)
const showVarianDropdown = ref(false)

// --- FORM PEMBELIAN ---
const form = useForm({
    id: null,
    nama_produk: '',
    kategori_id: '',
    kode_warna: '',
    satuan_id: 1,
    tipe: 'polos',
    lebar_kain: '',
    deskripsi: '',
})

// --- LOGIKA DROPDOWN NAMA PRODUK (SEARCH) ---
const filteredCategories = computed(() => {
    const list = props.categories || []
    const search = form.nama_produk?.toLowerCase().trim()

    // Jika kolom kosong, tampilkan semua kategori sebagai saran awal
    if (!search) return list

    return list.filter(c =>
        c.nama_kategori.toLowerCase().includes(search)
    )
})

// Menangani pemilihan kategori/nama produk
function selectCategory(cat) {
    form.nama_produk = cat.nama_kategori
    form.kategori_id = cat.id
    showDropdown.value = false
}

// --- LOGIKA DROPDOWN VARIAN (AUTO-COMPLETE) ---
const uniqueVarians = computed(() => {
    // Ambil data produk varian yang sudah pernah diinput sebelumnya
    const rawData = props.varian?.data || props.varian || []
    if (rawData.length === 0) return []

    const search = form.kode_warna?.toLowerCase()?.trim()

    // Ambil daftar nama varian (kode warna/seri) yang unik agar tidak double di list
    const varianNames = [...new Set(rawData.map(p => p.nama).filter(Boolean))]

    if (!search) return varianNames
    return varianNames.filter(name => name.toLowerCase().includes(search))
})

function selectVarian(name) {
    form.kode_warna = name
    showVarianDropdown.value = false
}

// Helper untuk menutup dropdown dengan delay agar @mousedown tereksekusi
const closeDropdownWithDelay = () => setTimeout(() => { showDropdown.value = false }, 200)
const closeVarianWithDelay = () => setTimeout(() => { showVarianDropdown.value = false }, 200)

// --- SUBMIT ---
function submit() {
    // Jika ID ada maka Update, jika tidak ada maka Store (Insert)
    const routeName = form.id
        ? route('grosir.master.products.update', form.id)
        : route('grosir.master.products.store')

    const method = form.id ? 'put' : 'post'

    form[method](routeName, {
        onSuccess: () => {
            form.reset()
            emit('close')
        }
    })
}

// Watcher untuk mengisi form jika dalam mode EDIT
watch(() => props.product, (p) => {
    if (p) {
        form.id = p.id
        form.nama_produk = p.produk?.nama ?? ''
        form.kategori_id = p.produk?.kategori_id ?? ''
        form.kode_warna = p.nama ?? ''
        form.tipe = p.tipe ?? 'polos'
        form.lebar_kain = p.lebar ?? ''
        form.satuan_id = p.produk?.satuan_id ?? 1
        form.deskripsi = p.produk?.deskripsi ?? ''
    } else {
        form.reset()
    }
}, { immediate: true })
</script>

<template>

    <div class="px-6 py-5 overflow-y-auto flex-grow bg-white">
        <form @submit.prevent="submit" class="space-y-5">

            <div class="relative">
                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Nama Produk
                    (Kategori)</label>
                <input v-model="form.nama_produk" @focus="showDropdown = true" @blur="closeDropdownWithDelay"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 h-10 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
                    placeholder="Cari atau ketik nama kain..." />

                <div v-if="showDropdown && filteredCategories.length > 0"
                    class="absolute z-[100] left-0 right-0 mt-1 max-h-52 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl">
                    <div v-for="cat in filteredCategories" :key="cat.id" @mousedown="selectCategory(cat)"
                        class="cursor-pointer px-4 py-3 text-sm hover:bg-indigo-50 hover:text-indigo-700 border-b border-slate-50 last:border-0 flex items-center gap-2">
                        <i class='bx bx-purchase-tag-alt text-slate-400'></i>
                        {{ cat.nama_kategori }}
                    </div>
                </div>
            </div>

            <div class="relative">
                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">
                    {{ form.tipe === 'polos' ? 'Kode Warna' : 'Kode Seri' }}
                </label>
                <input v-model="form.kode_warna" @focus="showVarianDropdown = true" @blur="closeVarianWithDelay"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 h-10 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                    placeholder="Contoh: Merah Cabe, Navy, atau Seri A" />

                <div v-if="showVarianDropdown && uniqueVarians.length > 0"
                    class="absolute z-[100] left-0 right-0 mt-1 max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl">
                    <div v-for="(varianName, index) in uniqueVarians" :key="index" @mousedown="selectVarian(varianName)"
                        class="cursor-pointer px-4 py-3 text-sm hover:bg-emerald-50 hover:text-emerald-700 border-b border-slate-50 last:border-0">
                        {{ varianName }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col">
                    <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Tipe</label>
                    <select v-model="form.tipe"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-2 h-10 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="polos">Polos</option>
                        <option value="motif">Motif</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Satuan</label>
                    <select v-model="form.satuan_id"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-2 h-10 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option v-for="unit in satuan" :key="unit.id" :value="unit.id">
                            {{ unit.nama_satuan.toUpperCase() }}
                        </option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Lebar Kain
                    (Opsional)</label>
                <input v-model="form.lebar_kain"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 h-10 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="Misal: 1.50 atau 150cm" />
            </div>

            <div>
                <label class="text-xs font-bold uppercase text-slate-500 tracking-wider">Catatan /
                    Deskripsi</label>
                <textarea v-model="form.deskripsi" rows="2"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="Tambahkan catatan jika perlu..."></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t mt-4">
                <button type="button" @click="emit('close')"
                    class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-all">
                    Batal
                </button>
                <button type="submit" :disabled="form.processing"
                    class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 disabled:bg-slate-300 transition-all flex items-center gap-2">
                    <i v-if="form.processing" class='bx bx-loader-alt animate-spin'></i>
                    {{ form.id ? 'Perbarui Data' : 'Simpan Produk' }}
                </button>
            </div>
        </form>
    </div>


</template>