<template>
  <div>
    <Marker
      v-for="city in cities"
      :key="city.name"
      :options="getMarkerOptions(city)"
      @click="$emit('marker-clicked', city)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios"; // Importando Axios
import { Marker } from "vue3-google-map";

// Definição das propriedades (props)
const props = defineProps({
  importId: {
    type: String,
    default: null,
  },
});

// Tipagem para os dados da cidade
interface City {
  id: number;
  name: string;
  type: string;
  total_liquido: number;
  position: {
    lat: number;
    lng: number;
  };
  info: string;
  color: string;
  campanha: Record<string, any>; // Pode ser substituído por uma interface específica se necessário
}

// Estado reativo para as cidades
const cities = ref<City[]>([]);

// Função para buscar dados da API usando Axios
const fetchCities = async (): Promise<void> => {
  try {
    const response = await axios.get("/api/campanhas/list", {
      params: {
        import_id: props.importId, // Passando o importId como parâmetro import_id
      },
    });

    const result = response.data;
    console.log(result);
    // Mapear os dados da API para o formato esperado
    cities.value = result.data.map(
      (campanha: any, index: number): City => ({
        id: campanha.id || index + 1,
        name: campanha.name,
        type: campanha.type,
        total_liquido: campanha.total_liquido,
        position: {
          lat: parseFloat(campanha.lat),
          lng: parseFloat(campanha.lng),
        },
        info: campanha.info,
        color: campanha.color,
        campanha: campanha,
      })
    );
  } catch (error) {
    console.error("Erro ao buscar campanhas:", error);
    // Fallback para dados estáticos em caso de erro
    cities.value = [];
  }
};

// Função para criar o SVG customizado
const createCustomSVG = (color: string = "#2563eb", type: string = "placas"): string => {
  // Define o tipo da campanha e o mapeamento correspondente
  const typeMapping: Record<string, string> = {
    radios: "R",
    placas: "P",
    portais: "N",
  };

  // Obtém o valor de text com base no tipo da campanha
  const text: string = typeMapping[type] || ""; // Caso o tipo não esteja no mapeamento, retorna uma string vazia

  return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
        <!-- Sombra do marcador -->
        <ellipse cx="16" cy="40" rx="8" ry="2" fill="#000000" opacity="0.2"/>
        
        <!-- Corpo principal do marcador -->
        <path d="M16 2 C10.48 2 6 6.48 6 12 C6 18 16 38 16 38 C16 38 26 18 26 12 C26 6.48 21.52 2 16 2 Z" 
              fill="${color}" 
              stroke="#ffffff" 
              stroke-width="2"/>
        
        <!-- Círculo interno -->
        <circle cx="16" cy="12" r="6" fill="#ffffff" opacity="0.9"/>
        
        <!-- Texto ou ícone no centro -->
        <text x="16" y="16" text-anchor="middle" fill="${color}" 
              font-family="Arial, sans-serif" font-size="8" font-weight="bold">${text}</text>
      </svg>
    `)}`;
};

// Função para obter as opções do marcador
const getMarkerOptions = (city: City) => {
  const baseOptions = {
    position: city.position,
    title: city.name,
    clickable: true,
    icon: {
      url: createCustomSVG(city.color, city.type),
    },
  };

  return baseOptions;
};

// Buscar dados da API quando o componente for montado
onMounted(() => {
  fetchCities();
});

// Expor função para permitir recarregar dados externamente
defineExpose({
  fetchCities,
});
</script>

<style>
/* Estilos adicionais se necessário */
</style>
