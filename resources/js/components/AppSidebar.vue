<script setup lang="ts">
import {
    IconInnerShadowTop,
    IconPackage,
    IconFileDescription,
    IconClipboardList,
    IconClipboardX,
    IconChecklist,
    IconSettingsAutomation,
    IconUsers,
    IconHierarchy,
    IconBuilding,
    IconGitMerge,
    TugasProduksi,
} from "@tabler/icons-vue";

import Master from "@/components/Master.vue";
import NavMain from "@/components/NavMain.vue";
import NavUser from "@/components/NavUser.vue";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

// Ambil data auth dari shared props (HandleInertiaRequests.php)
const page = usePage();
const user = computed(() => page.props.auth.user);
const userRoles = computed(() => (page.props.auth as any).roles as string[]);

/**
 * Logika Hak Akses:
 * isAdminOrQC untuk menu Sampel, Formulir, dan Master Data
 */
const isAdminOrQC = computed(() => {
    return userRoles.value.includes('admin') || userRoles.value.includes('Quality Control');
});

/**
 * Logika Hak Akses: Tugas Produksi
 * Hanya bisa dilihat oleh admin, operator, manager, dan supervisor
 */
const canAccessTugasProduksi = computed(() => {
    const allowed = ['admin', 'Quality Control', 'Operator', 'Leader', 'Manager', 'Supervisor'];
    return userRoles.value.some(role => allowed.includes(role.toLowerCase()) || allowed.includes(role));
});

/**
 * Logika Hak Akses: Persetujuan Manager
 * Hanya bisa dibuka oleh admin, QC Manager, dan Factory Manager
 */
const canAccessManagerApproval = computed(() => {
    const allowed = ['admin', 'QC Manager', 'Factory Manager'];
    return userRoles.value.some(role => allowed.includes(role));
});

/**
 * Menu Navigasi Utama
 */
const filteredNavMain = computed(() => {
    let menus: any[] = [];

    // 1. Menu Sampel & Formulir (Hanya Admin & QC)
    if (isAdminOrQC.value) {
        menus.push(
            {
                title: "Sampel",
                url: route("samples.index"),
                icon: IconPackage,
                root: "Sample",
            },
            {
                title: "Formulir Permintaan",
                url: route("formulirs.index"),
                icon: IconFileDescription,
                root: "Formulir",
            }
        );
    }

    // 2. Menu Tugas Produksi (Admin, Operator, Manager, Supervisor)
    if (canAccessTugasProduksi.value) {
        menus.push(
            {
                title: "Tugas Produksi",
                url: route("tugas.produksi.index"),
                icon: IconClipboardList,
                root: "TugasProduksi/Index",
            },
            {
                title: "Tugas Belum Diterima",
                url: route("belum.diterima.index"),
                icon: IconClipboardX,
                root: "TugasProduksi/BelumDiterima",
            },
        );
    }

    if (isAdminOrQC.value) {
        menus.push({
            title: "Paraf QC",
            url: route("paraf.qc.index"),
            icon: IconChecklist,
            root: "TugasProduksi/ParafQc",
        });
    }

    // 3. Menu Persetujuan Manager (Admin, QC Manager, Factory Manager)
    if (canAccessManagerApproval.value) {
        menus.push({
            title: "Persetujuan Manager",
            url: route("persetujuan.manager.index"),
            icon: IconSettingsAutomation,
            root: "PersetujuanManager",
        });
    }

    if (userRoles.value.includes('admin')) {
        menus.push({
            title: "Pengajuan SSO",
            url: route("sso-applications.index"),
            icon: IconUsers,
            root: "Master/Users/SsoApplications",
        });
    }

    menus.push({
        title: "Daftar Pengguna",
        url: route("daftar.pengguna.index"),
        icon: IconUsers,
        root: "DaftarPengguna",
    });

    return menus;
});

// Data untuk menu Master
const masterData = [
    {
        name: "Pengguna",
        url: route("users.index"),
        icon: IconUsers,
        root: "Master/Users",
    },
    {
        name: "Jabatan",
        url: route("roles.index"),
        icon: IconHierarchy,
        root: "Master/Roles",
    },
    {
        name: "Departemen",
        url: route("departemens.index"),
        icon: IconBuilding,
        root: "Master/Departemen",
    },
    {
        name: "Sub Departemen",
        url: route("sub.departemens.index"),
        icon: IconGitMerge,
        root: "Master/SubDepartemen",
    },
];
</script>

<template>
    <Sidebar collapsible="offcanvas">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        as-child
                        class="data-[slot=sidebar-menu-button]:!p-1.5"
                    >
                        <Link
                            :href="route('dashboard')"
                            as="button"
                            class="w-full text-left"
                        >
                            <IconInnerShadowTop class="!size-5" />
                            <span class="font-bold tracking-tight">Sisamsul</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="filteredNavMain" />
            <Master v-if="isAdminOrQC" :items="masterData" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser :user="user" />
        </SidebarFooter>
    </Sidebar>
</template>
