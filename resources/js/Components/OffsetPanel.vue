<template>
    <button @click="isOpen = true" class="bg-blue-600 text-white px-4 py-2 rounded">
        Buka Detail Produk
    </button>

    <Teleport to="body">
        <div v-if="isOpen" class="relative z-50">

            <Transition enter-active-class="ease-in-out duration-500" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="ease-in-out duration-500"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div class="fixed inset-0 bg-opacity-75 transition-opacity" @click="isOpen = false"></div>
            </Transition>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="fixed inset-y-0 right-0 flex max-w-full">

                        <Transition enter-active-class="transform transition ease-in-out duration-500"
                            enter-from-class="translate-x-full" enter-to-class="translate-x-0"
                            leave-active-class="transform transition ease-in-out duration-500"
                            leave-from-class="translate-x-0" leave-to-class="translate-x-full">
                            <div v-if="isOpen" class="pointer-events-auto w-screen max-w-[35%] ml-auto">
                                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                                    <div class="px-4 py-6 bg-gray-50 sm:px-6 flex items-center justify-between">
                                        <h2 class="text-lg font-medium text-gray-900">Detail Varian</h2>
                                        <button @click="isOpen = false" class="text-gray-400 hover:text-gray-500">
                                            <span class="sr-only">Close panel</span>
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="relative mt-6 flex-1 px-4 sm:px-6">
                                        <slot>
                                            yt
                                        </slot>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>

                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue'

const isOpen = ref(false)
</script>