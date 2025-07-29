<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { onMounted, ref, computed, defineProps} from "vue";
import axios from "axios";
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Table, TableHeader, TableHead, TableBody, TableRow, TableCell } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Trash2, Check, X, Edit, Save, Search, Filter, RotateCcw } from "lucide-vue-next";

const props = defineProps({
  importId: {
    type: String,
    default: null
  }
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dados',
        href: '/data',
    },
];

// Define a interface para os dados da campanha
interface Campanha {
    id: number;
    name: string | null;
    info: string | null;
    type: string | null;
    color: string | null;
    lat: string | null;
    lng: string | null;
}

// Interface para dados de paginação
interface PaginationData {
    data: Campanha[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

// Estado reativo para armazenar as campanhas e dados de paginação
const paginationData = ref<PaginationData>({
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: 0,
    to: 0
});

const isLoading = ref(false);

// Estados para o modal de edição
const isModalOpen = ref(false);
const isUpdating = ref(false);
const editingCampanha = ref<Campanha | null>(null);
const editForm = ref({
    name: '',
    info: '',
    type: '',
    color: '',
    lat: '',
    lng: ''
});

// Estados para filtros
const filters = ref({
    search: '',
    name: '',
    type: '',
    color: ''
});

const showFilters = ref(false);

// Estados para edição inline
const editingField = ref<{ id: number; field: 'lat' | 'lng' } | null>(null);
const editingValue = ref('');

// Função para limpar filtros
const clearFilters = () => {
    filters.value = {
        search: '',
        name: '',
        type: '',
        color: ''
    };
    fetchCampanhas(1, paginationData.value.per_page);
};

// Função para aplicar filtros (com debounce)
let searchTimeout: NodeJS.Timeout;
const applyFilters = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchCampanhas(1, paginationData.value.per_page);
    }, 500);
};

// Watch para aplicar filtros automaticamente
import { watch } from 'vue';
watch(filters, applyFilters, { deep: true });

// Função para abrir o modal de edição
const openEditModal = (campanha: Campanha) => {
    editingCampanha.value = campanha;
    editForm.value = {
        name: campanha.name || '',
        info: campanha.info || '',
        type: campanha.type || '',
        color: campanha.color || '',
        lat: campanha.lat || '',
        lng: campanha.lng || ''
    };
    isModalOpen.value = true;
};

// Função para fechar o modal
const closeModal = () => {
    isModalOpen.value = false;
    editingCampanha.value = null;
    editForm.value = {
        name: '',
        info: '',
        type: '',
        color: '',
        lat: '',
        lng: ''
    };
};

// Função para salvar as alterações do modal
const saveModalChanges = async () => {
    if (!editingCampanha.value) return;
    
    try {
        isUpdating.value = true;
        
        const response = await axios.put(`/api/campanhas/${editingCampanha.value.id}`, {
            name: editForm.value.name,
            info: editForm.value.info,
            type: editForm.value.type,
            color: editForm.value.color,
            lat: editForm.value.lat,
            lng: editForm.value.lng
        });
        
        // Atualizar os dados localmente
        const index = paginationData.value.data.findIndex(c => c.id === editingCampanha.value!.id);
        if (index !== -1) {
            paginationData.value.data[index] = {
                ...paginationData.value.data[index],
                ...editForm.value
            };
        }
        
        closeModal();
        
        // Opcional: mostrar mensagem de sucesso
        // alert('Campanha atualizada com sucesso!');
        
    } catch (error) {
        console.error('Erro ao atualizar campanha:', error);
        alert('Erro ao atualizar campanha. Tente novamente.');
    } finally {
        isUpdating.value = false;
    }
};

// Função para iniciar edição de um campo
const startEditing = (id: number, field: 'lat' | 'lng', currentValue: string | null) => {
    editingField.value = { id, field };
    editingValue.value = currentValue || '';
};

// Função para cancelar edição
const cancelEditing = () => {
    editingField.value = null;
    editingValue.value = '';
};

// Função para salvar edição inline
const saveEdit = async (id: number, field: 'lat' | 'lng') => {
    try {
        isLoading.value = true;
        await axios.put(`/api/campanhas/${id}`, {
            [field]: editingValue.value
        });
        
        // Atualizar o valor localmente
        const campanha = paginationData.value.data.find(c => c.id === id);
        if (campanha) {
            campanha[field] = editingValue.value;
        }
        
        cancelEditing();
    } catch (error) {
        console.error(`Erro ao atualizar ${field}:`, error);
        alert(`Erro ao atualizar ${field}. Tente novamente.`);
    } finally {
        isLoading.value = false;
    }
};

