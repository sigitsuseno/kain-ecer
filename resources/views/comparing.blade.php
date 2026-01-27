<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    @vite('resources/js/app.js')
</head>

<body>
    <div class="w-full max-w-xl">
        <tr v-for="item in results" :key="item.sku" :class="item.is_sync ? 'bg-green-50' : 'bg-red-50'">
            <td>{{ item . nama_produk }}</td>
            <td class="text-right">{{ item . stok_lama }} Yd</td>
            <td class="text-right">{{ item . stok_baru }} Yd</td>
            <td class="text-center">
                <span v-if="item.is_sync" class="text-green-600 font-bold">MATCH (TRUE)</span>
                <span v-else class="text-red-600 font-bold">MISMATCH ({{ item . selisih }})</span>
            </td>
        </tr>
    </div>
</body>

</html>
