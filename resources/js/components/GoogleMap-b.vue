<template>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto relative">
            <div class="w-full h-96 md:h-[500px] lg:h-[calc(100vh-150px)] relative">
                <div 
                    ref="mapContainer"
                    class="w-full h-full rounded-lg shadow-lg"
                    style="min-height: 400px;"
                />
                
                <!-- Sidebar -->
                <div 
                    :class="[
                        'fixed top-0 right-0 h-full w-80 bg-white shadow-2xl transform transition-transform duration-300 ease-in-out z-50',
                        sidebarOpen ? 'translate-x-0' : 'translate-x-full'
                    ]"
                >
                    <!-- Header do Sidebar -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ selectedLocation?.type === 'city' ? 'Cidade' : 'Placa' }}
                        </h2>
                        <button 
                            @click="closeSidebar"
                            class="p-2 hover:bg-gray-100 rounded-full transition-colors"
                        >
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
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
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Lat: {{ selectedLocation.position.lat }}
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
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
                                        : 'bg-blue-100 text-blue-800'
                                ]"
                            >
                                {{ selectedLocation.type === 'city' ? '🏙️ Cidade' : '📍 Placa' }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Overlay para fechar o sidebar -->
                <div 
                    v-if="sidebarOpen"
                    @click="closeSidebar"
                    class="fixed inset-0 bg-opacity-50 z-40"
                />
            </div>
        </div>
    </template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
// Configuração da API key
const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || "AIzaSyCCBRQh6dU4DSd1kRrXeM5w0oYAnGa9vvs";

// Configurações do mapa
const center = { lat: -25.424, lng: -49.273 }; // Curitiba, PR (centro do mapa)
const zoom = 8; // Zoom menor para ver as 3 cidades

// Lista de cidades do Paraná com marcadores
const cities = [
    {
        name: 'Curitiba',
        position: { lat: -25.424, lng: -49.273 },
        info: 'Capital do Paraná'
    },
    {
        name: 'Londrina',
        position: { lat: -23.311, lng: -51.169 },
        info: 'Cidade do Norte do Paraná'
    },
    {
        name: 'Maringá',
        position: { lat: -23.425, lng: -51.936 },
        info: 'Cidade Canção'
    }
];

// Pontos de interesse em Curitiba (marcadores azuis)
const placasPoints = [
    {
        "name": "PR 364 - Laranjeiras do Sul",
        "info": "Painel 12,00 x 5,00",
        "position": { "lat": -25.3944242, "lng": -51.5285082 }
    },
    {
        "name": "PR-466 - Pitanga",
        "info": "Painel 8,00 x 4,00",
        "position": { "lat": -24.7461133, "lng": -51.7733505 }
    },
    {
        "name": "PR-092 - Contorno de Andirá",
        "info": "Painel 9,00 x 3,00",
        "position": { "lat": -23.0615683, "lng": -50.2370097 }
    },
];

// Refs
const mapContainer = ref<HTMLElement | null>(null);
const sidebarOpen = ref(false);
const selectedLocation = ref<any>(null);
let map: google.maps.Map | null = null;
let markers: google.maps.Marker[] = [];
let infoWindows: google.maps.InfoWindow[] = [];

// Função para inicializar o mapa
const initMap = () => {
    if (!mapContainer.value) return;

    // Configurações do mapa
    const mapOptions: google.maps.MapOptions = {
        center: center,
        zoom: zoom,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        disableDefaultUI: true, // Remove todos os controles padrão
        styles: [
            {
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }]
            }
        ]
    };

    // Criar o mapa
    map = new google.maps.Map(mapContainer.value, mapOptions);

    // Criar marcadores para cada cidade
    cities.forEach((city, index) => {
        const svgIcon = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="37" viewBox="0 0 26 37">
            <!-- Formato de gota do marcador mais esticado -->
            <path d="M12 2 C8.13 2 5 5.13 5 9 C5 13 12 26 12 26 C12 26 19 13 19 9 C19 5.13 15.87 2 12 2 Z" 
                fill="red" stroke="red" stroke-width="6"/>
            
            <!-- Círculo interno para o número -->
            <circle cx="12" cy="9" r="4" fill="#ffffff" opacity="0.9"/>
            
            <!-- Número no centro -->
            <text x="12" y="11" text-anchor="middle" fill="#2563eb" font-family="Arial, sans-serif" font-size="6" font-weight="bold">o</text>
        </svg>
        `)}`;
        const marker = new google.maps.Marker({
            position: city.position,
            map: map,
            title: city.name,
            clickable: true,
            icon: {
                url: svgIcon,
                scaledSize: new google.maps.Size(26, 49),
                anchor: new google.maps.Point(12, 12)
            }
        });

        // Criar InfoWindow para cada marcador
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px;">
                    <h3 style="margin: 0 0 5px 0; color: #333;">${city.name}</h3>
                    <p style="margin: 0; color: #666;">${city.info}</p>
                </div>
            `
        });

        // Adicionar listener para clique no marcador
        marker.addListener('click', () => {
            selectedLocation.value = {
                ...city,
                type: 'city'
            };
            sidebarOpen.value = true;
            console.log(`Marcador clicado: ${city.name}`);
        });

        markers.push(marker);
        infoWindows.push(infoWindow);
    });

    // Criar marcadores azuis para pontos de interesse em Curitiba
    placasPoints.forEach((point, index) => {
        const svgIcon = `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="37" viewBox="0 0 26 37">
            <!-- Formato de gota do marcador mais esticado -->
            <path d="M12 2 C8.13 2 5 5.13 5 9 C5 13 12 26 12 26 C12 26 19 13 19 9 C19 5.13 15.87 2 12 2 Z" 
                fill="#2563eb" stroke="#1d4ed8" stroke-width="2"/>
            
            <!-- Círculo interno para o número -->
            <circle cx="12" cy="9" r="4" fill="#ffffff" opacity="0.9"/>
            
            <!-- Número no centro -->
            <text x="12" y="11" text-anchor="middle" fill="#2563eb" font-family="Arial, sans-serif" font-size="6" font-weight="bold">o</text>
        </svg>
        `)}`;

        const marker = new google.maps.Marker({
            position: point.position,
            map: map,
            title: point.name,            clickable: true,

            icon: {
                url: svgIcon,
                scaledSize: new google.maps.Size(26, 49),
                anchor: new google.maps.Point(12, 12)
            }
        });
        // Criar InfoWindow para cada marcador azul
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px;">
                    <h3 style="margin: 0 0 5px 0; color: #2563eb;">${point.name}</h3>
                    <p style="margin: 0; color: #666;">${point.info}</p>
                </div>
            `
        });

        // Adicionar listener para clique no marcador
        marker.addListener('click', () => {
            selectedLocation.value = {
                ...point,
                type: 'point'
            };
            sidebarOpen.value = true;
            console.log(`Placa clicado: ${point.name}`);
        });

        markers.push(marker);
        infoWindows.push(infoWindow);
    });

    console.log('Mapa carregado com sucesso!', map);
};

// Função para carregar o script do Google Maps
const loadGoogleMapsScript = () => {
    return new Promise<void>((resolve, reject) => {
        // Verificar se o script já foi carregado
        if (window.google && window.google.maps) {
            resolve();
            return;
        }

        // Criar o script
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=initGoogleMaps`;
        script.async = true;
        script.defer = true;

        // Definir a função de callback global
        (window as any).initGoogleMaps = () => {
            resolve();
        };

        script.onerror = () => {
            reject(new Error('Erro ao carregar o Google Maps'));
        };

        document.head.appendChild(script);
    });
};

// Lifecycle hooks
onMounted(async () => {
    try {
        await loadGoogleMapsScript();
        initMap();
    } catch (error) {
        console.error('Erro ao inicializar o mapa:', error);
    }
});

onUnmounted(() => {
    // Cleanup se necessário
    markers.forEach(marker => {
        marker.setMap(null);
    });
});

// Função para fechar o sidebar
const closeSidebar = () => {
    sidebarOpen.value = false;
    selectedLocation.value = null;
};
</script>