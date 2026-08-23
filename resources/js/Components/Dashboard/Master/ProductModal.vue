<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'



const props = defineProps({
    mode: String,
    product: Object,
    categories: Array,
    gudangs: Array,
    allProducts: Array,
})

const emit = defineEmits(['close'])

const isView = computed(() => props.mode === 'view')
const isEdit = computed(() => props.mode === 'edit')

// State untuk UI dropdown
const showDropdown = ref(false)

const filteredCategories = computed(() => {
    const s = form.nama_produk?.toLowerCase()?.trim()
    if (!s || isEdit.value) return []

    return (props.categories ?? [])
        .filter(c => typeof c?.nama_kategori === 'string')
        .filter(c =>
            c.nama_kategori.toLowerCase().includes(s) &&
            c.nama_kategori.toLowerCase() !== s
        )
})

const warnaDariNamaproduk = computed(() => {
    // 1. Pastikan nama_produk sudah diisi dan allProducts tersedia
    if (!form.nama_produk || !props.allProducts) return []

    const namaCari = form.nama_produk.toLowerCase().trim()

    // 2. Ambil data dari props.allProducts (handle jika itu pagination atau array biasa)
    const listSemua = props.allProducts?.data || props.allProducts || []

    return listSemua
        .filter(p => {
            // Hanya ambil produk yang namanya sama
            const isSameName = p.nama_produk?.toLowerCase().trim() === namaCari

            // JIKA SEDANG EDIT: Jangan masukkan warna dari produk itu sendiri ke daftar duplikat
            if (isEdit.value && p.id === props.product?.id) return false

            return isSameName
        })
        .map(p => p.kode_warna ?? p.nama ?? p.varian?.[0]?.nama ?? '') // schema baru: varian.nama, fallback lama kode_warna
})

const normalize = (v) =>
    v?.toString().toLowerCase().trim()

const isDuplicate = computed(() => {
    if (!form.kode_warna || !warnaDariNamaproduk.value.length) return false

    return warnaDariNamaproduk.value
        .map(normalize)
        .includes(normalize(form.kode_warna))
})



const isColorValid = computed(() => {
    return form.nama_produk.length > 0 &&
        form.kode_warna.length > 0 &&
        !isDuplicate.value
})

const form = useForm({
    nama_produk: '',
    kode_warna: '',
    satuan: 'yard',
    tipe: 'polos',
    lebar_kain: '',
    deskripsi: '',
})

// 3. Fungsi pilih kategori yang diperbaiki
function selectCategory(name) {
    form.nama_produk = name
    showDropdown.value = false
}

// Watcher untuk mengisi data saat Edit atau Reset saat Create
watch(
    () => props.product,
    (p) => {
        if (p) {
            form.nama_produk = p.nama_produk ?? p.nama ?? ''
            form.kode_warna = p.kode_warna ?? p.nama ?? p.varian?.[0]?.nama ?? ''
            form.tipe = p.tipe
            form.satuan = p.satuan
            form.lebar_kain = p.lebar_kain
            form.deskripsi = p.deskripsi
        } else {
            form.reset()
        }
    },
    { immediate: true }
)

const labelKeterangan = computed(() => {
    if (form.tipe === 'polos') {
        return 'Kode Warna';
    } else if (form.tipe === 'motif') {
        return 'Kode Seri';
    } else {
        return 'Pilih Tipe Dahulu';
    }
});

function submit() {
    const routeName = isEdit.value
        ? route('dashboard.master.products.update', props.product.id)
        : route('dashboard.master.products.store')

    const method = isEdit.value ? 'put' : 'post'

    form[method](routeName, {
        onSuccess: () => emit('close'),
    })
}
</script>

