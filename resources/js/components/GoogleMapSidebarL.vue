<template>
  <!-- Sidebar de Filtros -->
  <GoogleMapToggleButton  :isSidebarOpen="isOpen" @toggle="toggleSidebar" />
  <div
    :class="[
      'fixed top-0 left-0 h-full w-80 bg-background border-r shadow-2xl transform transition-transform duration-300 ease-in-out z-50',
      isOpen ? 'translate-x-0' : '-translate-x-full',
    ]"
  >
    <!-- Header do Sidebar -->
    <div class="flex items-center justify-between p-4 border-b">
      <h2 class="text-lg font-semibold">
        Filtros
      </h2>
      <Button
        variant="ghost"
        size="sm"
        @click="closeSidebar"
        class="h-8 w-8 p-0"
      >
        <svg
          class="w-4 h-4"
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
      </Button>
    </div>

    <!-- Conteúdo do Sidebar -->
    <div class="p-4 space-y-6 overflow-y-auto h-full pb-20">
      
      <!-- Filtro de Cidade -->
      <div class="space-y-2">
        <Label for="city-filter">Cidade</Label>
        <div class="relative">
          <Input
            id="city-filter"
            v-model="filters.city"
            @input="applyFilters"
            @focus="showCityDropdown = true"
            @blur="hideCityDropdown"
            placeholder="Digite ou selecione uma cidade"
            class="pr-8"
          />
          <Button
            v-if="filters.city"
            variant="ghost"
            size="sm"
            @click="clearCity"
            class="absolute right-1 top-1/2 transform -translate-y-1/2 h-6 w-6 p-0 hover:bg-muted"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </Button>
          <!-- Dropdown de opções -->
          <div
            v-if="showCityDropdown && filteredCities.length > 0"
            class="absolute z-10 w-full mt-1 bg-popover border rounded-md shadow-lg max-h-40 overflow-y-auto"
          >
            <div
              v-for="city in filteredCities"
              :key="city"
              @mousedown="selectCity(city)"
              class="px-3 py-2 hover:bg-accent hover:text-accent-foreground cursor-pointer text-sm"
            >
              {{ city }}
            </div>
          </div>
        </div>
      </div>

      <!-- Filtro de Estado -->
      <div class="space-y-2">
        <Label for="state-filter">Estado</Label>
        <div class="relative">
          <Input
            id="state-filter"
            v-model="filters.state"
            @input="applyFilters"
            @focus="showStateDropdown = true"
            @blur="hideStateDropdown"
            placeholder="Digite ou selecione um estado"
            class="pr-8"
          />
          <Button
            v-if="filters.state"
            variant="ghost"
            size="sm"
            @click="clearState"
            class="absolute right-1 top-1/2 transform -translate-y-1/2 h-6 w-6 p-0 hover:bg-muted"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </Button>
          <!-- Dropdown de opções -->
          <div
            v-if="showStateDropdown && filteredStates.length > 0"
            class="absolute z-10 w-full mt-1 bg-popover border rounded-md shadow-lg max-h-40 overflow-y-auto"
          >
            <div
              v-for="state in filteredStates"
              :key="state"
              @mousedown="selectState(state)"
              class="px-3 py-2 hover:bg-accent hover:text-accent-foreground cursor-pointer text-sm"
            >
              {{ state }}
            </div>
          </div>
        </div>
      </div>

      <!-- Filtro de Tipo -->
      <div class="space-y-2">
        <Label for="type-filter">Tipo</Label>
        <div class="relative">
          <Input
            id="type-filter"
            v-model="filters.type"
            @input="applyFilters"
            @focus="showTypeDropdown = true"
            @blur="hideTypeDropdown"
            placeholder="Digite ou selecione um tipo"
            class="pr-8"
          />
          <Button
            v-if="filters.type"
            variant="ghost"
            size="sm"
            @click="clearType"
            class="absolute right-1 top-1/2 transform -translate-y-1/2 h-6 w-6 p-0 hover:bg-muted"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </Button>
          <!-- Dropdown de opções -->
          <div
            v-if="showTypeDropdown && filteredTypes.length > 0"
            class="absolute z-10 w-full mt-1 bg-popover border rounded-md shadow-lg max-h-40 overflow-y-auto"
          >
            <div
              v-for="type in filteredTypes"
              :key="type"
              @mousedown="selectType(type)"
              class="px-3 py-2 hover:bg-accent hover:text-accent-foreground cursor-pointer text-sm"
            >
              {{ type }}
            </div>
          </div>
        </div>
      </div>

      <!-- Filtro de Data -->
      <div class="space-y-2">
        <Label>Período</Label>
        <div class="space-y-3">
          <div class="space-y-1">
            <Label for="date-from" class="text-xs text-muted-foreground">Data inicial</Label>
            <Input
              id="date-from"
              type="date"
              v-model="filters.dateFrom"
              @change="applyFilters"
            />
          </div>
          <div class="space-y-1">
            <Label for="date-to" class="text-xs text-muted-foreground">Data final</Label>
            <Input
              id="date-to"
              type="date"
              v-model="filters.dateTo"
              @change="applyFilters"
            />
          </div>
        </div>
      </div>

      <!-- Resumo dos Filtros Ativos -->
      <div v-if="hasActiveFilters" class="pt-4 border-t">
        <h3 class="text-sm font-medium mb-2">Filtros Ativos:</h3>
        <div class="space-y-1 text-xs text-muted-foreground">
          <div v-if="filters.city">Cidade: {{ filters.city }}</div>
          <div v-if="filters.state">Estado: {{ filters.state }}</div>
          <div v-if="filters.type">Tipo: {{ filters.type }}</div>
          <div v-if="filters.dateFrom">De: {{ formatDate(filters.dateFrom) }}</div>
          <div v-if="filters.dateTo">Até: {{ formatDate(filters.dateTo) }}</div>
        </div>
      </div>

      <!-- Botões de Ação -->
      <div class="pt-4 space-y-2">
        <Button
          variant="outline"
          @click="clearFilters"
          class="w-full"
        >
          Limpar Filtros
        </Button>
        
        <div class="text-center text-xs text-muted-foreground pt-2">
          {{ filteredCount }} de {{ totalCount }} itens
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, watch, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GoogleMapToggleButton from './GoogleMapToggleButton.vue';

