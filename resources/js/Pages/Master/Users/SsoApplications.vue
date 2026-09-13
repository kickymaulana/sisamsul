<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

defineOptions({ layout: AuthenticatedLayout });

type Application = { id: number; nik: string; name: string; email: string };
defineProps<{
    applications: { data: Application[]; prev_page_url: string | null; next_page_url: string | null };
    departemens: { id: number; nama: string }[];
    roles: string[];
}>();

const selected = ref<number | null>(null);
const form = useForm({ nik: "", name: "", email: "", whatsapp: "", departemen_id: "", roles: [] as string[] });
const select = (application: Application) => {
    form.reset();
    form.clearErrors();
    selected.value = application.id;
    form.nik = application.nik;
    form.name = application.name;
    form.email = application.email.toLowerCase();
};
const submit = () => {
    form.post(route("sso-applications.approve", selected.value), {
        onSuccess: () => { selected.value = null; form.reset(); },
    });
};
</script>

<template>
    <Head title="Pengajuan SSO" />
    <div class="grid gap-6 p-4 md:p-8">
        <Card>
            <CardHeader><CardTitle>Pengajuan SSO Menunggu Persetujuan</CardTitle></CardHeader>
            <CardContent class="grid gap-4">
                <p v-if="!applications.data.length">Tidak ada pengajuan menunggu persetujuan.</p>
                <div v-for="application in applications.data" :key="application.id" class="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                    <div><p class="font-medium">{{ application.name }} — {{ application.nik }}</p><p class="text-sm text-muted-foreground">{{ application.email }}</p></div>
                    <Button variant="outline" :disabled="form.processing" @click="select(application)">Lengkapi Data</Button>
                </div>
                <div class="flex gap-4">
                    <Link v-if="applications.prev_page_url" :href="applications.prev_page_url">Sebelumnya</Link>
                    <Link v-if="applications.next_page_url" :href="applications.next_page_url">Berikutnya</Link>
                </div>
            </CardContent>
        </Card>
        <Card v-if="selected">
            <CardHeader><CardTitle>Lengkapi dan Setujui Akun</CardTitle></CardHeader>
            <CardContent>
                <form class="grid max-w-xl gap-4" @submit.prevent="submit">
                    <div class="grid gap-2"><Label for="nik">NIK SSO</Label><Input id="nik" v-model="form.nik" readonly /></div>
                    <div class="grid gap-2"><Label for="name">Nama Lengkap</Label><Input id="name" v-model="form.name" required maxlength="255" /></div>
                    <div class="grid gap-2"><Label for="email">Email</Label><Input id="email" v-model="form.email" type="email" required maxlength="255" /></div>
                    <div class="grid gap-2"><Label for="whatsapp">WhatsApp (628…)</Label><Input id="whatsapp" v-model="form.whatsapp" required pattern="628[0-9]{7,12}" /></div>
                    <div class="grid gap-2">
                        <Label for="departemen">Departemen</Label>
                        <select id="departemen" v-model="form.departemen_id" required class="h-10 rounded-md border border-input bg-background px-3">
                            <option disabled value="">Pilih departemen</option>
                            <option v-for="departemen in departemens" :key="departemen.id" :value="departemen.id">{{ departemen.nama }}</option>
                        </select>
                    </div>
                    <fieldset class="grid gap-2">
                        <legend class="mb-2 text-sm font-medium">Jabatan (pilih minimal satu)</legend>
                        <label v-for="role in roles" :key="role" class="flex items-center gap-2 text-sm">
                            <input v-model="form.roles" type="checkbox" :value="role" />{{ role }}
                        </label>
                    </fieldset>
                    <ul v-if="Object.keys(form.errors).length" role="alert" class="text-sm text-destructive">
                        <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                    </ul>
                    <p class="text-sm text-muted-foreground">Akun dibuat setelah persetujuan. NIK tidak dapat diganti atau digabungkan dengan akun lain.</p>
                    <Button type="submit" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : 'Setujui dan Buat Akun' }}</Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
