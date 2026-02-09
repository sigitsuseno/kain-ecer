<template>
    <div class="w-full h-[calc(100vh-48px)] relative">
        <SettingTabPanel :tap_panel="activeComponent" />
        <div class="w-full h-[calc(100vh-88px)] bg-indigo-100 overflow-hidden">
            <div class="w-full h-16 bg-white flex items-center justify-between px-4">

            </div>
        </div>
    </div>

</template>

<script setup>
import { computed } from 'vue';
import SettingTabPanel from '../../../Components/SettingTabPanel.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage()
const activeComponent = computed(() => page.component)

</script>