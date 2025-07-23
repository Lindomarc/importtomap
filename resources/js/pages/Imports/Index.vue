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
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import InputError from '@/components/InputError.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Upload, FileText, Trash2, Download, AlertCircle, CheckCircle, Clock, Plus } from "lucide-vue-next";
import { MapPinned, Logs } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Importações',
        href: '/imports',
    },
];

// Interfaces
interface ImportType {
    label: string;
    value: string;
}

interface ImportRecord {
    id: number;
    name: string;
    type: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    records_processed: number | null;
    records_total: number | null;
    error_message: string | null;
    created_at: string;
    updated_at: string;
    file_size: number;
}

interface PaginationData {
    data: ImportRecord[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

// Estado reativo
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
const isUploading = ref(false);
const modalOpen = ref(false);

// Estados do formulário de upload
const type = ref<string>('');
const selectedFile = ref<File | null>(null);
const uploadMessage = ref<string>('');
const uploadMessageType = ref<'success' | 'error'>('success');
const recentlySuccessful = ref<boolean>(false);
const errors = ref<Record<string, string>>({});

// Tipos de importação disponíveis
const importTypes: ImportType[] = [
    { label: 'Emissoras', value: 'emissoras' },
    { label: 'Placas', value: 'placas' },
    { label: 'Portais', value: 'portais' },
    { label: 'Campanhas', value: 'campanhas' },
];

// Funções de manipulação de arquivo
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        selectedFile.value = target.files[0];
        errors.value.file = '';
    }
};

const clearFile = () => {
    selectedFile.value = null;
    const fileInput = document.getElementById('file') as HTMLInputElement;
    if (fileInput) fileInput.value = '';
};

const clearForm = () => {
    type.value = '';
    clearFile();
    uploadMessage.value = '';
    errors.value = {};
};

// Funções utilitárias
const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleString('pt-BR');
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'completed': return CheckCircle;
        case 'failed': return AlertCircle;
        case 'processing': return Clock;
        default: return Clock;
    }
};

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'completed': return 'text-green-600';
        case 'failed': return 'text-red-600';
        case 'processing': return 'text-blue-600';
        default: return 'text-gray-600';
    }
};

const getStatusLabel = (status: string): string => {
    switch (status) {
        case 'pending': return 'Pendente';
        case 'processing': return 'Processando';
        case 'completed': return 'Concluída';
        case 'failed': return 'Falhou';
        default: return status;
    }
};

const getProgressPercentage = (record: ImportRecord): number => {
    if (!record.records_total || record.records_total === 0) return 0;
    if (!record.records_processed) return 0;
    return Math.round((record.records_processed / record.records_total) * 100);
};

// Validação do formulário
const validateForm = (): boolean => {
    errors.value = {};

    if (!type.value) {
        errors.value.type = 'Selecione um tipo de importação';
    }

    if (!selectedFile.value) {
        errors.value.file = 'Selecione um arquivo';
    } else if (selectedFile.value.size > 10 * 1024 * 1024) { // 10MB
        errors.value.file = 'Arquivo muito grande (máximo 10MB)';
    }

    return Object.keys(errors.value).length === 0;
};

// Função para submissão do formulário
const onSubmit = async () => {
    if (!validateForm()) return;

    isUploading.value = true;
    uploadMessage.value = '';
    recentlySuccessful.value = false;

    const formData = new FormData();
    formData.append('type', type.value);
    formData.append('file', selectedFile.value!);

    try {
        const response = await axios.post('/api/imports/store', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        uploadMessage.value = response.data.message || 'Importação iniciada com sucesso!';
        uploadMessageType.value = 'success';
        recentlySuccessful.value = true;

        // Atualizar lista de importações
        fetchImports();

        // Limpar formulário após sucesso e fechar modal
        setTimeout(() => {
            clearForm();
            recentlySuccessful.value = false;
            modalOpen.value = false;
        }, 2000);

    } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Erro ao iniciar importação.';
        uploadMessage.value = errorMessage;
        uploadMessageType.value = 'error';

        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        }
    } finally {
        isUploading.value = false;
    }
};

// Função para buscar importações
const fetchImports = async (page: number = 1, perPage: number = 10) => {
    try {
        isLoading.value = true;
        const response = await axios.get(`/api/imports?page=${page}&per_page=${perPage}`);
        paginationData.value = response.data;
    } catch (error) {
        console.error("Erro ao buscar importações:", error);
    } finally {
        isLoading.value = false;
    }
};

