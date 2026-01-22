<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

const props = defineProps({
    mode: String,
    product: Object,
    categories: Array,
    gudangs: Array,
    allProducts: Array, // Pastikan ini berisi list produk lengkap dengan kode_warna-nya
    satuan: Array,
})

const emit = defineEmits(['close'])

const isView = computed(() => props.mode === 'view')
const isEdit = computed(() => props.mode === 'edit')

const showDropdown = ref(false)
const showVarianDropdown = ref(false)

// --- FORM ---
const form = useForm({
    id: null,
    nama_produk: '',
    kategori_id: '',
    kode_warna: '',
    satuan_id: '',
    tipe: 'polos',
    lebar_kain: '',
    deskripsi: '',
})

// --- LOGIKA DUPLIKAT (Nama Produk + Varian) ---
const isDuplicate = computed(() => {
    const currentNama = form.nama_produk?.toLowerCase().trim()
    const currentVarian = form.kode_warna?.toLowerCase().trim()

    // Jangan validasi jika input masih kosong
    if (!currentNama || !currentVarian) return false

    const listSemua = props.allProducts?.data || props.allProducts || []

    return listSemua.some(p => {
        // Jika sedang EDIT, abaikan produk yang sedang dibuka
        if (isEdit.value && p.id === props.product?.id) return false

        return p.nama_produk?.toLowerCase().trim() === currentNama &&
            p.kode_warna?.toLowerCase().trim() === currentVarian
    })
})

// --- LOGIKA DROPDOWN NAMA PRODUK ---
const filteredCategories = computed(() => {
    const s = form.nama_produk?.toLowerCase()?.trim()
    if (!s || isEdit.value) return []
    return (props.categories ?? []).filter(c =>
        c.nama_kategori.toLowerCase().includes(s) &&
        c.nama_kategori.toLowerCase() !== s
    )
})

function selectCategory(name) {
    form.nama_produk = name
    showDropdown.value = false
}

// --- LOGIKA DROPDOWN VARIAN ---
const uniqueVarians = computed(() => {
    const s = form.kode_warna?.toLowerCase()?.trim()
    if (!s || isEdit.value) return []

    const listSemua = props.allProducts?.data || props.allProducts || []
    const varianNames = listSemua.map(p => p.kode_warna).filter(Boolean)
    const uniqueList = [...new Set(varianNames)]

    return uniqueList.filter(name =>
        name.toLowerCase().includes(s) && name.toLowerCase() !== s
    )
})

function selectVarian(name) {
    form.kode_warna = name
    showVarianDropdown.value = false
}

function closeVarianWithDelay() {
    setTimeout(() => { showVarianDropdown.value = false }, 200)
}

// --- WATCHER ---
watch(() => props.product, (p) => {
    if (p) {
        form.id = p.id // Ini ID ProdukVarian
        // Ambil data dari relasi p.produk
        form.nama_produk = p.produk?.nama_produk ?? ''
        form.kategori_id = p.produk?.kategori_id ?? ''
        form.satuan_id = p.produk?.satuan_id ?? ''
        form.deskripsi = p.produk?.deskripsi ?? ''

        // Ambil data dari varian itu sendiri
        form.kode_warna = p.nama_varian ?? ''
        form.tipe = p.tipe ?? 'polos'
        form.lebar_kain = p.lebar ?? ''
    } else {
        form.reset()

        // Cari ID untuk satuan 'Yard' secara dinamis
        const defaultUnit = props.satuan.find(
            (u) => u.nama_satuan.toLowerCase() === 'yard'
        )

        if (defaultUnit) {
            form.satuan_id = defaultUnit.id
        }
    }
}, { immediate: true })

const labelKeterangan = computed(() => {
    return form.tipe === 'polos' ? 'Kode Warna' : 'Kode Seri'
})

function submit() {
    const routeName = isEdit.value
        ? route('grosir.master.products.update', props.product.id)
        : route('grosir.master.products.store')

    const method = isEdit.value ? 'put' : 'post'
    form[method](routeName, { onSuccess: () => emit('close') })
}
</script>

