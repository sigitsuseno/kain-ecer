# edit controller

```php
    public function edit($id)
    {
        $no_nota = '';
        $createNo = Penjualan::orderBy('id', 'desc')->latest()->first();

        if ($createNo) {
            $nomernya = $createNo->id + 1;
            $no_nota = 'INV' . date('Ymd') . str_pad($nomernya, 4, '0', STR_PAD_LEFT);
        } else {
            $nomernya = 1;
            $no_nota = 'INV' . date('Ymd') . str_pad($nomernya, 4, '0', STR_PAD_LEFT);
        }


        $varian = ProdukVarian::with([
            'produk',
            'details' => function ($query) {

                $query->where('status', 'tersedia')->with('satuan');
            }
        ])->where('gudang_id', 2)->get();

        return inertia('Grosir/Penjualan/Create', [
            'no_nota' => $no_nota,
            'produk_varian' => $varian,
            'penjualan' => Penjualan::findOrFail($id),
            'customers_data' => Pelanggan::all()
        ]);
    }
```

# dibawah adalah template create ubahin ke

```vue
<template>
    <div
        class="w-full h-[calc(100vh-40px)] overflow-hidden bg-blue-100 grid grid-cols-[1fr_300px]"
    >
        <div class="w-full h-full p-6 overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <div
                    class="flex items-center gap-3 text-2xl font-semibold text-primary"
                >
                    <i class="bx bxs-package"></i> <span>Penjualan Baru</span>
                </div>
                <Link
                    href="/grosir/penjualan"
                    class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm"
                >
                    <i class="bx bx-arrow-back"></i> Kembali
                </Link>
            </div>

            <form @submit.prevent="submit">
                <div
                    class="bg-white rounded-xl shadow-lg p-4 mb-6 grid grid-cols-4 gap-4 border border-blue-200"
                >
                    <div>
                        <label class="text-sm font-medium">No Nota</label>
                        <input
                            type="text"
                            :value="props.no_nota"
                            readonly
                            class="w-full h-8 rounded-md border px-3 text-sm bg-gray-100 outline-none"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Tanggal</label>
                        <input
                            type="date"
                            v-model="form.transaction_date"
                            class="w-full h-8 rounded-md border px-3 text-sm focus:ring focus:ring-primary/30 outline-none"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium">Pelanggan</label>
                        <input
                            type="text"
                            v-model="form.customer_name"
                            list="data_pelanggan"
                            class="w-full h-8 rounded-md border px-3 text-sm focus:ring focus:ring-primary/30 outline-none"
                            placeholder="Pilih/Tulis Pelanggan"
                        />
                        <datalist id="data_pelanggan">
                            <option
                                v-for="c in customers"
                                :key="c.id"
                                :value="c.name"
                            ></option>
                        </datalist>
                    </div>
                    <div>
                        <label class="text-sm font-medium"
                            >PJ (Marketing)</label
                        >
                        <input
                            type="text"
                            v-model="form.marketing_name"
                            list="data_marketing"
                            class="w-full h-8 rounded-md border px-3 text-sm focus:ring focus:ring-primary/30 outline-none"
                            placeholder="Pilih/Tulis PJ"
                        />
                        <datalist id="data_marketing">
                            <option
                                v-for="m in props.suppliers"
                                :key="m.id"
                                :value="m.name"
                            ></option>
                        </datalist>
                    </div>
                </div>

                <div
                    class="bg-white rounded-xl shadow overflow-hidden border border-blue-200"
                >
                    <table class="w-full text-sm">
                        <thead class="bg-primary/30">
                            <tr>
                                <th class="py-2 px-3 uppercase text-left w-30">
                                    Info Produk
                                </th>
                                <th class="py-2 px-3 uppercase text-left">
                                    Daftar Roll
                                </th>
                                <th
                                    class="py-2 px-3 uppercase text-center w-24"
                                >
                                    Jumlah
                                </th>
                                <th
                                    class="py-2 px-3 uppercase text-center w-32"
                                >
                                    Harga/Yard
                                </th>
                                <th
                                    class="py-2 px-3 uppercase text-center w-32"
                                >
                                    Subtotal
                                </th>
                                <th
                                    class="py-2 px-3 uppercase text-center w-14"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="form.items.length === 0">
                                <td
                                    colspan="6"
                                    class="py-10 text-center text-gray-400 italic"
                                >
                                    Belum ada produk. Silahkan cari produk di
                                    panel kanan.
                                </td>
                            </tr>
                            <tr
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="border-b border-black/10 hover:bg-blue-50/50"
                            >
                                <td class="p-3">
                                    <div
                                        class="font-bold text-primary line-clamp-1"
                                    >
                                        {{ item.product_name }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Warna: {{ item.color }}
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="r in item.rolls"
                                            :key="r.id"
                                            class="text-xs bg-blue-100 border border-blue-300 px-1 rounded"
                                        >
                                            {{ r.qty }} {{ r.satuan.inisial }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="font-bold text-gray-700">
                                        {{ item.rolls.length }} Roll
                                    </div>
                                    <div
                                        class="text-xs text-amber-900 font-semibold"
                                    >
                                        {{ item.total_qty }} Yd
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <input
                                        type="number"
                                        v-model="item.unit_price"
                                        @input="calculateSubtotal(index)"
                                        class="w-full h-8 border rounded text-center text-blue-600 font-bold outline-none focus:border-primary"
                                    />
                                </td>
                                <td class="p-3 text-center">
                                    <div class="font-bold text-primary">
                                        {{ formatRupiah(item.subtotal) }}
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <button
                                        @click="removeItem(index)"
                                        type="button"
                                        class="text-red-500 hover:scale-125 transition"
                                    >
                                        <i class="bx bx-trash text-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot
                            v-if="form.items.length > 0"
                            class="bg-gray-50 border-t-2 border-primary/20"
                        >
                            <tr>
                                <td
                                    colspan="4"
                                    class="p-3 text-right font-bold uppercase"
                                >
                                    Grand Total
                                </td>
                                <td
                                    class="p-3 text-center font-extrabold text-lg text-primary"
                                >
                                    {{ formatRupiah(grandTotal) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing || form.items.length === 0"
                        class="bg-indigo-600 px-10 py-2.5 text-white rounded-lg font-bold hover:bg-indigo-700 shadow-lg disabled:opacity-50 transition-all active:scale-95"
                    >
                        <span v-if="form.processing">MENYIMPAN...</span>
                        <span v-else>SIMPAN TRANSAKSI</span>
                    </button>
                </div>
            </form>
        </div>

        <div
            class="w-full h-[calc(100vh-40px)] bg-slate-200 border-l shadow-2xl flex flex-col"
        >
            <div class="p-4 flex flex-col h-full overflow-hidden">
                <div class="relative mb-4">
                    <i
                        class="bx bx-search absolute left-3 top-2.5 text-gray-400"
                    ></i>
                    <input
                        type="search"
                        v-model="searchProduk"
                        class="w-full h-10 pl-9 pr-4 bg-white outline-none rounded-full border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20"
                        placeholder="Cari kain atau warna..."
                    />
                </div>

                <div class=" flex-1 overflow-y-auto space-y-2 pr-1">
                    <div
                        v-for="p in filteredProduk"
                        :key="p.id"
                        @click="selectProduk(p)"
                        class="p-3 border bg-white rounded-xl hover:border-primary hover:bg-blue-50 cursor-pointer transition group border-gray-100 shadow-sm"
                    >
                        <div class="flex justify-between items-start">
                            <span class="font-bold text-gray-800 text-sm">{{
                                p.produk.nama
                            }}</span>
                            <span
                                class="text-xs bg-primary/10 text-primary px-2 rounded-full font-semibold"
                            >
                                {{ p.details?.length || 0 }} Roll
                            </span>
                        </div>
                        <div
                            class="text-sm text-blue-900 mt-1 flex items-center justify-between"
                        >
                            Warna: {{ p.nama }}
                            <i
                                class="bx bx-plus-circle text-xl text-primary opacity-0 group-hover:opacity-100 transition"
                            ></i>
                        </div>
                    </div>
                    <div
                        v-if="filteredProduk.length === 0"
                        class="text-center py-10 text-gray-400 text-sm italic"
                    >
                        Produk tidak ditemukan.
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showModalRoll"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
        >
            <div
                class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-in zoom-in duration-200"
            >
                <div
                    class="p-4 bg-primary text-white flex justify-between items-center"
                >
                    <h3 class="font-bold flex items-center gap-2">
                        <i class="bx bx-list-check text-xl"></i> Pilih Roll:
                        {{ activeProduk?.produk.nama }}
                    </h3>
                    <button
                        @click="showModalRoll = false"
                        class="hover:bg-white/20 rounded-full p-1"
                    >
                        <i class="bx bx-x text-2xl"></i>
                    </button>
                </div>

                <div class="p-4">
                    <div class="relative mb-4">
                        <i
                            class="bx bx-search absolute left-3 top-2.5 text-gray-400"
                        ></i>
                        <input
                            type="number"
                            v-model="searchRoll"
                            class="w-full h-9 pl-9 pr-4 bg-gray-50 outline-none rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary/20"
                            placeholder="Cari ukuran panjang roll..."
                        />
                    </div>

                    <div
                        class="text-xs text-gray-500 mb-3 uppercase font-bold tracking-wider"
                    >
                        Tersedia:
                    </div>

                    <div
                        class="grid grid-cols-4 gap-2 max-h-[300px] overflow-y-auto p-1"
                    >
                        <div
                            v-for="r in filteredRolls"
                            :key="r.id"
                            @click="toggleRollSelection(r)"
                            :class="[
                                'p-2 border rounded-lg text-center cursor-pointer transition-all text-sm font-semibold',
                                isRollSelected(r.id)
                                    ? 'bg-primary text-white border-primary shadow-md'
                                    : 'bg-gray-50 text-gray-700 hover:border-primary',
                            ]"
                        >
                            {{ r.qty }}
                            <span class="text-[10px]">{{
                                r.satuan.inisial
                            }}</span>
                        </div>
                        <div
                            v-if="filteredRolls.length === 0"
                            class="col-span-4 text-center py-4 text-gray-400 text-xs italic"
                        >
                            Ukuran tidak ditemukan.
                        </div>
                    </div>
                </div>

                <div
                    class="p-4 bg-gray-50 border-t flex justify-between items-center"
                >
                    <div class="text-sm font-medium">
                        Terpilih:
                        <span class="text-primary font-bold"
                            >{{ tempSelectedRolls.length }} Roll</span
                        >
                        <span class="text-gray-400 mx-1">|</span>
                        Total:
                        <span class="text-primary font-bold"
                            >{{ tempTotalQty }} yd</span
                        >
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="showModalRoll = false"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-200 rounded-lg font-medium"
                        >
                            Batal
                        </button>
                        <button
                            @click="confirmRollSelection"
                            :disabled="tempSelectedRolls.length === 0"
                            class="px-6 py-2 bg-primary text-white rounded-lg font-bold disabled:opacity-50 active:scale-95 transition-transform"
                        >
                            Konfirmasi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    customers_data: Array,
    suppliers: Array,
    produk_varian: Array,
    no_nota: String,
});

/* =========================
 * DATA UTAMA
 * ========================= */
const customers = props.customers_data ?? [];

const form = useForm({
    invoice_no: props.no_nota,
    transaction_date: new Date().toISOString().slice(0, 10),
    customer_name: "",
    marketing_name: "",
    items: [],
});

const searchProduk = ref("");
const searchRoll = ref("");
const showModalRoll = ref(false);
const activeProduk = ref(null);
const tempSelectedRolls = ref([]);

/* =========================
 * FILTER PRODUK
 * ========================= */
const filteredProduk = computed(() => {
    if (!searchProduk.value) return props.produk_varian;

    const keyword = searchProduk.value.toLowerCase();

    return props.produk_varian.filter(
        (p) =>
            p.produk.nama.toLowerCase().includes(keyword) ||
            p.nama.toLowerCase().includes(keyword),
    );
});

/* =========================
 * FILTER ROLL
 * ========================= */
const filteredRolls = computed(() => {
    if (!activeProduk.value) return [];

    if (!searchRoll.value) return activeProduk.value.details;

    return activeProduk.value.details.filter((r) =>
        r.qty.toString().includes(searchRoll.value),
    );
});

/* =========================
 * PILIH PRODUK
 * ========================= */
const selectProduk = (produk) => {
    activeProduk.value = produk;
    tempSelectedRolls.value = [];
    searchRoll.value = "";
    showModalRoll.value = true;
};

/* =========================
 * TOGGLE ROLL
 * ========================= */
const toggleRollSelection = (roll) => {
    const idx = tempSelectedRolls.value.findIndex((r) => r.id === roll.id);
    idx > -1
        ? tempSelectedRolls.value.splice(idx, 1)
        : tempSelectedRolls.value.push(roll);
};

const isRollSelected = (id) => tempSelectedRolls.value.some((r) => r.id === id);

/* =========================
 * TOTAL QTY TEMP
 * ========================= */
const tempTotalQty = computed(() => {
    return tempSelectedRolls.value.reduce((sum, r) => sum + Number(r.qty), 0);
});

/* =========================
 * KONFIRMASI ROLL
 * ========================= */
const confirmRollSelection = () => {
    if (!activeProduk.value || tempSelectedRolls.value.length === 0) return;

    const existing = form.items.find(
        (i) => i.product_id === activeProduk.value.id,
    );

    const mappedRolls = tempSelectedRolls.value.map((r) => ({
        id: r.id,
        roll_id: r.id,
        varian_id: r.varian_id,
        qty: Number(r.qty),
        satuan_id: r.satuan_id,
        satuan: r.satuan.inisial,
    }));

    if (existing) {
        mappedRolls.forEach((r) => {
            if (!existing.rolls.find((x) => x.roll_id === r.roll_id)) {
                existing.rolls.push(r);
            }
        });

        existing.total_qty = existing.rolls.reduce((s, r) => s + r.qty, 0);

        calculateSubtotal(form.items.indexOf(existing));
    } else {
        form.items.push({
            product_id: activeProduk.value.id,
            product_name: activeProduk.value.produk.nama,
            color: activeProduk.value.nama,
            total_qty: tempTotalQty.value,
            unit_price: 0,
            subtotal: 0,
            rolls: mappedRolls,
        });
    }

    showModalRoll.value = false;
};

/* =========================
 * HITUNG SUBTOTAL
 * ========================= */
const calculateSubtotal = (index) => {
    const item = form.items[index];
    const price = Number(item.unit_price) || 0;
    item.subtotal = item.total_qty * price;
};

/* =========================
 * GRAND TOTAL
 * ========================= */
const grandTotal = computed(() =>
    form.items.reduce((sum, i) => sum + i.subtotal, 0),
);

/* =========================
 * REMOVE ITEM
 * ========================= */
const removeItem = (index) => {
    form.items.splice(index, 1);
};

/* =========================
 * FORMAT RUPIAH
 * ========================= */
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

/* =========================
 * SUBMIT
 * ========================= */
const submit = () => {
    if (form.items.length === 0) return;
    form.post(route("grosir.penjualan.store"));
};
</script>

<style scoped>
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>
```

