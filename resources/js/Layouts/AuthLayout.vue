<template>
    <div class="bg-linear-to-bl from-teal-700 to-indigo-800">
        <main class="w-full h-screen flex items-center justify-center">
            <slot></slot>
        </main>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
// console.log(page.props.auth.user); 
</script>