// Função para deletar campanha
const deleteCampanha = async (id: number) => {
    if (!confirm('Tem certeza que deseja deletar esta campanha?')) {
        return;
    }
    
    try {
        isLoading.value = true;
        await axios.delete(`/api/campanhas/${id}`);
        
        // Remover da lista local
        paginationData.value.data = paginationData.value.data.filter(c => c.id !== id);
        paginationData.value.total--;
        
        // Se não há mais itens na página atual, voltar para a página anterior
        if (paginationData.value.data.length === 0 && paginationData.value.current_page > 1) {
            fetchCampanhas(paginationData.value.current_page - 1, paginationData.value.per_page);
        }
        
    } catch (error) {
        console.error("Erro ao deletar campanha:", error);
        alert("Erro ao deletar campanha. Tente novamente.");
    } finally {
        isLoading.value = false;
    }
};

// Função para buscar os dados das campanhas do backend
const fetchCampanhas = async (page: number = 1, perPage: number = 10) => {
    try {
        isLoading.value = true;

        const params: any = {
            import_id: props.importId,
            page: page,
            per_page: perPage,
        };

        // Adicionar filtros aos parâmetros se não estiverem vazios
        if (filters.value.search.trim()) {
            params.search = filters.value.search.trim();
        }
        if (filters.value.name.trim()) {
            params.name = filters.value.name.trim();
        }
        if (filters.value.type.trim()) {
            params.type = filters.value.type.trim();
        }
        if (filters.value.color.trim()) {
            params.color = filters.value.color.trim();
        }

        const response = await axios.get("/api/campanhas", { params });
    
        paginationData.value = response.data;
        console.log(paginationData.value)
    } catch (error) {
        console.error("Erro ao buscar campanhas:", error);
    } finally {
        isLoading.value = false;
    }
};

// Função para ir para uma página específica
const goToPage = (page: number) => {
    if (page >= 1 && page <= paginationData.value.last_page) {
        fetchCampanhas(page, paginationData.value.per_page);
    }
};

// Função para alterar o número de itens por página
const changePerPage = (perPage: number) => {
    fetchCampanhas(1, perPage);
};

// Computed para gerar array de páginas para navegação
const pageNumbers = computed(() => {
    const pages = [];
    const currentPage = paginationData.value.current_page;
    const lastPage = paginationData.value.last_page;
    
    // Lógica para mostrar páginas (máximo 5 páginas visíveis)
    let start = Math.max(1, currentPage - 2);
    let end = Math.min(lastPage, currentPage + 2);
    
    // Ajustar se estivermos no início ou fim
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
    fetchCampanhas();
});
</script>

