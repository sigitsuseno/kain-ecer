<script setup>
import { Link } from '@inertiajs/vue3';
import Logog3 from '../../assets/svg/g3.svg'
import Dimg from '../../assets/svg/dashboard.svg'
import ImgPjl from '../../assets/svg/sales.svg'
import ImgMst from '../../assets/svg/master.svg'
import ImgPnm from '../../assets/svg/supplier.svg'
import ImgStc from '../../assets/svg/stock.svg'
import ImgLpr from '../../assets/svg/report2.svg'
import ImgStg from '../../assets/svg/setting.svg'
import Imglgt from '../../assets/svg/logout.svg'

const props = defineProps({
    auth: Object,
});

</script>
<template>
    <div class="max-w-50 h-screen overflow-hidden bg-primary text-white relative -top-12 left-0">
        <h1 class="flex items-center flex-nowrap">
            <div class="w-16 h-12 p-2 shrink-0">
                <img :src="Logog3" alt="">
            </div>
            <span class="whitespace-nowrap font-bold ml-2 text-2xl">ERP Lite</span>
        </h1>
        <div class="p-3 mt-4">
            <div class="w-full h-20 rounded-lg bg-amber-50">
                <div class="w-full h-full flex flex-col items-center justify-center">
                    <div
                        class="w-10 h-10 rounded-full bg-blue-900 flex items-center justify-center text-white font-bold text-lg">
                        {{ auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <span class="mt-2 font-semibold text-sm text-gray-700">{{ auth.user.name }}</span>
                </div>
            </div>
        </div>
        <div class="flex flex-col">
            <div class="pt-2">
                <Link href="/ecer"
                    class="w-full flex items-center justify-start flex-nowrap px-4 py-1 hover:bg-amber-50/10">
                    <div class="w-8 h-8 shrink-0 p-0.5"><img :src="Dimg" alt=""></div>
                    <span class="ml-4">Dashboard</span>
                </Link>
            </div>
            <div class="pt-2">
                <Link href="/ecer/master"
                    class="w-full flex items-center justify-start flex-nowrap px-4 py-1 hover:bg-amber-50/10">
                    <div class="w-8 h-8 shrink-0 p-0.5"><img :src="ImgMst" alt=""></div>
                    <span class="ml-4 whitespace-nowrap">Master Input</span>
                </Link>
            </div>
            <div class="pt-2">
                <a href="#" class="w-full flex items-center justify-start flex-nowrap px-4 py-1 hover:bg-amber-50/10">
                    <div class="w-8 h-8 shrink-0 p-0.5"><img :src="ImgPjl" alt=""></div>
                    <span class="ml-4">Penjualan</span>
                </a>
            </div>

            <!-- <div class="pt-2">
                <Link href="/dashboard/penerimaan-barang"
                    class="w-full flex items-center justify-start flex-nowrap px-4 py-1 hover:bg-amber-50/10">
                    <div class="w-8 h-8 shrink-0 p-0.5"><img :src="ImgPnm" alt=""></div>
                    <span class="ml-4">Penerimaan</span>
                </Link>
            </div> -->
            <div class="pt-2">
                <a href="#" class="w-full flex items-center justify-start flex-nowrap px-4 py-1 hover:bg-amber-50/10">
                    <div class="w-8 h-8 shrink-0 p-0.5"><img :src="ImgStc" alt=""></div>
                    <span class="ml-4">Stock</span>
                </a>
            </div>
            <div class="pt-2">
                <a href="#" class="w-full flex items-center justify-start flex-nowrap px-4 py-1 hover:bg-amber-50/10">
                    <div class="w-8 h-8 shrink-0 p-0.5"><img :src="ImgLpr" alt=""></div>
                    <span class="ml-4">Laporan</span>
                </a>
            </div>
            <div class="pt-2">
                <a href="#" class="w-full flex items-center justify-start flex-nowrap px-4 py-1 hover:bg-amber-50/10">
                    <div class="w-8 h-8 shrink-0 p-0.5"><img :src="ImgStg" alt=""></div>
                    <span class="ml-4">Setting</span>
                </a>
            </div>
            <Link :href="route('logout')" method="post" as="button"
                class="w-full flex items-center justify-start flex-nowrap px-4 py-1 hover:bg-amber-50/10 cursor-pointer">
                <div class="w-8 h-8 shrink-0 p-0.5"><img :src="Imglgt" alt=""></div>
                <span class="ml-4">Logout</span>
            </Link>
        </div>
    </div>
</template>





<style lang="scss" scoped></style>