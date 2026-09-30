<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { IconClipboardX, IconPencil, IconSearch, IconX } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    tugas_list: {
        data: Array<{
            id: number;
            formulir: {
                size: string;
                running_ke: number;
                sampel: {
                    kode_sample: string;
                    customer: string;
                    model: string;
                };
            };
            sub_departemen: {
                nama: string;
                departemen: { nama: string };
            };
            qty: number;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string };
    is_admin: boolean;
}>();

const search = ref(props.filters.search || "");
let timeout: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route("belum.diterima.index"), { search: value }, {
            preserveState: true,
            replace: true,
        });
    }, 500);
});

const clearSearch = () => {
    search.value = "";
};

const cleanLabel = (label: string) => {
    if (label.includes("Previous")) return "Sebelumnya";
    if (label.includes("Next")) return "Selanjutnya";
    return label;
};
</script>

<template>
    <Head title="Tugas Belum Diterima" />

    <div class="flex flex-col gap-4 p-4 pt-4 md:p-8">
        <Card class="overflow-hidden border-none shadow-sm">
            <CardHeader class="flex flex-col items-start justify-between space-y-4 pb-6 md:flex-row md:items-center md:space-y-0">
                <CardTitle class="flex items-center gap-2 text-xl font-black uppercase tracking-tight">
                    <IconClipboardX class="size-6 text-orange-500" />
                    Tugas Belum Diterima
                </CardTitle>
                <div class="relative w-full md:w-72">
                    <IconSearch class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari Kode Sample..." class="pl-10 pr-10" />
                    <button v-if="search" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                        <IconX class="size-4" />
                    </button>
                </div>
            </CardHeader>

            <CardContent>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="bg-muted/50 text-[10px] uppercase">
                            <tr class="border-b">
                                <th v-if="is_admin" class="h-10 px-4 text-left font-bold">Departemen</th>
                                <th class="h-10 px-4 text-left font-bold">Sub</th>
                                <th class="h-10 px-4 text-left font-bold">Kode Sample</th>
                                <th class="h-10 px-4 text-left font-bold">Customer</th>
                                <th class="h-10 px-4 text-left font-bold">Model</th>
                                <th class="h-10 px-4 text-left font-bold">Size</th>
                                <th class="h-10 px-4 text-center font-bold">Run Ke</th>
                                <th class="h-10 px-4 text-center font-bold">Qty</th>
                                <th v-if="!is_admin" class="h-10 px-4 text-right font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="tugas_list.data.length === 0" class="border-b">
                                <td :colspan="8" class="h-24 text-center italic text-muted-foreground">
                                    Tidak ada tugas yang belum diterima.
                                </td>
                            </tr>
                            <tr v-for="tugas in tugas_list.data" :key="tugas.id" class="border-b whitespace-nowrap hover:bg-muted/30">
                                <td v-if="is_admin" class="px-4 py-3 font-medium">{{ tugas.sub_departemen?.departemen?.nama }}</td>
                                <td class="px-4 py-3"><Badge variant="outline" class="bg-orange-50 text-orange-700">{{ tugas.sub_departemen?.nama }}</Badge></td>
                                <td class="px-4 py-3 font-mono font-bold italic text-primary">{{ tugas.formulir.sampel.kode_sample }}</td>
                                <td class="px-4 py-3">{{ tugas.formulir.sampel.customer }}</td>
                                <td class="px-4 py-3">{{ tugas.formulir.sampel.model }}</td>
                                <td class="px-4 py-3">{{ tugas.formulir.size }}</td>
                                <td class="px-4 py-3 text-center">{{ tugas.formulir.running_ke }}</td>
                                <td class="px-4 py-3 text-center font-black">{{ tugas.qty }}</td>
                                <td v-if="!is_admin" class="px-4 py-3 text-right">
                                    <Button variant="ghost" size="icon" class="size-8 rounded-full hover:bg-orange-100" as-child>
                                        <Link :href="route('tugas.produksi.edit', tugas.id)">
                                            <IconPencil class="size-4 text-orange-500" />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-col items-center justify-between gap-4 md:flex-row">
                    <p class="text-xs text-muted-foreground">Menampilkan {{ tugas_list.from ?? 0 }} - {{ tugas_list.to ?? 0 }} dari {{ tugas_list.total }} data</p>
                    <nav class="flex items-center gap-1">
                        <template v-for="(link, k) in tugas_list.links" :key="k">
                            <Button v-if="link.url === null" variant="outline" size="sm" disabled class="px-3 text-xs" v-html="cleanLabel(link.label)" />
                            <Button v-else as-child variant="outline" size="sm" class="px-3 text-xs" :class="{ 'bg-primary text-primary-foreground': link.active }">
                                <Link :href="link.url" v-html="cleanLabel(link.label)" />
                            </Button>
                        </template>
                    </nav>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
