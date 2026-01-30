<template>
    <div class="p-6 bg-white h-[calc(100vh-40px)] overflow-hidden">
        <div class="flex items-center justify-between text-slate-800">
            <h1 class="text-xl font-bold">Transfer Produk dari Grosir</h1>
            <Link :href="route('ecer.stok.index')" class="text-sm text-gray-500 hover:underline text-slate-800">
                Kembali
            </Link>
        </div>
        <div class="h-[calc(100vh-120px)] flex flex-1 overflow-hidden pt-4 gap-4">
            <div class="w-1/2 bg-white rounded-xl shadow-sm flex flex-col overflow-hidden border">
                <div class="p-4 border-b bg-slate-50">
                    <input v-model="search" type="text" placeholder="Cari nama produk atau nomor..."
                        class="w-full h-12 border-2 border-slate-200 rounded-xl px-4 focus:border-blue-500 outline-none text-lg" />
                </div>

                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                    <div v-for="item in filteredProduk" :key="item.id"
                        class="border rounded-xl overflow-hidden shadow-sm">
                        <div class="p-4 bg-slate-50 border-b flex justify-between items-center">
                            <div>
                                <div class="text-[10px] font-bold text-blue-600 font-mono">{{ item.nomor }}</div>
                                <div class="font-bold text-slate-800">{{ item.varian?.produk?.nama }}</div>
                                <div class="text-xs text-slate-500 italic">{{ item.varian?.nama }}</div>
                            </div>
                        </div>

                        <div class="p-2 flex flex-wrap gap-2 bg-white">
                            <div v-for="det in item.details" :key="det.id" @click="toggleRoll(item, det)"
                                :class="['w-[23%]  p-3 border rounded-lg cursor-pointer transition flex justify-center items-center',
                                    isRollSelected(det.id) ? 'bg-blue-600 border-blue-600 text-white' : 'hover:bg-blue-50 text-slate-800']">
                                <!-- <span class="text-xs font-mono">ID:{{ det.id }}</span> -->
                                <span class="font-bold text-lg">{{ det.qty }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-1/2 flex flex-col gap-4">
                <div
                    class="h-[calc(100vh-200px)] bg-white rounded-xl shadow-sm p-6 border flex-1 overflow-hidden flex flex-col">
                    <h2 class="text-slate-400 font-bold text-xs uppercase mb-4">Ringkasan Transfer (Roll Terpilih)</h2>

                    <div v-if="form.selected_rolls.length === 0"
                        class="flex-1 flex flex-col items-center justify-center text-slate-300 border-2 border-dashed rounded-xl">
                        <i class="bx bx-package text-6xl mb-2"></i>
                        <p>Belum ada roll yang dipilih</p>
                    </div>

                    <div v-else class=" flex-1 overflow-y-auto space-y-3 pr-2">
                        <div v-for="stok in form.selected_rolls" :key="stok.stok_id" class="mb-4 border-b pb-2">
                            <div class="font-bold text-blue-700 text-sm">{{ stok.product_name }} - {{ stok.warna }}
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <div v-for="roll in stok.details" :key="roll.id"
                                    class="bg-slate-200 px-2 py-1 rounded text-xs flex items-center gap-2">
                                    <span>{{ roll.qty }}</span>
                                    <button @click="toggleRoll(stok, roll)" class="text-red-500">×</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t" v-if="form.selected_rolls.length > 0">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-slate-600 uppercase text-xs">Total Qty</span>
                            <span class="text-3xl font-black text-slate-800">{{ totalSelectedQty }}</span>
                        </div>
                    </div>
                </div>

                <button @click="submit" :disabled="form.selected_rolls.length === 0 || form.processing"
                    :class="['w-full py-6 rounded-xl font-black text-xl shadow-lg transition transform active:scale-95',
                        form.selected_rolls.length > 0 ? 'bg-orange-500 text-white hover:bg-orange-600' : 'bg-slate-300 text-slate-400 cursor-not-allowed']">
                    {{ form.processing ? 'MEMPROSES...' : 'KONFIRMASI TERIMA ' + form.selected_rolls.length + ' ROLL' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
    produkGrosir: Array
})

const search = ref('')

// Form diubah untuk menampung array objek roll yang dipilih
const form = useForm({
    selected_rolls: [],
})

const filteredProduk = computed(() => {
    if (!props.produkGrosir) return []

    return props.produkGrosir.filter(i => {
        const namaProduk = i.varian?.produk?.nama?.toLowerCase() || ''
        const nomorStok = i.nomor?.toLowerCase() || ''
        const keyword = search.value.toLowerCase()

        return namaProduk.includes(keyword) || nomorStok.includes(keyword)
    })
})

const toggleRoll = (item, det) => {
    // 1. Cari apakah stok_id ini sudah ada di daftar transfer
    let stokEntry = form.selected_rolls.find(s => s.stok_id === item.id)

    if (!stokEntry) {
        // Jika stok belum ada, buat entry baru dengan array details kosong
        stokEntry = {
            stok_id: item.id,
            product_name: item.varian?.produk?.nama,
            warna: item.varian?.nama,
            details: []
        }
        form.selected_rolls.push(stokEntry)
    }

    // 2. Cek apakah roll (det.id) sudah ada di dalam stok tersebut
    const detailIndex = stokEntry.details.findIndex(d => d.id === det.id)

    if (detailIndex > -1) {
        // Jika sudah ada, hapus roll tersebut
        stokEntry.details.splice(detailIndex, 1)

        // Jika setelah dihapus ternyata details kosong, hapus stok-nya sekalian
        if (stokEntry.details.length === 0) {
            const stokIndex = form.selected_rolls.findIndex(s => s.stok_id === item.id)
            form.selected_rolls.splice(stokIndex, 1)
        }
    } else {
        // Jika belum ada, masukkan roll ke dalam array details milik stok tersebut
        stokEntry.details.push({
            id: det.id,
            qty: det.qty
        })
    }
}

const isRollSelected = (id) => {
    return form.selected_rolls.some(stok =>
        stok.details.some(det => det.id === id)
    )
}

const removeRoll = (id) => {
    const index = form.selected_rolls.findIndex(r => r.id === id)
    if (index > -1) {
        form.selected_rolls.splice(index, 1)
        if (form.selected_rolls.length === 0) form.stok_id = '';
    }
}
const totalSelectedQty = computed(() => {
    return form.selected_rolls.reduce((total, stok) => {
        return total + stok.details.reduce((subtotal, det) => subtotal + Number(det.qty), 0)
    }, 0)
})
const totalRollCount = computed(() => {
    return form.selected_rolls.reduce((total, stok) => total + stok.details.length, 0)
})

const submit = () => {
    form.post(route('ecer.stok.store-transfer'))
}
</script>