<template>
    <div class="absolute top-0 right-0 w-85 xl:w-96 h-[calc(100vh-90px)] bg-slate-200 p-4 shadow">
        <div class="w-full h-full rounded-2xl bg-white shadow-xl overflow-hidden flex flex-col">

            <div class="flex items-center justify-between border-b px-6 py-4 shrink-0">
                <h2 class="text-base font-semibold text-slate-800">
                    {{ mode === 'create' ? 'Tambah' : mode === 'edit' ? 'Edit' : 'Detail' }} Produk
                </h2>
                <button @click="emit('close')" class="text-slate-400 hover:text-slate-600">
                    ✕
                </button>
            </div>

            <div class="px-6 py-5 overflow-y-auto flex-grow">
                <div v-if="isView" class="grid grid-cols-1 gap-4 text-sm">
                    <div>
                        <p class="text-slate-500">Nama Produk</p>
                        <p class="font-medium">{{ product.produk.nama_produk }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Varian</p>
                        <p class="font-medium">{{ product.nama_varian }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Kategori</p>
                        <p class="font-medium">{{ product.produk.kategori?.nama_kategori ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500">Satuan</p>
                        <p class="font-medium uppercase">{{ product.produk.satuan.nama_satuan }}</p>
                    </div>
                    <div class="col-span-1">
                        <p class="text-slate-500">Deskripsi</p>
                        <p class="font-medium">{{ product.produk.deskripsi ?? '-' }}</p>
                    </div>
                </div>

                <form v-else @submit.prevent="submit" class="space-y-4">
                    <div class="relative">
                        <label class="text-sm font-medium">Nama Produk</label>
                        <input v-model="form.nama_produk" @focus="showDropdown = true"
                            class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="Ketik nama kain..." />

                        <div v-if="showDropdown && filteredCategories.length > 0"
                            class="absolute z-30 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border bg-white shadow-lg">
                            <div v-for="cat in filteredCategories" :key="cat.id"
                                @click="selectCategory(cat.nama_kategori)"
                                class="cursor-pointer px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-600 border-b last:border-0">
                                {{ cat.nama_kategori }}
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <label class="text-sm font-medium">{{ labelKeterangan }}</label>
                        <input v-model="form.kode_warna" @focus="showVarianDropdown = true" @blur="closeVarianWithDelay"
                            class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                            :class="{ 'border-red-500 bg-red-50': isDuplicate }"
                            placeholder="Ketik warna atau seri..." />

                        <div v-if="showVarianDropdown && uniqueVarians.length > 0"
                            class="absolute z-20 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border bg-white shadow-xl">
                            <div v-for="(varianName, index) in uniqueVarians" :key="index"
                                @click="selectVarian(varianName)"
                                class="cursor-pointer px-4 py-2 text-sm hover:bg-indigo-50 hover:text-indigo-600 border-b last:border-0">
                                {{ varianName }}
                            </div>
                        </div>

                        <p v-if="isDuplicate" class="mt-1 text-xs text-red-500 font-medium">
                            Warna/Seri ini sudah ada di produk "{{ form.nama_produk }}"!
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium">Tipe Kain</label>
                            <select v-model="form.tipe"
                                class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm">
                                <option value="polos">Polos</option>
                                <option value="motif">Motif</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium">Satuan</label>
                            <select v-model="form.satuan_id"
                                class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm focus:ring-2 focus:ring-blue-500">
                                <option v-if="satuan.length === 0" value="">Memuat satuan...</option>
                                <option v-for="unit in satuan" :key="unit.id" :value="unit.id">
                                    {{ unit.nama_satuan }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium">Lebar Kain</label>
                        <input v-model="form.lebar_kain"
                            class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm"
                            placeholder="Kosongkan bila tidak ada" />
                        <p v-if="form.errors.lebar_kain" class="text-xs text-red-500">{{ form.errors.lebar_kain }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium">Deskripsi</label>
                        <textarea v-model="form.deskripsi"
                            class="mt-1 w-full rounded-sm border border-black/30 px-3 py-2 text-sm" rows="3" />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" @click="emit('close')"
                            class="rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-100">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing || isDuplicate"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>