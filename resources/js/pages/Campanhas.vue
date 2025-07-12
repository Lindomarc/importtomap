<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { onMounted, ref, computed } from "vue";
import axios from "axios";
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Table, TableHeader, TableHead, TableBody, TableRow, TableCell } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Input } from "@/components/ui/input";
// import { Trash2, Check, X } from "lucide-react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Campanhas',
        href: '/campanhas',
    },
];

// Define a interface para os dados da campanha
interface Campanha {
    id: number;
    campanha: string | null;
    cliente: string | null;
    veiculo: string | null;
    meio: string | null;
    praca: string | null;
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

// Estados para edição inline
const editingField = ref<{ id: number; field: 'lat' | 'lng' } | null>(null);
const editingValue = ref('');

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

// Função para salvar edição
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
        // alert(`Erro ao atualizar ${field}. Tente novamente.`);
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
        const response = await axios.get(`/api/campanhas?page=${page}&per_page=${perPage}`);
        paginationData.value = response.data;
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
                <!-- Controles de paginação superior -->
                <!-- <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600">Itens por página:</span>
                        <Select :value="paginationData.per_page.toString()" @update:value="(value) => changePerPage(parseInt(value))">
                            <SelectTrigger class="w-20">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10</SelectItem>
                                <SelectItem value="25">25</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    
                    <div class="text-sm text-gray-600">
                        Mostrando {{ paginationData.from }} até {{ paginationData.to }} de {{ paginationData.total }} resultados
                    </div>
                </div> -->

                <!-- Tabela para exibir os dados -->
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Campanha</TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead>Veículo</TableHead>
                            <TableHead>Meio</TableHead>
                            <TableHead>Praça</TableHead>
                            <TableHead>LAT</TableHead>
                            <TableHead>LNG</TableHead>
                            <TableHead class="w-24">Ações</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="isLoading">
                            <TableCell colspan="8" class="text-center py-8">
                                <div class="flex items-center justify-center">
                                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                                    <span class="ml-2">Carregando...</span>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-else-if="paginationData.data.length === 0">
                            <TableCell colspan="8" class="text-center py-8">
                                Nenhuma campanha encontrada
                            </TableCell>
                        </TableRow>
                        <TableRow v-else v-for="campanha in paginationData.data" :key="campanha.id">
                            <TableCell>{{ campanha.campanha }}</TableCell>
                            <TableCell>{{ campanha.cliente }}</TableCell>
                            <TableCell>{{ campanha.veiculo }}</TableCell>
                            <TableCell>{{ campanha.meio }}</TableCell>
                            <TableCell>{{ campanha.praca }}</TableCell>
                            
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
                                <Button 
                                    size="sm" 
                                    variant="ghost" 
                                    @click="deleteCampanha(campanha.id)"
                                    class="h-8 w-8 p-0 text-red-600 hover:text-red-800 hover:bg-red-50"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
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
    </AppLayout>
</template>