# model penjualan

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $fillable = [
        'nomer',
        'tanggal',
        'bagian',
        'pelanggan_id',
        'marketing_id',
        'total_penjualan',
        'diskon',
        'pajak',
        'ongkir',
        'kembalian',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(PenjualanItem::class, 'penjualan_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'penjualan_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function marketing()
    {
        return $this->belongsTo(Supplier::class, 'marketing_id');
    }
}
```

# model PenjualanItem

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanItem extends Model
{
    protected $fillable = [
        'penjualan_id',
        'varian_id',
        'satuan_id',
        'total_qty',
        'total_jumlah',
        'harga_satuan',
        'subtotal',
    ];
    public function details()
    {
        return $this->hasMany(PenjualanDetail::class, 'penjualan_item_id');
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }
}
```

# model PenjualanDetail

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    protected $fillable = [
        'penjualan_item_id',
        'produk_detail_id',
        'satuan_id',
        'qty',
        'harga',
        'jumlah_harga',
        'status',
    ];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function item()
    {
        return $this->belongsTo(PenjualanItem::class, 'penjualan_item_id');
    }
}
```

# model produk

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $fillable = [
        'nama',
        'kategori_id',
        'satuan_id',
        'brand_id',
        'deskripsi',
        'status',

    ];

    public function varian()
    {
        return $this->hasMany(ProdukVarian::class, 'produk_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
```