<template>
    <!-- <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"> -->
    <div class="absolute top-0 right-0 w-85 xl:w-96 h-[calc(100vh-90px)] bg-slate-200 p-4 shadow">
        <div class="w-full h-full rounded-2xl bg-white shadow-xl">

            <!-- HEADER -->
            <div class="flex items-center justify-between border-b px-6 py-4">
                <h2 class="text-base font-semibold text-slate-800">
                    {{ mode === 'create' ? 'Tambah' : mode === 'edit' ? 'Edit' : 'Detail' }} Produk
                </h2>
                <button @click="emit('close')" class="text-slate-400 hover:text-slate-600">
                    ✕
                </button>
            </div>

            <!-- BODY -->
            <div class="px-6 py-5">

                <!-- VIEW MODE -->
                <div v-if="isView" class="grid grid-cols-1 text-sm">
                    <div>
                        <p class="text-slate-500">Nama Produk</p>
                        <p class="font-medium">{{ product.nama_produk ?? product.nama ?? '' }}</p>
                    </div>

                    <div>
                        <p class="text-slate-500">{{ product.tipe === 'polos' ? 'Kode Warna' : 'Kode Seri' }}</p>
                        <p class="font-medium">{{ product.kode_warna ?? product.nama ?? '' }}</p>
                    </div>

                    <div>
                        <p class="text-slate-500">Kategori</p>
                        <p class="font-medium">
                            {{ product.kategori?.nama_kategori ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-slate-500">Satuan</p>
                        <p class="font-medium uppercase">{{ product.satuan }}</p>
                    </div>

                    <div>
                        <p class="text-slate-500">Gudang</p>
                        <p class="font-medium">{{ product.gudang?.nama ?? '-' }}</p>
                    </div>

                    <div class="col-span-2">
                        <p class="text-slate-500">Deskripsi</p>
                        <p class="font-medium">{{ product.deskripsi ?? '-' }}</p>
                    </div>
                </div>

                <!-- FORM MODE -->
                <form v-else @submit.prevent="submit" class="space-y-3">

                    <!-- Nama Produk -->
                    <div class="relative">
                        <label class="text-sm font-medium">Nama Produk</label>
                        <input v-model="form.nama_produk" @focus="showDropdown = true"
                            class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="Ketik nama kain..." />

                        <div v-if="showDropdown && filteredCategories.length > 0"
                            class="absolute z-10 mt-1 max-h-40 w-full overflow-y-auto rounded-lg border bg-white shadow-lg">
                            <div v-for="cat in filteredCategories" :key="cat.id"
                                @click="selectCategory(cat.nama_kategori)"
                                class="cursor-pointer px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-600">
                                {{ cat.nama_kategori }}
                            </div>
                        </div>
                    </div>
                    <!-- Kode Warna -->
                    <div>
                        <label class="text-sm font-medium">{{ labelKeterangan }}</label>
                        <div class="relative flex items-center">
                            <input v-model="form.kode_warna"
                                class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm pr-10"
                                :class="{ 'border-red-500 bg-red-50': isDuplicate }"
                                placeholder="contoh: Biru Dongker" />

                            <span v-if="isColorValid" class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </span>

                            <span v-if="isDuplicate" class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>

                        <p v-if="isDuplicate" class="mt-1 text-xs text-red-500 font-medium">
                            Warna ini sudah ada di produk "{{ form.nama_produk }}"!
                        </p>
                    </div>


                    <div class="grid grid-cols-2 gap-4">
                        <!-- tipe -->
                        <div>
                            <label class="text-sm font-medium">Tipe Kain</label>
                            <select v-model="form.tipe"
                                class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm">
                                <option value="polos">Polos</option>
                                <option value="motif">Motif</option>
                            </select>
                        </div>
                        <!-- Satuan -->
                        <div>
                            <label class="text-sm font-medium">Satuan</label>
                            <select v-model="form.satuan"
                                class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm">
                                <option value="yard">Yard</option>
                                <option value="meter">Meter</option>
                                <option value="kg">Kg</option>
                            </select>
                        </div>

                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium">Lebar Kain</label>
                            <input v-model="form.lebar_kain"
                                class="mt-1 w-full rounded-sm border border-black/30 px-3 h-8 text-sm"
                                placeholder="Kosongkan bila tidak ada" />
                            <p v-if="form.errors.lebar_kain" class="text-xs text-red-500">
                                {{ form.errors.lebar_kain }}
                            </p>
                        </div>
                    </div>
                    <!-- Deskripsi -->
                    <div class="col-span-1">
                        <label class="text-sm font-medium ">Deskripsi</label>
                        <textarea v-model="form.deskripsi"
                            class="mt-1 w-full rounded-sm border border-black/30 px-3 text-sm" rows="3" />
                    </div>

                    <!-- ACTION -->
                    <div class="col-span-2 flex justify-end gap-2 pt-4">
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
