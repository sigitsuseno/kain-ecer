<template>
    <div class="w-full h-full flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="w-full bg-white py-6 rounded-2xl shadow shadow-primary/20">
                <p class="text-center text-2xl mb-6">
                    <span class="font-bold text-4xl/10 uppercase">Selamat Datang </span><br />di Aplikasi ERP-LITE
                </p>
                <div class="grid grid-cols-1 gap-6 px-6">
                    <a href="/auth/login-page"
                        class="w-full aspect-video flex items-center justify-center  font-black bg-linear-to-br from-green-900 via-amber-500 to-purple-500 rounded-xl">
                        Silahkan Login
                    </a>
                </div>
            </div>
            <p class="text-center text-gray-800 text-xs fixed bottom-12 left-1/2 -translate-x-1/2">
                &copy;2026 ERP-LITE. All rights
                reserved.
            </p>
        </div>
    </div>
</template>

<script setup>
import Layout from '../Layouts/Layout.vue';

defineOptions({ layout: Layout })
</script>

<style lang="scss" scoped></style>