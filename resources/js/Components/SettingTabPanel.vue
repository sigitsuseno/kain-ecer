<template>
    <div class="w-full h-10 bg-amber-200 flex items-end justify-start gap-1 px-4">
        <Link href="/grosir/settings"
            :class="[tap_panel === '/ecer/Settings/Settings' ? 'bg-white text-black' : 'bg-button text-white']"
            class="px-4 py-1.5 rounded-t-md transition-colors">
            General
        </Link>

        <Link href="/grosir/settings/akun"
            :class="[tap_panel === '/ecer/Settings/Akun' ? 'bg-white text-black' : 'bg-button text-white']"
            class="px-4 py-1.5 rounded-t-md transition-colors">
            Akun
        </Link>
        <Link href="/grosir/settings/bank"
            :class="[tap_panel === '/ecer/Settings/Akun' ? 'bg-white text-black' : 'bg-button text-white']"
            class="px-4 py-1.5 rounded-t-md transition-colors">
            Bank
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

// Menerima data dari parent
defineProps({
    tap_panel: String
})
</script>