interface Address {
  id: number;
  city: string;
  state: string;
  street: string | null;
  number: string | null;
  cep: string | null;
  lat: string;
  lng: string;
  created_at: string;
  updated_at: string;
}

interface Campanha {
  name: string;
  info: string;
  color: string;
  type: string;
  lat: string;
  lng: string;
  total_liquido: number;
  created_at: string;
  address: Address;
}

interface Location {
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
  campanha: Campanha;
}

interface Filters {
  city: string;
  state: string;
  type: string;
  dateFrom: string;
  dateTo: string;
}

const props = defineProps<{
  data: Location[];
}>();
const isOpen = ref(true);

const emit = defineEmits<{
  close: [];
  filtersChanged: [filteredData: Location[]];
}>();

const filters = reactive<Filters>({
  city: '',
  state: '',
  type: '',
  dateFrom: '',
  dateTo: ''
});

// Estados para controlar os dropdowns
const showCityDropdown = ref(false);
const showStateDropdown = ref(false);
const showTypeDropdown = ref(false);

// Computed properties para opções filtradas
const filteredCities = computed(() => {
  if (!filters.city) return uniqueCities.value;
  return uniqueCities.value.filter(city => 
    city.toLowerCase().includes(filters.city.toLowerCase())
  );
});

const filteredStates = computed(() => {
  if (!filters.state) return uniqueStates.value;
  return uniqueStates.value.filter(state => 
    state.toLowerCase().includes(filters.state.toLowerCase())
  );
});

