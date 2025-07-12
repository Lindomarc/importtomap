<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    /**
     * Lista de breadcrumbs para navegação.
     */
    breadcrumbs?: BreadcrumbItemType[];
}

// Define valores padrão para as props
withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Slot para injetar o conteúdo da página -->
        <slot />
    </AppLayout>
</template>

<style scoped>
/* Estilos específicos do layout, se necessário */
</style>