<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
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
const getProducts = async () => {
    try {
        const response = await axios.get("products/labas");
        console.log(response);
    } catch (error) {
        console.error(error);
    }
};
</script>

<template>
    <div>
        <Head title="Dashboard" />

        <AuthenticatedLayout>
            <template #header>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            </template>

            <div class="py-4">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div
                        class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            {{ $t("you_are_logged_in") }}
                            <h1>{{ $t("welcome") }}</h1>
                        </div>
                        <div
                            class="flex items-center justify-center min-h-screen bg-gradient-to-br from-blue-400 to-purple-600 flex-col"
                        >
                            <h1
                                class="text-5xl font-bold text-white shadow-lg p-4 rounded-lg"
                            >
                                Sveiki atvykę į „Tailwind“!
                            </h1>
                            <button
                                class="btn btn-success bg-slate-700 p-2 rounded text-white"
                                @click="getProducts"
                            >
                                {{ $t("get_products") }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>