const filteredTypes = computed(() => {
  if (!filters.type) return uniqueTypes.value;
  return uniqueTypes.value.filter(type => 
    type.toLowerCase().includes(filters.type.toLowerCase())
  );
});

// Computed properties para opções únicas
const uniqueCities = computed(() => {
  const cities = new Set<string>();
  props.data.forEach(item => {
    if (item.campanha.address.city) {
      cities.add(item.campanha.address.city);
    }
  });
  return Array.from(cities).sort();
});

const uniqueStates = computed(() => {
  const states = new Set<string>();
  props.data.forEach(item => {
    if (item.campanha.address.state) {
      states.add(item.campanha.address.state);
    }
  });
  return Array.from(states).sort();
});

const uniqueTypes = computed(() => {
  const types = new Set<string>();
  props.data.forEach(item => {
    if (item.type) {
      types.add(item.type);
    }
  });
  return Array.from(types).sort();
});

// Dados filtrados
const filteredData = computed(() => {
  return props.data.filter(item => {
    // Filtro por cidade
    if (filters.city && item.campanha.address.city !== filters.city) {
      return false;
    }

    // Filtro por estado
    if (filters.state && item.campanha.address.state !== filters.state) {
      return false;
    }

    // Filtro por tipo
    if (filters.type && item.type !== filters.type) {
      return false;
    }

    // Filtro por data
    if (filters.dateFrom || filters.dateTo) {
      const itemDate = new Date(item.campanha.created_at);
      
      if (filters.dateFrom) {
        const fromDate = new Date(filters.dateFrom);
        if (itemDate < fromDate) {
          return false;
        }
      }
      
      if (filters.dateTo) {
        const toDate = new Date(filters.dateTo);
        toDate.setHours(23, 59, 59, 999); // Inclui o dia todo
        if (itemDate > toDate) {
          return false;
        }
      }
    }

    return true;
  });
});

// Verificar se há filtros ativos
const hasActiveFilters = computed(() => {
  return filters.city || filters.state || filters.type || filters.dateFrom || filters.dateTo;
});

// Contadores
const filteredCount = computed(() => filteredData.value.length);
const totalCount = computed(() => props.data.length);

// Função para alternar o estado do sidebar
function toggleSidebar() {
  // console.log(isOpen)
  isOpen.value = !isOpen.value ;
}
// Funções para gerenciar dropdowns
function hideCityDropdown() {
  setTimeout(() => {
    showCityDropdown.value = false;
  }, 150);
}

function hideStateDropdown() {
  setTimeout(() => {
    showStateDropdown.value = false;
  }, 150);
}

function hideTypeDropdown() {
  setTimeout(() => {
    showTypeDropdown.value = false;
  }, 150);
}

// Funções para selecionar opções
function selectCity(city: string) {
  filters.city = city;
  showCityDropdown.value = false;
  applyFilters();
}

function selectState(state: string) {
  filters.state = state;
  showStateDropdown.value = false;
  applyFilters();
}

function selectType(type: string) {
  filters.type = type;
  showTypeDropdown.value = false;
  applyFilters();
}

// Funções para limpar campos individuais
function clearCity() {
  filters.city = '';
  applyFilters();
}

function clearState() {
  filters.state = '';
  applyFilters();
}

function clearType() {
  filters.type = '';
  applyFilters();
}

// Função para fechar o sidebar
function closeSidebar() {
  isOpen.value  = false
  emit('close');
}

// Função para aplicar filtros
function applyFilters() {
  emit('filtersChanged', filteredData.value);
}

// Função para limpar filtros
function clearFilters() {
  filters.city = '';
  filters.state = '';
  filters.type = '';
  filters.dateFrom = '';
  filters.dateTo = '';
  applyFilters();
}

// Função para formatar data
function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString('pt-BR');
}

// Watch para mudanças nos dados
watch(() => props.data, () => {
  applyFilters();
}, { deep: true });

// Watch para mudanças nos filtros
watch(filters, () => {
  applyFilters();
}, { deep: true });
</script>