// Função para deletar importação
const deleteImport = async (id: number) => {
    if (!confirm('Tem certeza que deseja deletar esta importação?')) {
        return;
    }

    try {
        isLoading.value = true;
        await axios.delete(`/api/imports/${id}`);

        // Remover da lista local
        paginationData.value.data = paginationData.value.data.filter(item => item.id !== id);
        paginationData.value.total--;

        // Se não há mais itens na página atual, voltar para a página anterior
        if (paginationData.value.data.length === 0 && paginationData.value.current_page > 1) {
            fetchImports(paginationData.value.current_page - 1, paginationData.value.per_page);
        }

    } catch (error) {
        console.error("Erro ao deletar importação:", error);
        alert("Erro ao deletar importação. Tente novamente.");
    } finally {
        isLoading.value = false;
    }
};

// Função para baixar relatório de erros
const downloadErrorReport = async (id: number) => {
    try {
        const response = await axios.get(`/api/imports/${id}/error-report`, {
            responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `import-errors-${id}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error("Erro ao baixar relatório:", error);
        alert("Erro ao baixar relatório de erros.");
    }
};

// Funções de paginação
const goToPage = (page: number) => {
    if (page >= 1 && page <= paginationData.value.last_page) {
        fetchImports(page, paginationData.value.per_page);
    }
};

const changePerPage = (perPage: number) => {
    fetchImports(1, perPage);
};

// Computed para páginas de navegação
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

// Função para abrir o modal e limpar o formulário
const openModal = () => {
    clearForm();
    modalOpen.value = true;
};

// Buscar dados ao montar componente
onMounted(() => {
    fetchImports();
    // Configurar polling para atualizar status das importações
    setInterval(() => {
        if (!isLoading.value && !isUploading.value) {
            fetchImports(paginationData.value.current_page, paginationData.value.per_page);
        }
    }, 10000); // Atualizar a cada 10 segundos
});
</script>

<template>
  <Head title="Importações" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
      <!-- Cabeçalho com botão para abrir modal -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold tracking-tight">Importações</h2>
          <p class="text-muted-foreground">
            Gerencie suas importações de dados
          </p>
        </div>
        
        <!-- Botão para abrir modal -->
        <Dialog v-model:open="modalOpen">
          <DialogTrigger as-child>
            <Button @click="openModal">
              <Plus class="h-4 w-4 mr-2" />
              Nova Importação
            </Button>
          </DialogTrigger>
          
          <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
              <DialogTitle>Nova Importação</DialogTitle>
            </DialogHeader>
            
            <!-- Formulário dentro do modal -->
            <form @submit.prevent="onSubmit" class="space-y-6">
              <!-- Tipo de Importação -->
              <div class="grid gap-2">
                <Label for="type">Tipo de Importação</Label>
                <Select v-model="type" required>
                  <SelectTrigger>
                    <SelectValue placeholder="Selecione o tipo de importação" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in importTypes"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <InputError class="mt-2" :message="errors.type" />
              </div>

              <!-- Upload de Arquivo -->
              <div class="grid gap-2">
                <Label for="file">Arquivo</Label>
                <div class="flex items-center gap-4">
                  <Input
                    id="file"
                    type="file"
                    accept=".xlsx,.csv"
                    @change="handleFileChange"
                    class="flex-1"
                    required
                  />
                  <Button
                    v-if="selectedFile"
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="clearFile"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
                <div
                  v-if="selectedFile"
                  class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                  <FileText class="h-4 w-4" />
                  <span>{{ selectedFile.name }}</span>
                  <span class="text-xs">({{ formatFileSize(selectedFile.size) }})</span>
                </div>
                <InputError class="mt-2" :message="errors.file" />
              </div>

              <!-- Mensagem de Resultado -->
              <div v-if="uploadMessage" class="mt-4">
                <div
                  :class="[
                    'rounded-lg p-4 text-sm',
                    uploadMessageType === 'success'
                      ? 'bg-green-50 text-green-800 border border-green-200'
                      : 'bg-red-50 text-red-800 border border-red-200',
                  ]"
                >
                  <div class="flex items-center gap-2">
                    <CheckCircle v-if="uploadMessageType === 'success'" class="h-4 w-4" />
                    <AlertCircle v-else class="h-4 w-4" />
                    <span>{{ uploadMessage }}</span>
                  </div>
                </div>
              </div>

              <!-- Informações sobre formatos -->
              <div class="rounded-lg border border-border p-4 bg-muted/50">
                <h4 class="font-medium mb-2">Formatos aceitos</h4>
                <ul class="text-sm text-muted-foreground space-y-1">
                  <li>• Arquivos Excel (.xlsx)</li>
                  <li>• Arquivos CSV (.csv)</li>
                  <li>• Tamanho máximo: 10MB</li>
                </ul>
              </div>

              <!-- Botões de Ação -->
              <div class="flex items-center justify-end gap-4 pt-4">
                <Button
                  type="button"
                  variant="outline"
                  @click="modalOpen = false"
                  :disabled="isUploading"
                >
                  Cancelar
                </Button>
                
                <Button :disabled="isUploading" type="submit">
                  <Upload v-if="!isUploading" class="h-4 w-4" />
                  <div
                    v-else
                    class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
                  ></div>
                  <span class="ml-2">{{ isUploading ? "Enviando..." : "Importar" }}</span>
                </Button>

                <Transition
                  enter-active-class="transition ease-in-out"
                  enter-from-class="opacity-0"
                  leave-active-class="transition ease-in-out"
                  leave-to-class="opacity-0"
                >
                  <p v-show="recentlySuccessful" class="text-sm text-green-600">
                    Importação iniciada com sucesso!
                  </p>
                </Transition>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Histórico de Importações -->
      <div class="container mx-auto">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium">Histórico de Importações</h3>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-600">Itens por página:</span>
            <Select
              :model-value="paginationData?.per_page?.toString()"
              @update:model-value="(value) => changePerPage(parseInt(value))"
            >
              <SelectTrigger class="w-20">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="10">10</SelectItem>
                <SelectItem value="25">25</SelectItem>
                <SelectItem value="50">50</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <!-- Tabela de Importações -->
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Arquivo</TableHead>
              <TableHead>Tipo</TableHead>
              <TableHead>Data/Hora</TableHead>
              <TableHead class="w-32">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="isLoading">
              <TableCell colspan="4" class="text-center py-8">
                <div class="flex items-center justify-center">
                  <div
                    class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"
                  ></div>
                  <span class="ml-2">Carregando...</span>
                </div>
              </TableCell>
            </TableRow>
            <TableRow v-else-if="paginationData?.data?.length === 0">
              <TableCell colspan="4" class="text-center py-8">
                Nenhuma importação encontrada
              </TableCell>
            </TableRow>
            <TableRow v-else v-for="record in paginationData.data" :key="record.id">
              <TableCell>
                <div class="flex items-center gap-2">
                  <FileText class="h-4 w-4 text-gray-500" />
                  <div>
                    <div class="font-medium">{{ record?.name }}</div>
                    <div class="text-sm text-gray-500">
                      {{ formatFileSize(record.file_size) }}
                    </div>
                  </div>
                </div>
              </TableCell>
              <TableCell>{{ record.type }}</TableCell>
              <TableCell>
                <div class="text-sm">{{ formatDate(record.created_at) }}</div>
              </TableCell>
              <TableCell>
                <div class="flex items-center gap-2">
                  <!-- Botão para visualizar logs -->
                  <Button
                    v-if="record.id"
                    size="sm"
                    variant="ghost"
                    :as="Link"
                    :href="`/map?import_id=${record.id}`"
                    target="_blank"
                    class="h-8 w-8 p-0 text-blue-600 hover:text-blue-800 hover:bg-blue-50"
                  >
                    <Logs class="h-4 w-4" />
                  </Button>                  
                  <!-- Botão para visualizar mapa -->
                  <Button
                    v-if="record.id"
                    size="sm"
                    variant="ghost"
                    :as="Link"
                    :href="`/data?import_id=${record.id}`"
                    target="_blank"
                    class="h-8 w-8 p-0 text-blue-600 hover:text-blue-800 hover:bg-blue-50"
                  >
                    <MapPinned class="h-4 w-4" />
                  </Button>
                  <Button
                    v-if="record.status === 'failed'"
                    size="sm"
                    variant="ghost"
                    @click="downloadErrorReport(record.id)"
                    class="h-8 w-8 p-0"
                  >
                    <Download class="h-4 w-4" />
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    @click="deleteImport(record.id)"
                    class="h-8 w-8 p-0 text-red-600 hover:text-red-800 hover:bg-red-50"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Controles de paginação -->
        <div class="flex items-center justify-between mt-4">
          <div class="text-sm text-gray-600">
            Mostrando {{ paginationData.from }} até {{ paginationData.to }} de
            {{ paginationData.total }} resultados
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