<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
    IconBell,
    IconChecklist,
    IconCircleCheck,
    IconClipboardX,
    IconClock,
    IconFileCheck,
    IconPackage,
    IconUserCheck,
} from "@tabler/icons-vue";

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    stats: {
        totalSamples: number;
        processing: number;
        pendingTasks: number;
        pendingQc: number;
        pendingAfrida: number;
        pendingParinton: number;
        completedToday: number;
        unreadNotifications: number;
    };
    recentSamples: Array<{
        code: string;
        customer: string;
        status: string;
        date: string | null;
    }>;
}>();

const cards = [
    { label: "Total Sampel", value: props.stats.totalSamples, icon: IconPackage, href: route("samples.index"), color: "text-blue-600", bg: "bg-blue-50" },
    { label: "Sedang Diproses", value: props.stats.processing, icon: IconClock, href: route("formulirs.index"), color: "text-orange-600", bg: "bg-orange-50" },
    { label: "Tugas Belum Diterima", value: props.stats.pendingTasks, icon: IconClipboardX, href: route("belum.diterima.index"), color: "text-red-600", bg: "bg-red-50" },
    { label: "Menunggu Paraf QC", value: props.stats.pendingQc, icon: IconChecklist, href: route("paraf.qc.index"), color: "text-violet-600", bg: "bg-violet-50" },
    { label: "Menunggu Bu Afrida", value: props.stats.pendingAfrida, icon: IconFileCheck, href: route("persetujuan.manager.index"), color: "text-amber-600", bg: "bg-amber-50" },
    { label: "Menunggu Pak Parinton", value: props.stats.pendingParinton, icon: IconUserCheck, href: route("persetujuan.manager.index"), color: "text-cyan-600", bg: "bg-cyan-50" },
    { label: "Selesai Hari Ini", value: props.stats.completedToday, icon: IconCircleCheck, href: route("formulirs.index"), color: "text-green-600", bg: "bg-green-50" },
    { label: "Notifikasi Belum Dibaca", value: props.stats.unreadNotifications, icon: IconBell, href: route("notifikasi.index"), color: "text-pink-600", bg: "bg-pink-50" },
];
</script>

<template>
    <Head title="Dashboard SISAMSUL" />

    <div class="flex flex-col gap-6 p-4 pt-4 md:p-8">
        <div>
            <h1 class="text-2xl font-black uppercase italic tracking-tight">Dashboard SISAMSUL</h1>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">PT Mark Dynamics Indonesia Tbk</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link v-for="card in cards" :key="card.label" :href="card.href" class="block">
                <Card class="overflow-hidden border-none shadow-sm transition-shadow hover:shadow-md">
                    <CardContent class="flex items-center justify-between p-5">
                        <div>
                            <p class="mb-1 text-[9px] font-black uppercase tracking-widest text-muted-foreground">{{ card.label }}</p>
                            <p class="text-3xl font-black italic tracking-tighter">{{ card.value }}</p>
                        </div>
                        <div :class="[card.bg, card.color]" class="flex size-11 items-center justify-center rounded-2xl">
                            <component :is="card.icon" class="size-5" />
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <Card class="border-none shadow-sm">
            <CardHeader>
                <CardTitle class="text-xs font-black uppercase tracking-widest">Aktivitas Sampel Terbaru</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div v-if="recentSamples.length === 0" class="py-8 text-center text-sm italic text-muted-foreground">Belum ada aktivitas sampel.</div>
                <div v-for="sample in recentSamples" :key="`${sample.code}-${sample.date}`" class="flex items-center justify-between rounded-xl border p-3">
                    <div>
                        <p class="text-xs font-black uppercase text-primary">{{ sample.code }}</p>
                        <p class="text-[10px] font-bold uppercase text-muted-foreground">{{ sample.customer }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <Badge :variant="sample.status === 'Selesai' ? 'default' : 'secondary'" class="text-[9px] font-black uppercase">{{ sample.status }}</Badge>
                        <span class="text-xs text-muted-foreground">{{ sample.date ?? '-' }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
