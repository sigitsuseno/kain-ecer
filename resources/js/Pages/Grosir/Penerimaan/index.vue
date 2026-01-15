<template>
    <div class="w-full h-[calc(100vh-48px)] overflow-hidden bg-blue-100 relative">
        <div class="text-2xl font-semibold text-primary py-2 px-6 mt-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class='bx bxs-package'></i> <span>Input Kain Baru</span>
            </div>
            <div>
                <Link href="/grosir/penerimaan/create"
                    class="px-2 h-8 flex items-center justify-center text-sm bg-button active:scale-90 text-white rounded-sm">
                    <i class='bx bx-plus'></i>
                    Tambah
                    Produk
                </Link>
            </div>
        </div>
        <div class="px-4 w-full h-[calc(100vh-120px)] overflow-y-auto">
            <div class="bg-white p-4 rounded-lg">
                <div class="w-full flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <select name="perHalaman" id="per_halaman"
                            class="w-20 h-8 px-2 rounded-sm border border-primary/30 text-sm outline-none">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <input type="search"
                            class="w-60 h-8 px-3 flex text-sm items-center border border-primary/30 rounded-sm outline-none"
                            placeholder="Cari produk dari nama, warna atau seri">
                    </div>
                </div>
                <div class="w-full overflow-x-auto mt-4">
                    <table class="w-full text-sm">
                        <thead class="bg-primary/10">
                            <tr class="border-b border-primary/30">
                                <th class="px-2 py-1">No.</th>
                                <th class="px-2 py-1">Nama Produk</th>
                                <th class="px-2 py-1">Warna / Seri</th>
                                <th class="px-2 py-1">Tipe</th>
                                <th class="px-2 py-1">Roll</th>
                                <th class="px-2 py-1">Panjang</th>
                                <th class="px-2 py-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-2 py-1"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';


</script>

<style lang="scss" scoped></style>
