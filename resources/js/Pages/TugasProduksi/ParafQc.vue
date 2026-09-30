<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { IconChecklist, IconEye, IconSearch, IconX } from "@tabler/icons-vue";
import { ref, watch } from "vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    tugas_list: { data: any[]; links: any[]; from: number | null; to: number | null; total: number };
    filters: { search?: string; tab?: string };
    tab: string;
    is_admin: boolean;
}>();

const search = ref(props.filters.search || "");
let timeout: ReturnType<typeof setTimeout>;

const load = (nextTab = props.tab) => {
    router.get(route("paraf.qc.index"), { search: search.value, tab: nextTab }, {
        preserveState: true,
        replace: true,
    });
};

watch(search, () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => load(), 500);
});

const clearSearch = () => { search.value = ""; };
const cleanLabel = (label: string) => label.includes("Previous") ? "Sebelumnya" : label.includes("Next") ? "Selanjutnya" : label;
</script>

<template>
    <Head title="Paraf QC" />
    <div class="flex flex-col gap-4 p-4 pt-4 md:p-8">
        <Card class="overflow-hidden border-none shadow-sm">
            <CardHeader class="flex flex-col items-start justify-between gap-4 pb-6 md:flex-row md:items-center">
                <CardTitle class="flex items-center gap-2 text-xl font-black uppercase tracking-tight">
                    <IconChecklist class="size-6 text-primary" /> Paraf QC
                </CardTitle>
                <div class="relative w-full md:w-72">
                    <IconSearch class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Cari Kode Sample..." class="pl-10 pr-10" />
                    <button v-if="search" @click="clearSearch" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"><IconX class="size-4" /></button>
                </div>
            </CardHeader>
            <CardContent>
                <div class="mb-4 flex gap-2">
                    <Button :variant="tab === 'menunggu' ? 'default' : 'outline'" @click="load('menunggu')">Menunggu Paraf Saya</Button>
                    <Button :variant="tab === 'afrida' ? 'default' : 'outline'" @click="load('afrida')">Sudah Diperiksa Bu Afrida</Button>
                    <Button :variant="tab === 'parinton' ? 'default' : 'outline'" @click="load('parinton')">Sudah Disetujui Pak Parinton</Button>
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-[10px] uppercase">
                            <tr class="border-b">
                                <th class="h-10 px-4 text-left font-bold">Departemen</th>
                                <th class="h-10 px-4 text-left font-bold">Sub</th>
                                <th class="h-10 px-4 text-left font-bold">Kode Sample</th>
                                <th class="h-10 px-4 text-left font-bold">Customer</th>
                                <th class="h-10 px-4 text-left font-bold">Model</th>
                                <th class="h-10 px-4 text-left font-bold">Paraf SPV</th>
                                <th class="h-10 px-4 text-left font-bold">Paraf QC</th>
                                <th class="h-10 px-4 text-right font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="tugas_list.data.length === 0" class="border-b"><td colspan="8" class="h-24 text-center italic text-muted-foreground">Tidak ada data paraf QC.</td></tr>
                            <tr v-for="tugas in tugas_list.data" :key="tugas.id" class="border-b whitespace-nowrap hover:bg-muted/30">
                                <td class="px-4 py-3">{{ tugas.sub_departemen?.departemen?.nama }}</td>
                                <td class="px-4 py-3"><Badge variant="outline">{{ tugas.sub_departemen?.nama }}</Badge></td>
                                <td class="px-4 py-3 font-mono font-bold text-primary">{{ tugas.formulir?.sampel?.kode_sample }}</td>
                                <td class="px-4 py-3">{{ tugas.formulir?.sampel?.customer }}</td>
                                <td class="px-4 py-3">{{ tugas.formulir?.sampel?.model }}</td>
                                <td class="px-4 py-3 text-blue-600">{{ tugas.spv_user?.name ?? '-' }}</td>
                                <td class="px-4 py-3 text-green-600">{{ tugas.qc_user?.name ?? 'Belum diparaf' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Button v-if="!is_admin && tab === 'menunggu'" variant="ghost" size="icon" as-child>
                                        <Link :href="route('formulirs.departemen.edit', { formulir: tugas.formulir_id, departemen_terlibat: tugas.id })" title="Buka untuk paraf QC"><IconEye class="size-4 text-primary" /></Link>
                                    </Button>
                                    <Button v-else-if="tab === 'menunggu'" variant="ghost" size="icon" as-child>
                                        <Link :href="route('tugas.produksi.show', tugas.id)" title="Lihat preview dokumen"><IconEye class="size-4 text-primary" /></Link>
                                    </Button>
                                    <Button v-else variant="ghost" size="icon" as-child>
                                        <Link :href="route('persetujuan.manager.show', tugas.formulir_id)" title="Lihat detail persetujuan"><IconEye class="size-4 text-primary" /></Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 flex items-center justify-between gap-4">
                    <p class="text-xs text-muted-foreground">Menampilkan {{ tugas_list.from ?? 0 }} - {{ tugas_list.to ?? 0 }} dari {{ tugas_list.total }} data</p>
                    <nav class="flex gap-1"><template v-for="(link, k) in tugas_list.links" :key="k"><Button v-if="link.url === null" variant="outline" size="sm" disabled v-html="cleanLabel(link.label)" /><Button v-else as-child variant="outline" size="sm" :class="{ 'bg-primary text-primary-foreground': link.active }"><Link :href="link.url" v-html="cleanLabel(link.label)" /></Button></template></nav>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
