<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
defineProps({
    test: {
        type: String,
    },
});
// import { useI18n } from "vue-i18n";
import axios from "axios"; // Importuojame axios HTTP užklausoms
// const locale = useI18n();
// const changeLanguage = async (newLocale) => {
//     try {
//         // Išsiunčiame užklausą į Laravel backend, kad išsaugotume kalbos pasirinkimą
//         const resp = await axios.post(route("language.set"), {
//             locale: newLocale,
//         });
//         console.log(resp);
//         // alert(newLocale);
//         // Nustatome naują kalbą Vue.js programėlėje
//         locale.locale.value = newLocale;

//         // Atnaujiname Inertia puslapį su naujais props (vertimais)
//         // Tai užtikrins, kad Laravel atsakys su naujais vertimais
//         router.reload({ preserveScroll: true }); // Arba router.visit(window.location.href, { preserveScroll: true });
//     } catch (error) {
//         console.error("Klaida keičiant kalbą:", error);
//     }
// };
// const getProducts = async () => {
//     try {
//         const response = await axios.get("products/labas");
//         console.log(response);
//     } catch (error) {
//         console.error(error);
//     }
// };
</script>

<template>
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <Head title="TestPage" />
        <AuthenticatedLayout>
            <div
                class="text-5xl flex flex-col items-center justify-top min-h-screen"
            >
                <h1>This is test page</h1>
                <div>{{ test }}</div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>
