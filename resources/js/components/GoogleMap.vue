<template>
  <div class="q-pa-md">
    <div class="relative" style="height: calc(100vh - 120px)">

        <!-- Conteúdo do Sidebar -->
        <GoogleMapSidebarR :selectedLocation="selectedLocation"  :isOpen="isSidebarOpen" @close="isSidebarOpen = false" />
   
      <!-- Loading indicator -->
      <div v-if="loading" class="absolute top-4 left-4 z-40 bg-white p-3 rounded-lg shadow-lg">
        <div class="flex items-center space-x-2">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
          <span class="text-sm text-gray-700">Carregando Mesoregiões...</span>
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="absolute top-4 left-4 z-40 bg-red-100 p-3 rounded-lg shadow-lg max-w-sm">
        <div class="flex items-center space-x-2">
          <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span class="text-sm text-red-700">{{ error }}</span>
        </div>
      </div>

      <!-- Debug info -->
      <div v-if="showDebug" class="absolute bottom-4 left-4 z-40 bg-yellow-100 p-3 rounded-lg shadow-lg max-w-sm">
        <div class="text-xs text-yellow-800">
          <div>Map Ready: {{ mapReady }}</div>
          <div>Map Loaded: {{ mapLoaded }}</div>
          <div>GeoJSON Status: {{ geoJsonStatus }}</div>
          <button @click="manualLoad" class="mt-2 bg-yellow-500 text-white px-2 py-1 rounded text-xs">
            Carregar Manualmente
          </button>
        </div>
      </div>
      <GoogleMap
        ref="googleMapRef"
        map-id="e73daaa552348451662ec44b"
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
        :map-type-control="false"
        gesture-handling="greedy"
        @loaded="onMapLoaded"
        @ready="onMapReady"
        @click="onMapClick"
        @idle="onMapIdle"
      >
        <!-- <MarkerPlacas @marker-clicked="handleMarkerClick" /> -->
        <MarkerCity
          :import-id="importId"
          @marker-clicked="handleMarkerClick" 
        />
      </GoogleMap>
    </div>
  </div>
</template>

<script setup lang="ts">
import { GoogleMap } from "vue3-google-map";
import { ref, onMounted, nextTick, watch } from "vue";
import MarkerPlacas from "./MarkerPlacas.vue";
import MarkerCity from "./MarkerCity.vue";
import GoogleMapSidebarR from "./GoogleMapSidebarR.vue";
const props = defineProps({
  importId: {
    type: String,
    default: null
  }
})


const selectedMap = ref<string>("roadmap");
const googleMapRef = ref<any | null>(null);
const mapCenter = ref({
  lat: -24.7461133,
  lng: -51.7733505,
});
const zoom = ref(8);
const loading = ref(false);
const error = ref<string | null>(null);
const showDebug = ref(false); // Ativar debug

// Estados de debug
const mapReady = ref(false);
const mapLoaded = ref(false);
const geoJsonStatus = ref('waiting');

const apiKey =
  import.meta.env.VITE_GOOGLE_MAPS_API_KEY || "AIzaSyCCBRQh6dU4DSd1kRrXeM5w0oYAnGa9vvs";

// Controle da Drawer
const isSidebarOpen = ref(false);

function toggleSidebar() {
  isSidebarOpen.value = !isSidebarOpen.value;
}
const selectedLocation = ref<{
  name: string;
  info: string;
  total_liquido: string;
  position: { lat: number; lng: number };
  type: string;
} | null>(null);

// Função para lidar com o clique no marcador
const handleMarkerClick = (item: {
  name: string;
  info: string;
  total_liquido: string;
  position: { lat: number; lng: number };
  type: string;
}) => {
  selectedLocation.value = item;
  isSidebarOpen.value = true;
};

// Tipagem para o GeoJSON
interface GeoJsonFeature {
  type: string;
  properties: { name: string };
  geometry: {
    type: string;
    coordinates: number[][][];
  };
}

interface GeoJson {
  type: string;
  features: GeoJsonFeature[];
}