# model ProdukVarian

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukVarian extends Model
{
    protected $fillable = [
        'id',
        'produk_id',
        'gudang_id',
        'nama',
        'tipe',
        'panjang',
        'tinggi',
        'lebar',
        'tebal',
        'jumlah',
        'sku',
        'is_paket',
        'harga_beli',
        'harga_jual',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
    public function stok()
    {
        return $this->hasOne(Stok::class, 'varian_id');
    }

    public function details()
    {
        return $this->hasMany(ProdukDetail::class, 'varian_id');
    }
    public function konversi()
    {
        return $this->hasMany(KonversiSatuan::class, 'satuan_id');
    }
    public function purcItem()
    {
        return $this->hasOne(PembelianItems::class, 'varian_id');
    }
}
```

# model ProdukDetail

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukDetail extends Model
{
    protected $fillable = [
        'varian_id',
        'stok_id',
        'old_id',
        'satuan_id',
        'qty',
        'isi',
        'status',
    ];

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }

    public function stok()
    {
        return $this->belongsTo(Stok::class, 'stok_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
```

# Model Stok

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    protected $table = 'stoks';

    protected $fillable = [
        'varian_id',
        'gudang_id',
        'nomor',
        'tanggal_kadaluarsa',

    ];

    public function varian()
    {
        return $this->belongsTo(ProdukVarian::class, 'varian_id');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function details()
    {
        return $this->HasMany(ProdukDetail::class, 'stok_id');
    }
}
```
