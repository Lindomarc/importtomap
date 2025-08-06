<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { onMounted, ref, computed, watch } from "vue";
import axios from "axios";
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
  Table,
  TableHeader,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Trash2, Check, X, Edit, Save, Search, Filter, RotateCcw, Upload } from "lucide-vue-next";

// Define os breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dados', href: '/data' },
];
const showFilters = ref(false);

// Interface para os dados da entidade
interface Entity {
  id: number;
  name: string | null;
  razao_social: string | null;
  type: string | null;
  cnpj_cpf: string | null;
  address_id: number | null;
}

// Interface para dados de paginação
interface PaginationData {
  data: Entity[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

// Estado reativo para armazenar as entidades e dados de paginação
const paginationData = ref<PaginationData>({
  data: [],
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
});

const isLoading = ref(false);

// Estados para o modal de edição
const isModalOpen = ref(false);
const isUpdating = ref(false);
const editingEntity = ref<Entity | null>(null);
const editForm = ref({
  name: '',
  razao_social: '',
  type: '',
  cnpj_cpf: '',
  address_id: null,
});

// Estados para o modal de importação
const isImportModalOpen = ref(false);
const importFile = ref<File | null>(null);
const importError = ref<string | null>(null);

// Estados para filtros
const filters = ref({
  search: '',
  name: '',
  razao_social: '',
  type: '',
  cnpj_cpf: '',
});

// Debounce para evitar muitas requisições
let debounceTimer: NodeJS.Timeout | null = null;

// Watch para aplicar filtros automaticamente
watch(filters, () => {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  debounceTimer = setTimeout(() => {
    applyFilters();
  }, 500); // Aguarda 500ms após a última alteração
}, { deep: true });

// Função para aplicar filtros
const applyFilters = () => {
  fetchEntities(1, paginationData.value.per_page);
};

// Função para limpar todos os filtros
const clearFilters = () => {
  filters.value = {
    search: '',
    name: '',
    razao_social: '',
    type: '',
    cnpj_cpf: '',
  };
  // A função fetchEntities será chamada automaticamente pelo watch
};

// Computed para verificar se há filtros ativos
const hasActiveFilters = computed(() => {
  return Object.values(filters.value).some(filter => filter.trim() !== '');
});

// Computed para contar filtros ativos
const activeFiltersCount = computed(() => {
  return Object.values(filters.value).filter(filter => filter.trim() !== '').length;
});

// Função para buscar as entidades do backend
const fetchEntities = async (page: number = 1, perPage: number = 10) => {
  try {
    isLoading.value = true;
    const params: any = {
      page: page,
      per_page: perPage,
    };

    // Adicionar filtros aos parâmetros se não estiverem vazios
    Object.entries(filters.value).forEach(([key, value]) => {
      if (value.trim()) {
        params[key] = value.trim();
      }
    });

    const response = await axios.get("/api/entities", { params });
    paginationData.value = response.data;
  } catch (error) {
    console.error("Erro ao buscar entidades:", error);
  } finally {
    isLoading.value = false;
  }
};

// Função para ir para uma página específica
const goToPage = (page: number) => {
  if (page >= 1 && page <= paginationData.value.last_page) {
    fetchEntities(page, paginationData.value.per_page);
  }
};

// Função para alterar o número de itens por página
const changePerPage = (perPage: number) => {
  fetchEntities(1, perPage);
};

// Computed para gerar array de páginas para navegação
const pageNumbers = computed(() => {
  const pages = [];
  const currentPage = paginationData.value.current_page;
  const lastPage = paginationData.value.last_page;

  let start = Math.max(1, currentPage - 2);
  let end = Math.min(lastPage, currentPage + 2);

  if (end - start < 4) {
    if (start === 1) {
      end = Math.min(lastPage, start + 4);
    } else {
      start = Math.max(1, end - 4);
    }
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  return pages;
});

// Busca os dados ao montar o componente
onMounted(() => {
  fetchEntities();
});

// Função para abrir o modal de edição
const openEditModal = (entity: Entity) => {
  editingEntity.value = entity;
  editForm.value = {
    name: entity.name || '',
    razao_social: entity.razao_social || '',
    type: entity.type || '',
    cnpj_cpf: entity.cnpj_cpf || '',
    address_id: entity.address_id || null,
  };
  isModalOpen.value = true;
};

// Função para fechar o modal
const closeModal = () => {
  isModalOpen.value = false;
  editingEntity.value = null;
  editForm.value = {
    name: '',
    razao_social: '',
    type: '',
    cnpj_cpf: '',
    address_id: null,
  };
};

// Função para salvar as alterações do modal
const saveModalChanges = async () => {
  if (!editingEntity.value) return;
  try {
    isUpdating.value = true;
    await axios.put(`/api/entities/${editingEntity.value.id}`, editForm.value);

    // Atualizar os dados localmente
    const index = paginationData.value.data.findIndex(e => e.id === editingEntity.value!.id);
    if (index !== -1) {
      paginationData.value.data[index] = { ...paginationData.value.data[index], ...editForm.value };
    }

    closeModal();
  } catch (error) {
    console.error("Erro ao atualizar entidade:", error);
  } finally {
    isUpdating.value = false;
  }
};

// Função para deletar uma entidade
const deleteEntity = async (id: number) => {
  if (!confirm("Tem certeza que deseja excluir esta entidade?")) return;
  try {
    isLoading.value = true;
    await axios.delete(`/api/entities/${id}`);

    // Remover a entidade localmente
    paginationData.value.data = paginationData.value.data.filter(e => e.id !== id);
    paginationData.value.total -= 1;
  } catch (error) {
    console.error("Erro ao deletar entidade:", error);
  } finally {
    isLoading.value = false;
  }
};

// Função para abrir o modal de importação
const openImportModal = () => {
  isImportModalOpen.value = true;
};

// Função para fechar o modal de importação
const closeImportModal = () => {
  isImportModalOpen.value = false;
  importFile.value = null;
  importError.value = null;
};

// Função para processar o upload do arquivo
const handleImportSubmit = async () => {
  if (!importFile.value) {
    importError.value = "Por favor, selecione um arquivo para importar.";
    return;
  }

  try {
    const formData = new FormData();
    formData.append('file', importFile.value);

    await axios.post('/api/entities/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    closeImportModal();
    fetchEntities(); // Atualiza a lista após a importação
  } catch (error) {
    console.error("Erro ao importar entidades:", error);
    importError.value = "Ocorreu um erro durante a importação. Tente novamente.";
  }
};

// Função para remover um filtro específico
const removeFilter = (filterKey: keyof typeof filters.value) => {
  filters.value[filterKey] = '';
};
</script>

<template>
  <Head title="Entidades" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="container mx-auto p-4">
        <!-- Botão de Importação -->
        <div class="mb-6 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold text-gray-900">Entidades</h1>
            <div v-if="hasActiveFilters" class="flex items-center gap-2">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ activeFiltersCount }} filtro{{ activeFiltersCount > 1 ? 's' : '' }} ativo{{ activeFiltersCount > 1 ? 's' : '' }}
              </span>
              <Button variant="ghost" size="sm" @click="clearFilters" class="text-gray-500 hover:text-gray-700">
                <RotateCcw class="h-4 w-4" />
              </Button>
            </div>
          </div>
          <Button @click="openImportModal" class="flex items-center gap-2">
            <Upload class="h-4 w-4" /> Importar Entidades
          </Button>
        </div>

        <!-- Seção de Filtros -->
        <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm">
          <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Filtros de Pesquisa</h3>
            <Button 
              variant="ghost" 
              size="sm" 
              @click="showFilters = !showFilters" 
              class="flex items-center gap-2"
              :class="{ 'text-blue-600': hasActiveFilters }"
            >
              <Filter class="h-4 w-4" />
              {{ showFilters ? 'Ocultar' : 'Mostrar' }} Filtros
            </Button>
          </div>
          
          <div v-show="showFilters" class="p-4 space-y-4">
            <!-- Pesquisa Geral -->
            <div class="w-full">
              <Label for="search" class="text-sm font-medium text-gray-700">Pesquisa Geral</Label>
              <div class="relative mt-1">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input 
                  id="search" 
                  v-model="filters.search" 
                  placeholder="Pesquisar em todos os campos..." 
                  class="pl-10" 
                />
                <button 
                  v-if="filters.search"
                  @click="removeFilter('search')"
                  class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                  <X class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Filtros Específicos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <Label for="filter-name" class="text-sm font-medium text-gray-700">Nome</Label>
                <div class="relative mt-1">
                  <Input 
                    id="filter-name" 
                    v-model="filters.name" 
                    placeholder="Filtrar por nome..." 
                  />
                  <button 
                    v-if="filters.name"
                    @click="removeFilter('name')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <div>
                <Label for="filter-razao-social" class="text-sm font-medium text-gray-700">Razão Social</Label>
                <div class="relative mt-1">
                  <Input 
                    id="filter-razao-social" 
                    v-model="filters.razao_social" 
                    placeholder="Filtrar por razão social..." 
                  />
                  <button 
                    v-if="filters.razao_social"
                    @click="removeFilter('razao_social')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <div>
                <Label for="filter-type" class="text-sm font-medium text-gray-700">Tipo</Label>
                <div class="relative mt-1">
                  <Input 
                    id="filter-type" 
                    v-model="filters.type" 
                    placeholder="Filtrar por tipo..." 
                  />
                  <button 
                    v-if="filters.type"
                    @click="removeFilter('type')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <div>
                <Label for="filter-cnpj-cpf" class="text-sm font-medium text-gray-700">CNPJ/CPF</Label>
                <div class="relative mt-1">
                  <Input 
                    id="filter-cnpj-cpf" 
                    v-model="filters.cnpj_cpf" 
                    placeholder="Filtrar por CNPJ/CPF..." 
                  />
                  <button 
                    v-if="filters.cnpj_cpf"
                    @click="removeFilter('cnpj_cpf')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Ações dos Filtros -->
            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
              <div class="text-sm text-gray-500">
                Os filtros são aplicados automaticamente enquanto você digita
              </div>
              <div class="flex items-center gap-2">
                <Button 
                  variant="outline" 
                  size="sm" 
                  @click="clearFilters" 
                  :disabled="!hasActiveFilters"
                  class="flex items-center gap-2"
                >
                  <RotateCcw class="h-4 w-4" />
                  Limpar Todos os Filtros
                </Button>
                <Button 
                  variant="ghost" 
                  size="sm" 
                  @click="showFilters = false"
                  class="flex items-center gap-2"
                >
                  Ocultar Filtros
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Indicadores de filtros ativos -->
        <div v-if="hasActiveFilters" class="mb-4">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-600">Filtros ativos:</span>
            
            <span v-if="filters.search" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded-md text-xs">
              Pesquisa Geral: "{{ filters.search }}"
              <button @click="removeFilter('search')" class="hover:text-blue-600">
                <X class="h-3 w-3" />
              </button>
            </span>
            
            <span v-if="filters.name" class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-800 rounded-md text-xs">
              Nome: "{{ filters.name }}"
              <button @click="removeFilter('name')" class="hover:text-green-600">
                <X class="h-3 w-3" />
              </button>
            </span>
            
            <span v-if="filters.razao_social" class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 text-yellow-800 rounded-md text-xs">
              Razão Social: "{{ filters.razao_social }}"
              <button @click="removeFilter('razao_social')" class="hover:text-yellow-600">
                <X class="h-3 w-3" />
              </button>
            </span>
            
            <span v-if="filters.type" class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 rounded-md text-xs">
              Tipo: "{{ filters.type }}"
              <button @click="removeFilter('type')" class="hover:text-purple-600">
                <X class="h-3 w-3" />
              </button>
            </span>
            
            <span v-if="filters.cnpj_cpf" class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-800 rounded-md text-xs">
              CNPJ/CPF: "{{ filters.cnpj_cpf }}"
              <button @click="removeFilter('cnpj_cpf')" class="hover:text-orange-600">
                <X class="h-3 w-3" />
              </button>
            </span>
          </div>
        </div>

        <!-- Status de Loading e Resultados -->
        <div v-if="isLoading" class="flex items-center justify-center py-8">
          <div class="text-gray-500">Carregando entidades...</div>
        </div>

        <!-- Tabela para exibir os dados -->
        <div v-else>
          <div class="mb-4 text-sm text-gray-500">
            {{ paginationData.total }} entidade{{ paginationData.total !== 1 ? 's' : '' }} encontrada{{ paginationData.total !== 1 ? 's' : '' }}
          </div>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Nome</TableHead>
                <TableHead>Razão Social</TableHead>
                <TableHead>Tipo</TableHead>
                <TableHead>CNPJ/CPF</TableHead>
                <TableHead class="w-20">Ações</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="paginationData.data.length === 0">
                <TableCell colspan="5" class="text-center py-8 text-gray-500">
                  {{ hasActiveFilters ? 'Nenhuma entidade encontrada com os filtros aplicados' : 'Nenhuma entidade cadastrada' }}
                </TableCell>
              </TableRow>
              <TableRow v-for="entity in paginationData.data" :key="entity.id">
                <TableCell>{{ entity.name || '-' }}</TableCell>
                <TableCell>{{ entity.razao_social || '-' }}</TableCell>
                <TableCell>{{ entity.type || '-' }}</TableCell>
                <TableCell>{{ entity.cnpj_cpf || '-' }}</TableCell>
                <TableCell>
                  <div class="flex items-center gap-1">
                    <Button 
                      size="sm" 
                      variant="ghost" 
                      @click="openEditModal(entity)" 
                      class="h-8 w-8 p-0 text-blue-600 hover:text-blue-800 hover:bg-blue-50" 
                      title="Editar entidade"
                    >
                      <Edit class="h-4 w-4" />
                    </Button>
                    <Button 
                      size="sm" 
                      variant="ghost" 
                      @click="deleteEntity(entity.id)" 
                      class="h-8 w-8 p-0 text-red-600 hover:text-red-800 hover:bg-red-50" 
                      title="Deletar entidade"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <!-- Controles de paginação inferior -->
          <div v-if="paginationData.total > 0" class="flex items-center justify-between mt-6">
            <div class="flex items-center gap-4">
              <span class="text-sm text-gray-700">
                Exibindo {{ paginationData.from }} até {{ paginationData.to }} de {{ paginationData.total }} registros
              </span>
              <select 
                :value="paginationData.per_page" 
                @change="changePerPage(parseInt(($event.target as HTMLSelectElement).value))"
                class="text-sm border border-gray-300 rounded px-2 py-1"
              >
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
              </select>
            </div>
            <div class="flex items-center gap-2">
              <Button 
                variant="outline" 
                size="sm" 
                :disabled="paginationData.current_page === 1" 
                @click="goToPage(1)"
              >
                ««
              </Button>
              <Button 
                variant="outline" 
                size="sm" 
                :disabled="paginationData.current_page === 1" 
                @click="goToPage(paginationData.current_page - 1)"
              >
                «
              </Button>
              <Button 
                v-for="page in pageNumbers" 
                :key="page" 
                :variant="page === paginationData.current_page ? 'default' : 'outline'" 
                size="sm" 
                @click="goToPage(page)"
              >
                {{ page }}
              </Button>
              <Button 
                variant="outline" 
                size="sm" 
                :disabled="paginationData.current_page === paginationData.last_page" 
                @click="goToPage(paginationData.current_page + 1)"
              >
                »
              </Button>
              <Button 
                variant="outline" 
                size="sm" 
                :disabled="paginationData.current_page === paginationData.last_page" 
                @click="goToPage(paginationData.last_page)"
              >
                »»
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Edição -->
    <Dialog v-model:open="isModalOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Editar Entidade</DialogTitle>
        </DialogHeader>
        <div v-if="editingEntity" class="space-y-4 py-4">
          <div class="space-y-2">
            <Label for="name">Nome</Label>
            <Input id="name" v-model="editForm.name" placeholder="Nome da entidade" />
          </div>
          <div class="space-y-2">
            <Label for="razao_social">Razão Social</Label>
            <Input id="razao_social" v-model="editForm.razao_social" placeholder="Razão Social" />
          </div>
          <div class="space-y-2">
            <Label for="type">Tipo</Label>
            <Input id="type" v-model="editForm.type" placeholder="Tipo" />
          </div>
          <div class="space-y-2">
            <Label for="cnpj_cpf">CNPJ/CPF</Label>
            <Input id="cnpj_cpf" v-model="editForm.cnpj_cpf" placeholder="CNPJ ou CPF" />
          </div>
        </div>
        <div class="flex items-center justify-end gap-2 pt-4">
          <Button variant="outline" @click="closeModal" :disabled="isUpdating">Cancelar</Button>
          <Button @click="saveModalChanges" :disabled="isUpdating" class="flex items-center gap-2">
            <Save class="h-4 w-4" />{{ isUpdating ? 'Salvando...' : 'Salvar' }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Modal de Importação -->
    <Dialog v-model:open="isImportModalOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Importar Entidades</DialogTitle>
        </DialogHeader>
        <div class="space-y-4 py-4">
          <div class="space-y-2">
            <Label for="import-file">Selecione o Arquivo</Label>
            <Input 
              id="import-file" 
              type="file" 
              @change="(e) => importFile = (e.target as HTMLInputElement).files?.[0]" 
              accept=".csv,.xlsx,.xls"
            />
            <p class="text-xs text-gray-500">Formatos suportados: CSV, XLSX, XLS</p>
          </div>
          <div v-if="importError" class="text-sm text-red-600">{{ importError }}</div>
        </div>
        <div class="flex items-center justify-end gap-2 pt-4">
          <Button variant="outline" @click="closeImportModal">Cancelar</Button>
          <Button @click="handleImportSubmit" class="flex items-center gap-2">
            <Upload class="h-4 w-4" />Importar
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>