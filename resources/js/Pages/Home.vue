<template>
    <div class="w-full h-full flex items-center justify-center">
        <div class="w-full max-w-md">
            <div class="w-full bg-white py-6 rounded-2xl shadow shadow-primary/20">
                <p class="text-center text-2xl mb-6">
                    <span class="font-bold text-4xl/10 uppercase">Selamat Datang </span><br />Di Aplikasi Penjualan Kain
                </p>

                <div class="grid grid-cols-1 gap-6 px-6">
                    <Link v-if="!auth.user" href="/auth/login-page"
                        class="w-full aspect-video flex items-center justify-center font-black bg-linear-to-br from-green-900 via-amber-500 to-purple-500 rounded-xl text-white">
                        Silahkan Login
                    </Link>

                    <Link v-else :href="dashboardRoute"
                        class="w-full aspect-video flex items-center justify-center font-black bg-linear-to-br from-blue-600 to-indigo-700 rounded-xl text-white">
                        Masuk ke Dashboard ({{ auth.user.role }})
                    </Link>
                </div>
            </div>
            <p class="text-center text-gray-800 text-xs fixed bottom-12 left-1/2 -translate-x-1/2">
                &copy;2026 ERP-LITE. All rights reserved.
            </p>
        </div>
    </div>
</template>

<script setup>
import Layout from '../Layouts/Layout.vue';
import { Link, usePage } from '@inertiajs/vue3'; // Import Link dan usePage
import { computed } from 'vue';

defineOptions({ layout: Layout })

const page = usePage();
const auth = computed(() => page.props.auth);

// Logika penentuan URL berdasarkan role Spatie
const dashboardRoute = computed(() => {
    const role = auth.value.user?.role;

    if (role === 'owner') return '/dashboard';
    if (role === 'admin-grosir') return '/grosir';
    if (role === 'admin-ecer') return '/ecer';

    return '/dashboard'; // default fallback
});
</script>