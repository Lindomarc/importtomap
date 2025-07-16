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

<script setup>
import { ref, onMounted } from 'vue';
import { Marker } from "vue3-google-map";

// Estado reativo para as cidades
const cities = ref([]);

// Função para buscar dados da API
const fetchCities = async () => {
  try {
    const response = await fetch('/api/campanhas/list');
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    // Mapear os dados da API para o formato esperado
    cities.value = result.data.map((campanha, index) => ({
      id: campanha.id || index + 1,
      name: campanha.name,
      type: campanha.type,
      position: {
        lat: parseFloat(campanha.lat),
        lng: parseFloat(campanha.lng)
      },
      info: campanha.info,
      color: campanha.color,
      // Incluir dados originais da campanha
      campanha: campanha
    }));
  } catch (error) {
    console.error('Erro ao buscar campanhas:', error);
    // Fallback para dados estáticos em caso de erro
    cities.value = [];
  }
};

// Função para criar o SVG customizado
const createCustomSVG = (color = "#2563eb", type = "placas") => {
    // Define o tipo da campanha e o mapeamento correspondente
const typeMapping = {
  radios: 'R',
  placas: 'P',
  portais: 'N',
};

// Obtém o valor de $txt com base no tipo da campanha
const text = typeMapping[type] || ''; // Caso o tipo não esteja no mapeamento, retorna uma string vazia

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
              font-family="Arial, sans-serif" font-size="8" font-weight="bold"></text>
      </svg>
    `)}`;
};

// Função para criar ícone de cidade
const createCityIcon = (number) => {
  return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
        <!-- Sombra -->
        <ellipse cx="16" cy="40" rx="8" ry="2" fill="#000000" opacity="0.2"/>
        
        <!-- Corpo do marcador -->
        <path d="M16 2 C10.48 2 6 6.48 6 12 C6 18 16 38 16 38 C16 38 26 18 26 12 C26 6.48 21.52 2 16 2 Z" 
              fill="#dc2626" 
              stroke="#ffffff" 
              stroke-width="2"/>
        
        <!-- Círculo interno -->
        <circle cx="16" cy="12" r="6" fill="#ffffff" opacity="0.9"/>
        
        <!-- Número -->
        <text x="16" y="16" text-anchor="middle" fill="#dc2626" 
              font-family="Arial, sans-serif" font-size="8" font-weight="bold">${number}</text>
      </svg>
    `)}`;
};

// Função para obter as opções do marcador
const getMarkerOptions = (city) => {
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

// Função para abrir o drawer
const openDrawer = (city) => {
  console.log("city clicada:", city);
  // Sua lógica para abrir o drawer
};

// Buscar dados da API quando o componente for montado
onMounted(() => {
  fetchCities();
});

// Expor função para permitir recarregar dados externamente
defineExpose({
  fetchCities
});
</script>

<style>
/* Estilos adicionais se necessário */
</style>