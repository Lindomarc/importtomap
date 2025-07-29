<template>
  <!-- Sidebar -->
  <div
    :class="[
      'fixed top-0 right-0 h-full w-80 bg-white shadow-2xl transform transition-transform duration-300 ease-in-out z-50',
      isOpen ? 'translate-x-0' : 'translate-x-full',
    ]"
  >
    <!-- Header do Sidebar -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200">
      <h2 class="text-lg font-semibold text-gray-800">
        Detalhes
      </h2>
      <button
        @click="closeSidebar"
        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
      >
        <svg
          class="w-5 h-5 text-gray-600"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          ></path>
        </svg>
      </button>
    </div>

    <!-- Conteúdo do Sidebar -->
    <div class="p-4" v-if="selectedLocation">
      <div class="mb-4">
        <h3 class="text-xl font-bold text-gray-800 mb-2">
          {{ selectedLocation.name }}
        </h3>
        <p class="text-gray-600">
          {{ selectedLocation.info }}
        </p>
      </div>
      
      <div class="mb-4">
        <p class="text-gray-600 font-semibold">
          {{ formatarEmReais(selectedLocation.total_liquido) }}
        </p>
      </div>

      <div class="space-y-3">
        <div class="flex items-center text-sm text-gray-500">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
            ></path>
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
            ></path>
          </svg>
          Lat: {{ selectedLocation.position.lat }}
        </div>
        
        <div class="flex items-center text-sm text-gray-500">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
            ></path>
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
            ></path>
          </svg>
          Lng: {{ selectedLocation.position.lng }}
        </div>
      </div>

      <div class="mt-6 pt-4 border-t border-gray-200">
        <div
          v-if="selectedLocation.type === 'portais'"
          class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        >
          Portal de Notícias
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Position {
  lat: number;
  lng: number;
}

interface Location {
  name: string;
  info: string;
  total_liquido: number | string;
  position: Position;
  type: string;
}

const props = defineProps<{
  selectedLocation: Location | null;
  isOpen: boolean;
}>();

const emit = defineEmits<{
  close: [];
}>();

function closeSidebar() {
  emit('close');
}

function formatarEmReais(valor: number | string): string {
  // Verifica se o valor é um número válido
  const valorNumerico = parseFloat(valor as string);
  if (isNaN(valorNumerico)) {
    console.error("O valor fornecido não é um número válido.");
    return "R$ 0,00";
  }

  // Converte o valor para um número com duas casas decimais
  const valorFormatadoDecimal = valorNumerico.toFixed(2);

  // Substitui o ponto decimal por vírgula e adiciona separadores de milhar
  const valorFormatado = valorFormatadoDecimal
    .replace('.', ',') // Substitui o ponto por vírgula
    .replace(/\B(?=(\d{3})+(?!\d))/g, '.'); // Adiciona pontos de milhar

  // Retorna o valor formatado com o símbolo R$
  return `R$ ${valorFormatado}`;
}
</script>