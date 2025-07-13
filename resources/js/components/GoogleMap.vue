<template>
  <div class="q-pa-md">
    <div class="relative" style="height: calc(100vh - 120px)">
      <!-- Sidebar -->
      <div
        :class="[
          'fixed top-0 right-0 h-full w-80 bg-white shadow-2xl transform transition-transform duration-300 ease-in-out z-50',
          sidebarOpen ? 'translate-x-0' : 'translate-x-full',
        ]"
      >
        <!-- Header do Sidebar -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ selectedLocation?.type === "city" ? "Cidade" : "Placa" }}
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

          <div class="space-y-3">
            <div class="flex items-center text-sm text-gray-500">
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
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
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
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
              :class="[
                'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                selectedLocation.type === 'city'
                  ? 'bg-red-100 text-red-800'
                  : 'bg-blue-100 text-blue-800',
              ]"
            >
              {{ selectedLocation.type === "city" ? "🏙️ Cidade" : "📍 Placa" }}
            </div>
          </div>
        </div>
      </div>

      <GoogleMap
        ref="googleMapRef"
        map-id="ec854352fd4714b8"
        :api-key="apiKey"
        :libraries="['visualization', 'marker', 'geometry']"
        style="width: 100%; height: 100%"
        :center="mapCenter"
        :zoom="zoom"
        :fullscreen-control="false"
        :zoom-control="false"
        :scale-control="false"
        :street-view-control="false"
        :rotate-control="false"
        :map-type-id="selectedMap"
        :map-type-control="true"
        gesture-handling="greedy"
      >
        <MarkerPlacas @marker-clicked="handleMarkerClick" />
        <MarkerCity @marker-clicked="handleMarkerClick" />
      </GoogleMap>
    </div>
  </div>
</template>

<script setup lang="ts">
import { GoogleMap, Marker } from "vue3-google-map";
import { ref } from "vue";
import MarkerPlacas from "./MarkerPlacas.vue";
import MarkerCity from "./MarkerCity.vue";

const selectedMap = ref("satellite");
const mapCenter = ref({
  lat: -24.7461133,
  lng: -51.7733505,
});
const zoom = ref(8); // Zoom menor para ver as 3 cidades

const apiKey =
  import.meta.env.VITE_GOOGLE_MAPS_API_KEY || "AIzaSyCCBRQh6dU4DSd1kRrXeM5w0oYAnGa9vvs";

// Controle da Drawer
const sidebarOpen = ref(false);

const selectedLocation = ref<{
  name: string;
  info: string;
  position: { lat: number; lng: number };
} | null>(null);

function closeSidebar() {
  sidebarOpen.value = false;
}

const googleMapRef = ref();

// Função para lidar com o clique no marcador
const handleMarkerClick = (item: {
  name: string;
  info: string;
  position: { lat: number; lng: number };
}) => {
  selectedLocation.value = item; // Atualiza o estado com a placa clicada
  sidebarOpen.value = true;
};
</script>
