<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import Sidebar from '../Components/Sidebar.vue'
import EcerSidebar from '../Components/EcerSidebar.vue';
import GrosirSidebar from '../Components/GrosirSidebar.vue';
import IconSidebar from '../Components/Icons/IconSidebar.vue'

const page = usePage();

const props = defineProps({
    auth: Object,
});

const breadcrumbs = computed(() => {
    const paths = page.component.split('/');

    return paths.map((path, index) => {
        return {
            label: path,
            url: '/' + paths.slice(0, index + 1).join('/').toLowerCase(),
            isLast: index === paths.length - 1
        };
    });
});

</script>

<template>
    <div class="w-full h-screen relative flex flex-wrap">
        <input id="toogleSidebar" type="checkbox" class="peer sr-only">
        <label for="toogleSidebar"
            class="absolute top-0 left-50 flex items-center justify-center w-12 h-12 cursor-pointer hover:bg-slate-100 peer-checked:left-16 transition-all duration-300">
            <IconSidebar />
        </label>
        <header class="w-full h-12 bg-amber-100 flex items-center pl-64 peer-checked:pl-28 transition-all duration-300">
            <div class="w-full">
                <ol class="inline-flex items-center space-x-1">

                    <li v-for="(crumb, index) in breadcrumbs" :key="index">
                        <div class="flex items-center">
                            <span v-if="index > 0" class="mx-2 text-gray-400">/</span>
                            <Link v-if="!crumb.isLast" :href="crumb.url"
                                class="font-medium text-gray-700 hover:text-blue-600 capitalize">
                                {{ crumb.label }}
                            </Link>

                            <span v-else class="font-bold text-gray-500 capitalize">
                                {{ crumb.label }}
                            </span>
                        </div>
                    </li>

                </ol>
            </div>
        </header>
        <aside class="peer-checked:w-16 w-50 h-[calc(100vh-48px)] shrink-0 relative transition-all duration-300">
            <Sidebar v-if="auth.user.role === 'owner'" :auth="auth" />

            <EcerSidebar v-else-if="auth.user.role === 'admin-ecer'" :auth="auth" />

            <GrosirSidebar v-else-if="auth.user.role === 'admin-grosir'" :auth="auth" />

            <div v-else class="p-4 text-white">Role tidak terdaftar</div>
        </aside>
        <main
            class="peer-checked:w-[calc(100%-64px)] w-[calc(100%-200px)] h-[calc(100vh-48px)] transition-all duration-300">
            <slot></slot>
        </main>
    </div>
</template>