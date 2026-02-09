<template>
    <div class="w-full h-[calc(100vh-48px)] relative">
        <EcerSettingTab :tap_panel="activeComponent" />
        <div class="w-full h-[calc(100vh-88px)] bg-indigo-100 overflow-hidden">
            <div class="w-full bg-white flex items-center justify-between px-4">
                <div class="max-w-md mx-auto bg-white shadow rounded-lg p-4">
                    <h2 class="font-bold text-lg mb-4 text-indigo-900">Pengaturan Bank</h2>

                    <form @submit.prevent="submit" class="flex gap-2 mb-6">
                        <input v-model="form.nama_bank" type="text" placeholder="Nama Bank (BCA, Mandiri...)"
                            class="flex-1 border rounded px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500"
                            required>
                        <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">
                            Tambah
                        </button>
                    </form>

                    <div class="space-y-2">
                        <div v-for="bank in banks" :key="bank.id"
                            class="flex justify-between items-center p-3 bg-slate-50 rounded border">
                            <span class="font-medium">{{ bank.nama_bank }}</span>
                            <button @click="hapus(bank.id)" class="text-red-500 hover:text-red-700">
                                <i class="bx bx-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import EcerSettingTab from '../../../Components/EcerSettingTab.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage()
const activeComponent = computed(() => page.component)



defineProps({ banks: Array });

const form = useForm({
    nama_bank: ''
});

const submit = () => {
    form.post(route('ecer.settings.bank.store'), {
        onSuccess: () => form.reset()
    });
};

const hapus = (id) => {
    if (confirm('Hapus bank ini?')) {
        router.delete(`/ecer/settings/bank/${id}`);
    }
};

</script>