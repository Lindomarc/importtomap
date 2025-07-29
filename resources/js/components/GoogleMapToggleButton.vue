<template>
  <button
    @click="emitToggle"
    class="absolute top-4 left-4 bg-blue-500 text-white px-4 py-2 rounded-full shadow-lg hover:bg-blue-600 transition-colors z-50"
  >
  Filtros
  </button>
</template>

<script setup lang="ts">
const props = defineProps<{
  isSidebarOpen: boolean;
}>();

const emit = defineEmits<{
  toggle: [];
}>();

// Função para emitir o evento de toggle
function emitToggle() {
  emit('toggle');
}
</script>