<template>
    <Head title="Campanhas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="container mx-auto p-4">
                <!-- Seção de Filtros -->
                <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <!-- Header dos filtros -->
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Filtros de Pesquisa</h3>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="showFilters = !showFilters"
                            class="flex items-center gap-2"
                        >
                            <Filter class="h-4 w-4" />
                            {{ showFilters ? 'Ocultar' : 'Mostrar' }} Filtros
                        </Button>
                    </div>
                    
                    <!-- Conteúdo dos filtros -->
                    <div v-show="showFilters" class="p-4 space-y-4">
                        <!-- Primeira linha: Pesquisa geral -->
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
                            </div>
                        </div>
                        
                        <!-- Segunda linha: Filtros específicos -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Filtro por Nome -->
                            <div>
                                <Label for="filter-name" class="text-sm font-medium text-gray-700">Nome</Label>
                                <Input
                                    id="filter-name"
                                    v-model="filters.name"
                                    placeholder="Filtrar por nome..."
                                    class="mt-1"
                                />
                            </div>
                            
                            <!-- Filtro por Tipo -->
                            <div>
                                <Label for="filter-type" class="text-sm font-medium text-gray-700">Tipo</Label>
                                <Input
                                    id="filter-type"
                                    v-model="filters.type"
                                    placeholder="Filtrar por tipo..."
                                    class="mt-1"
                                />
                            </div>
                            
                            <!-- Filtro por Cor -->
                            <div>
                                <Label for="filter-color" class="text-sm font-medium text-gray-700">Cor</Label>
                                <Input
                                    id="filter-color"
                                    v-model="filters.color"
                                    placeholder="Filtrar por cor..."
                                    class="mt-1"
                                />
                            </div>
                        </div>
                        
                        <!-- Botões de ação -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="clearFilters"
                                class="flex items-center gap-2"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Limpar Filtros
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Indicadores de filtros ativos -->
                <div v-if="filters.search || filters.name || filters.type || filters.color" class="mb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm text-gray-600">Filtros ativos:</span>
                        
                        <span v-if="filters.search" class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded-md text-xs">
                            Pesquisa: "{{ filters.search }}"
                            <button @click="filters.search = ''" class="hover:text-blue-600">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                        
                        <span v-if="filters.name" class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-800 rounded-md text-xs">
                            Nome: "{{ filters.name }}"
                            <button @click="filters.name = ''" class="hover:text-green-600">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                        
                        <span v-if="filters.type" class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-800 rounded-md text-xs">
                            Tipo: "{{ filters.type }}"
                            <button @click="filters.type = ''" class="hover:text-purple-600">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                        
                        <span v-if="filters.color" class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-800 rounded-md text-xs">
                            Cor: "{{ filters.color }}"
                            <button @click="filters.color = ''" class="hover:text-orange-600">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                    </div>
                </div>
                <!-- Tabela para exibir os dados -->
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nome</TableHead>
                            <TableHead>Descrição</TableHead>
                            <TableHead>Tipo</TableHead>
                            <TableHead>Cor</TableHead>
                            <TableHead>LAT</TableHead>
                            <TableHead>LNG</TableHead>
                            <TableHead class="w-32">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="isLoading">
                            <TableCell colspan="7" class="text-center py-8">
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                                    <span class="ml-2">Carregando...</span>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-else-if="paginationData.data.length === 0">
                            <TableCell colspan="7" class="text-center py-8">
                                Nenhuma campanha encontrada
                            </TableCell>
                        </TableRow>
                        <TableRow v-else v-for="campanha in paginationData.data" :key="campanha.id">
                            <TableCell>{{ campanha.name }}</TableCell>
                            <TableCell>{{ campanha.info }}</TableCell>
                            <TableCell>{{ campanha.type }}</TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <div 
                                        v-if="campanha.color"
                                        class="w-4 h-4 rounded-full border border-gray-300"
                                        :style="{ backgroundColor: campanha.color }"
                                    ></div>
                                    <span>{{ campanha.color }}</span>
                                </div>
                            </TableCell>
                            
                            <!-- Campo LAT editável -->
                            <TableCell>
                                <div v-if="editingField?.id === campanha.id && editingField?.field === 'lat'" class="flex items-center gap-2">
                                    <Input 
                                        v-model="editingValue" 
                                        type="text"
                                        class="h-8 w-24"
                                        @keyup.enter="saveEdit(campanha.id, 'lat')"
                                        @keyup.escape="cancelEditing()"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="ghost" 
                                        @click="saveEdit(campanha.id, 'lat')"
                                        class="h-8 w-8 p-0"
                                    >
                                        <Check class="h-4 w-4 text-green-600" />
                                    </Button>
                                    <Button 
                                        size="sm" 
                                        variant="ghost" 
                                        @click="cancelEditing()"
                                        class="h-8 w-8 p-0"
                                    >
                                        <X class="h-4 w-4 text-red-600" />
                                    </Button>
                                </div>
                                <div v-else 
                                     class="cursor-pointer hover:bg-gray-100 p-1 rounded"
                                     @click="startEditing(campanha.id, 'lat', campanha.lat)"
                                >
                                    {{ campanha.lat || '' }}
                                </div>
                            </TableCell>
                            
                            <!-- Campo LNG editável -->
                            <TableCell>
                                <div v-if="editingField?.id === campanha.id && editingField?.field === 'lng'" class="flex items-center gap-2">
                                    <Input 
                                        v-model="editingValue" 
                                        type="text"
                                        class="h-8 w-24"
                                        @keyup.enter="saveEdit(campanha.id, 'lng')"
                                        @keyup.escape="cancelEditing()"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="ghost" 
                                        @click="saveEdit(campanha.id, 'lng')"
                                        class="h-8 w-8 p-0"
                                    >
                                        <Check class="h-4 w-4 text-green-600" />
                                    </Button>
                                    <Button 
                                        size="sm" 
                                        variant="ghost" 
                                        @click="cancelEditing()"
                                        class="h-8 w-8 p-0"
                                    >
                                        <X class="h-4 w-4 text-red-600" />
                                    </Button>
                                </div>
                                <div v-else 
                                     class="cursor-pointer hover:bg-gray-100 p-1 rounded"
                                     @click="startEditing(campanha.id, 'lng', campanha.lng)"
                                >
                                    {{ campanha.lng || '' }}
                                </div>
                            </TableCell>
                            
                            <!-- Coluna de ações -->
                            <TableCell>
                                <div class="flex items-center gap-1">
                                    <!-- Botão para abrir modal de edição -->
                                    <Button 
                                        size="sm" 
                                        variant="ghost" 
                                        @click="openEditModal(campanha)"
                                        class="h-8 w-8 p-0 text-blue-600 hover:text-blue-800 hover:bg-blue-50"
                                        title="Editar campanha"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    
                                    <!-- Botão de deletar -->
                                    <Button 
                                        size="sm" 
                                        variant="ghost" 
                                        @click="deleteCampanha(campanha.id)"
                                        class="h-8 w-8 p-0 text-red-600 hover:text-red-800 hover:bg-red-50"
                                        title="Deletar campanha"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Controles de paginação inferior -->
                <div class="flex items-center justify-between mt-4">
                    <div class="text-sm text-gray-600">
                        Página {{ paginationData.current_page }} de {{ paginationData.last_page }}
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Primeira página -->
                        <Button 
                            variant="outline" 
                            size="sm"
                            :disabled="paginationData.current_page === 1"
                            @click="goToPage(1)"
                        >
                            ««
                        </Button>
                        
                        <!-- Página anterior -->
                        <Button 
                            variant="outline" 
                            size="sm"
                            :disabled="paginationData.current_page === 1"
                            @click="goToPage(paginationData.current_page - 1)"
                        >
                            «
                        </Button>
                        
                        <!-- Números das páginas -->
                        <Button 
                            v-for="page in pageNumbers"
                            :key="page"
                            :variant="page === paginationData.current_page ? 'default' : 'outline'"
                            size="sm"
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </Button>
                        
                        <!-- Próxima página -->
                        <Button 
                            variant="outline" 
                            size="sm"
                            :disabled="paginationData.current_page === paginationData.last_page"
                            @click="goToPage(paginationData.current_page + 1)"
                        >
                            »
                        </Button>
                        
                        <!-- Última página -->
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

        <!-- Modal de Edição -->
        <Dialog v-model:open="isModalOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar Campanha</DialogTitle>
                </DialogHeader>
                
                <div v-if="editingCampanha" class="space-y-4 py-4">
                    <!-- Campo Nome -->
                    <div class="space-y-2">
                        <Label for="name">Nome</Label>
                        <Input 
                            id="name"
                            v-model="editForm.name" 
                            placeholder="Nome da campanha"
                        />
                    </div>
                    
                    <!-- Campo Descrição -->
                    <div class="space-y-2">
                        <Label for="info">Descrição</Label>
                        <Textarea 
                            id="info"
                            v-model="editForm.info" 
                            placeholder="Descrição da campanha"
                            rows="3"
                        />
                    </div>
                    
                    <!-- Campo Tipo -->
                    <!-- <div class="space-y-2">
                        <Label for="type">Tipo</Label>
                        <Input 
                            id="type"
                            v-model="editForm.type" 
                            placeholder="Tipo da campanha"
                        />
                    </div> -->
                    
                    <!-- Campo Cor -->
                    <div class="space-y-2">
                        <Label for="color">Cor</Label>
                        <div class="flex items-center gap-2">
                            <Input 
                                id="color"
                                v-model="editForm.color" 
                                placeholder="#000000"
                                class="flex-1"
                            />
                            <div 
                                v-if="editForm.color"
                                class="w-8 h-8 rounded border border-gray-300"
                                :style="{ backgroundColor: editForm.color }"
                            ></div>
                        </div>
                    </div>
                    
                    <!-- Campos LAT e LNG -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="lat">Latitude</Label>
                            <Input 
                                id="lat"
                                v-model="editForm.lat" 
                                placeholder="Latitude"
                                type="text"
                            />
                        </div>
                        
                        <div class="space-y-2">
                            <Label for="lng">Longitude</Label>
                            <Input 
                                id="lng"
                                v-model="editForm.lng" 
                                placeholder="Longitude"
                                type="text"
                            />
                        </div>
                    </div>
                </div>
                
                <!-- Botões de ação -->
                <div class="flex items-center justify-end gap-2 pt-4">
                    <Button 
                        variant="outline" 
                        @click="closeModal"
                        :disabled="isUpdating"
                    >
                        Cancelar
                    </Button>
                    <Button 
                        @click="saveModalChanges"
                        :disabled="isUpdating"
                        class="flex items-center gap-2"
                    >
                        <Save class="h-4 w-4" />
                        {{ isUpdating ? 'Salvando...' : 'Salvar' }}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>