// Função para tentar carregar o GeoJSON
const attemptGeoJsonLoad = async () => {
  // console.log('🔄 Tentando carregar GeoJSON...');
  
  // Aguarda um pouco para garantir que o mapa esteja pronto
  await new Promise(resolve => setTimeout(resolve, 2000));
  
  // Verifica se temos acesso ao mapa
  let mapInstance = null;
  
  if (googleMapRef.value && googleMapRef.value.map) {
    mapInstance = googleMapRef.value.map;
    // console.log('✅ Mapa obtido via googleMapRef.value.map');
  } else if (googleMapRef.value && googleMapRef.value.$mapInstance) {
    mapInstance = googleMapRef.value.$mapInstance;
    // console.log('✅ Mapa obtido via googleMapRef.value.$mapInstance');
  } else {
    // console.log('❌ Não foi possível obter a instância do mapa');
    return;
  }
  
  if (mapInstance) {
    await onMapLoaded(mapInstance);
  }
};

// Função chamada quando o mapa está pronto
const onMapReady = async (mapInstance: any) => {
  // console.log('🚀 Map Ready Event - Instância do mapa:', mapInstance);
  mapReady.value = true;
  
  // Tenta carregar o GeoJSON após um delay
  setTimeout(async () => {
    await attemptGeoJsonLoad();
  }, 1000);
};

// Função chamada quando o mapa é carregado
const onMapLoaded = async (map: any): Promise<void> => {
  // console.log('🗺️ Map Loaded Event - Mapa carregado:', map);
  mapLoaded.value = true;
  
  try {
    loading.value = true;
    error.value = null;
    geoJsonStatus.value = 'loading';
    
    // Verifica se é uma instância do Google Maps
    if (!window.google || !window.google.maps) {
      throw new Error('Google Maps API não está disponível');
    }
    
    // Aguarda um pouco para garantir que tudo esteja pronto
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Encontra a instância correta do mapa
    let mapInstance = map;
    if (map.map && map.map instanceof window.google.maps.Map) {
      mapInstance = map.map;
    } else if (!(map instanceof window.google.maps.Map)) {
      // Tenta obter via ref
      if (googleMapRef.value && googleMapRef.value.map) {
        mapInstance = googleMapRef.value.map;
      } else {
        throw new Error('Não foi possível obter a instância do Google Maps');
      }
    }
    
    // console.log('✅ Instância do mapa obtida:', mapInstance);
    
    // Verifica se tem data layer
    if (!mapInstance.data) {
      throw new Error('Data Layer não está disponível');
    }
    
    const dataLayer = mapInstance.data;
    // console.log('✅ Data Layer obtido:', dataLayer);
    
    // Carrega o GeoJSON
    // console.log('📁 Carregando arquivo GeoJSON...');
    // const geoJsonData = await fetchGeoJson('/storage/geo/41-mun.json');
    const geoJsonData = await fetchGeoJson('/storage/geo/mesorregioes_intermediaria.json');
    
    await loadBrazilBoundaries(dataLayer, geoJsonData);
    
    geoJsonStatus.value = 'loaded';
    // console.log('🎉 Fronteiras carregadas com sucesso!');
    
  } catch (err) {
    console.error('💥 Erro ao carregar GeoJSON:', err);
    error.value = `Erro ao carregar fronteiras: ${err instanceof Error ? err.message : 'Erro desconhecido'}`;
    geoJsonStatus.value = 'error';
  } finally {
    loading.value = false;
  }
};

// Eventos adicionais do mapa
const onMapClick = (event: any) => {
  console.log('🖱️ Map Click:', event);
};

const onMapIdle = async () => {
  // console.log('😴 Map Idle - Mapa está inativo');
  
  // Se o GeoJSON ainda não foi carregado, tenta carregar
  if (geoJsonStatus.value === 'waiting' && !loading.value) {
    // console.log('🔄 Tentando carregar GeoJSON no evento idle...');
    await attemptGeoJsonLoad();
  }
};

// Função para carregar manualmente
const manualLoad = async () => {
  // console.log('🔧 Carregamento manual iniciado');
  await attemptGeoJsonLoad();
};

