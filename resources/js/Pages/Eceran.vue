<template>
    <div class=" bg-blue-200 h-[calc(100vh-40px)] flex flex-col overflow-hidden">
        <div class="p-6 overflow-y-auto">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Dashboard POS</h1>
                    <p class="text-slate-500">Selamat datang kembali, {{ $page.props.auth.user.name }}</p>
                </div>
                <div class="flex gap-2">
                    <button class="bg-white border px-4 py-2 rounded-lg text-sm font-medium">Download Laporan</button>
                    <Link href="/ecer/penjualan"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                        Transaksi
                        Baru</Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="stat in stats" :key="stat.label"
                    class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <p class="text-sm text-slate-500 mb-1">{{ stat.label }}</p>
                    <h3 class="text-2xl font-bold text-slate-800">{{ stat.value }}</h3>
                    <p :class="stat.trendUp ? 'text-green-600' : 'text-red-600'" class="text-xs mt-2 font-medium">
                        {{ stat.trend }} <span class="text-slate-400 font-normal ml-1">vs bulan lalu</span>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <h3 class="font-bold text-slate-700 mb-4">Grafik Penjualan 7 Hari Terakhir</h3>
                    <div class="h-64 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400">

                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <h3 class="font-bold text-slate-700 mb-4">Top Varian Kain</h3>
                    <div class="space-y-4">
                        <div v-for="item in topProducts" :key="item.name" class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-slate-800">{{ item.name }}</p>
                                <p class="text-xs text-slate-500">{{ item.color }}</p>
                            </div>
                            <span class="text-sm font-bold text-blue-600">{{ item.total }} Yd</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const stats = [
    { label: 'Omzet Hari Ini', value: 'Rp 12.450.000', trend: '+12%', trendUp: true },
    { label: 'Pesanan Baru', value: '48', trend: '+5%', trendUp: true },
    { label: 'Total Piutang', value: 'Rp 8.200.000', trend: '-2%', trendUp: false },
    { label: 'Stok Kritis', value: '12 Varian', trend: 'Perlu Cek', trendUp: false },
]

const topProducts = [
    { name: 'Katun Rayon Premium', color: 'Midnight Blue', total: '150.5' },
    { name: 'Satin Silk Luxury', color: 'Rose Gold', total: '92.0' },
    { name: 'Linen Crinkle', color: 'Sage Green', total: '85.2' },
]
</script>