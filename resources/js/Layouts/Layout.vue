<template>
    <div class="bg-linear-to-bl from-teal-700 to-indigo-800">
        <header class="w-full py-3">
            <nav class="max-w-xl mx-auto h-12 bg-blue-950 rounded-full flex items-center justify-between px-3">
                <div class="flex items-center justify-between w-full">
                    <Link href="/" class="px-6 h-8 font-bold rounded-full bg-white flex items-center justify-center">
                        Home
                    </Link>

                    <Link v-if="$page.props.auth.user" :href="route('dashboard.index')"
                        class="px-6 h-8 font-bold rounded-full bg-white flex items-center justify-center">
                        Dashboard
                    </Link>

                    <Link v-else :href="route('login')"
                        class="px-6 h-8 font-bold rounded-full bg-white flex items-center justify-center">
                        Login
                    </Link>
                </div>
            </nav>
        </header>
        <main class="w-full h-[calc(100vh-72px)]">
            <slot></slot>
        </main>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

</script>