// Função para buscar o GeoJSON do servidor
const fetchGeoJson = async (url: string): Promise<GeoJson> => {
  // console.log('📡 Fazendo fetch do GeoJSON:', url);
  
  try {
    const response = await fetch(url);
    // console.log('📡 Response status:', response.status);
    
    if (!response.ok) {
      throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    // console.log('📄 GeoJSON carregado com', data.features?.length, 'features');
    
    // Validação básica do GeoJSON
    if (!data || !data.features || !Array.isArray(data.features)) {
      throw new Error('Formato de GeoJSON inválido');
    }
    
    return data as GeoJson;
    
  } catch (fetchError) {
    // console.error('💥 Erro no fetch:', fetchError);
    
    // Tenta URLs alternativas
    const alternativeUrls = [
      '/public/storage/geo/mesorregioes_intermediaria.json',
    ];
    
    for (const altUrl of alternativeUrls) {
      try {
        // console.log('🔄 Tentando URL alternativa:', altUrl);
        const response = await fetch(altUrl);
        if (response.ok) {
          const data = await response.json();
          // console.log('✅ Sucesso com URL alternativa:', altUrl);
          return data as GeoJson;
        }
      } catch (altError) {
        // console.log('❌ Falha com URL alternativa:', altUrl);
      }
    }
    
    throw fetchError;
  }
};

// Carrega as fronteiras dos municípios
const loadBrazilBoundaries = async (dataLayer: any, geoJsonData: GeoJson): Promise<void> => {
  return new Promise((resolve, reject) => {
    try {
      // console.log('🎨 Adicionando GeoJSON ao mapa...');
      
      // Adiciona os dados à camada Data Layer
      const features = dataLayer.addGeoJson(geoJsonData);
      // console.log('✅ Features adicionadas:', features.length);
      
      // Define o estilo das fronteiras
      dataLayer.setStyle({
        fillColor: "rgba(255, 0, 0, 0.1)",
        fillOpacity: 0.1,
        strokeColor: "#6e7172",
        strokeWeight: '1',
        strokeOpacity: 0.8,
      });
      
      // console.log('🎨 Estilos aplicados');
      
      // Adiciona event listeners para interações
      dataLayer.addListener('mouseover', (event: any) => {
        dataLayer.revertStyle();
        dataLayer.overrideStyle(event.feature, {
          fillOpacity: 0.1,
          strokeWeight: 3,
          strokeColor: "#0000FF",
        });
      });
      
      dataLayer.addListener('mouseout', () => {
        dataLayer.revertStyle();
      });
      
      // Adiciona click listener para mostrar informações
      dataLayer.addListener('click', (event: any) => {
        const feature = event.feature;
        const municipio = feature.getProperty('name') || 
                         feature.getProperty('NM_MUN') || 
                         feature.getProperty('NM_MUNICIP') ||
                         'Município';
        
        // console.log('🖱️ Clicou no município:', municipio);
        
        // Lista todas as propriedades disponíveis
        const properties: { key: string; value: any; }[] = [];
        feature.forEachProperty((value: any, key: string) => {
          properties.push({ key, value });
        });
        // console.log('🖱️ Propriedades:', properties);
      });
      
      resolve();
      
    } catch (err) {
      console.error('💥 Erro ao carregar fronteiras:', err);
      reject(err);
    }
  });
};

// Watcher para mudanças no googleMapRef
watch(googleMapRef, async (newVal) => {
  if (newVal && newVal.map && geoJsonStatus.value === 'waiting') {
    // console.log('👀 GoogleMapRef mudou, tentando carregar GeoJSON...');
    await new Promise(resolve => setTimeout(resolve, 2000));
    await attemptGeoJsonLoad();
  }
}, { immediate: true });

// Tenta carregar após montagem
onMounted(async () => {
  // console.log('🔧 Componente montado');
  
  // Aguarda um pouco e tenta carregar
  await new Promise(resolve => setTimeout(resolve, 3000));
  
  if (geoJsonStatus.value === 'waiting') {
    // console.log('🔄 Tentando carregar GeoJSON após montagem...');
    await attemptGeoJsonLoad();
  }
});


</script>