<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100 grid grid-cols-[1fr_300px]">

        <div class="w-full h-full p-6 overflow-y-auto">
            <table class="w-full border-collapse bg-white shadow-sm rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase text-xs font-bold">
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Nama Produk / Slug</th>
                        <th class="p-3 text-right">Aplikasi Baru</th>
                        <th class="p-3 text-right">Aplikasi Lama</th>
                        <th class="p-3 text-right">Selisih</th>
                        <th class="p-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr v-for="item in results" :key="item.sku_slug" :class="!item.found ? 'bg-yellow-50' : ''">
                        <td class="p-3">
                            <div class="font-bold">{{ item.id }}</div>

                        </td>
                        <td class="p-3">
                            <div class="font-bold">{{ item.nama }}</div>
                            <div class="text-xs text-slate-500">{{ item.sku_slug }}</div>
                        </td>
                        <td class="p-3 text-right font-mono text-blue-600 font-bold">
                            {{ item.stok_baru.toLocaleString() }} Yd
                        </td>
                        <td class="p-3 text-right font-mono text-slate-600">
                            {{ item.stok_lama.toLocaleString() }} Yd
                        </td>
                        <td class="p-3 text-right font-mono"
                            :class="item.selisih !== 0 ? 'text-red-500' : 'text-slate-400'">
                            {{ item.selisih }}
                        </td>
                        <td class="p-3 text-center">
                            <span v-if="item.is_sync"
                                class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[10px] font-bold">
                                SINKRON
                            </span>
                            <span v-else-if="!item.found"
                                class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-[10px] font-bold">
                                TIDAK DITEMUKAN DI LAMA
                            </span>
                            <span v-else class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-[10px] font-bold">
                                MISMATCH
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
<script setup>

const props = defineProps({
    results: Object,
})
</script>