<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import axios from "axios";
import { useQuasar } from 'quasar';
import { useRoute, useRouter } from 'vue-router';

const $q = useQuasar();
const route = useRoute();
const router = useRouter();

// Interface para os dados da importação
interface Import {
    id: number;
    name: string;
    date: string;
    type: string;
    success_count: number;
    error_count: number;
    errors?: string[];
    status: 'sucesso' | 'falha' | 'parcial';
    total_records: number;
    created_at: string;
}

const importData = ref<Import | null>(null);
const isLoading = ref(false);

// Função para buscar os dados da importação
const fetchImport = async () => {
    try {
        isLoading.value = true;
        const importId = route.params.id;
        const response = await axios.get(`/api/imports/${importId}`);
        importData.value = response.data;
    } catch (error) {
        console.error("Erro ao buscar importação:", error);
        $q.notify({
            type: 'negative',
            message: 'Erro ao carregar detalhes da importação',
            position: 'top-right'
        });
        router.push('/imports');
    } finally {
        isLoading.value = false;
    }
};

// Função para deletar importação
const deleteImport = async () => {
    if (!importData.value) return;

    try {
        await $q.dialog({
            title: 'Confirmar Exclusão',
            message: `Tem certeza que deseja deletar o registro "${importData.value.name}"?`,
            cancel: true,
            persistent: true
        });

        isLoading.value = true;
        await axios.delete(`/api/imports/${importData.value.id}`);

        $q.notify({
            type: 'positive',
            message: 'Importação removida com sucesso',
            position: 'top-right'
        });

        router.push('/imports');
        
    } catch (error) {
        if (error !== false) { // false é retornado quando o usuário cancela o dialog
            console.error("Erro ao deletar importação:", error);
            $q.notify({
                type: 'negative',
                message: 'Erro ao deletar importação',
                position: 'top-right'
            });
        }
    } finally {
        isLoading.value = false;
    }
};

// Função para obter cor do tipo
const getTypeColor = (type: string) => {
    switch (type) {
        case 'emissoras': return 'positive';
        case 'placas': return 'negative';
        case 'portais': return 'primary';
        default: return 'grey';
    }
};

// Função para obter cor do status
const getStatusColor = (status: string) => {
    switch (status) {
        case 'sucesso': return 'positive';
        case 'falha': return 'negative';
        case 'parcial': return 'warning';
        default: return 'grey';
    }
};

// Função para formatar data
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR');
};

// Função para formatar data e hora
const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('pt-BR');
};

// Computed para taxa de sucesso
const successRate = computed(() => {
    if (!importData.value || importData.value.total_records === 0) return 0;
    return (importData.value.success_count / importData.value.total_records) * 100;
});

// Computed para cor da barra de progresso
const progressColor = computed(() => {
    const rate = successRate.value;
    if (rate >= 90) return 'positive';
    if (rate >= 70) return 'warning';
    return 'negative';
});

// Busca os dados ao montar o componente
onMounted(() => {
    fetchImport();
});
</script>

<template>
    <q-page class="q-pa-md">
        <!-- Header -->
        <div class="row q-mb-md items-center justify-between">
            <div class="col-auto">
                <div class="text-h5 text-weight-bold">Detalhes da Importação</div>
            </div>
            <div class="col-auto">
                <q-btn
                    color="grey"
                    icon="arrow_back"
                    label="Voltar"
                    @click="router.push('/imports')"
                />
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex flex-center q-pa-xl">
            <q-spinner-dots size="50px" color="primary" />
        </div>

        <!-- Conteúdo principal -->
        <div v-else-if="importData" class="row q-col-gutter-md">
            <!-- Informações Gerais -->
            <div class="col-12 col-md-6">
                <q-card>
                    <q-card-section class="bg-primary text-white">
                        <div class="text-h6">Informações Gerais</div>
                    </q-card-section>
                    
                    <q-card-section>
                        <q-list>
                            <q-item>
                                <q-item-section>
                                    <q-item-label class="text-weight-bold">Nome do Arquivo:</q-item-label>
                                    <q-item-label caption>{{ importData.name }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-separator spaced />

                            <q-item>
                                <q-item-section>
                                    <q-item-label class="text-weight-bold">Data:</q-item-label>
                                    <q-item-label caption>{{ formatDate(importData.date) }}</q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-separator spaced />

                            <q-item>
                                <q-item-section>
                                    <q-item-label class="text-weight-bold">Tipo:</q-item-label>
                                    <q-item-label caption>
                                        <q-chip
                                            :color="getTypeColor(importData.type)"
                                            text-color="white"
                                            size="sm"
                                        >
                                            {{ importData.type.charAt(0).toUpperCase() + importData.type.slice(1) }}
                                        </q-chip>
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-separator spaced />

                            <q-item>
                                <q-item-section>
                                    <q-item-label class="text-weight-bold">Status:</q-item-label>
                                    <q-item-label caption>
                                        <q-chip
                                            :color="getStatusColor(importData.status)"
                                            text-color="white"
                                            size="sm"
                                        >
                                            {{ importData.status.charAt(0).toUpperCase() + importData.status.slice(1) }}
                                        </q-chip>
                                    </q-item-label>
                                </q-item-section>
                            </q-item>

                            <q-separator spaced />

                            <q-item>
                                <q-item-section>
                                    <q-item-label class="text-weight-bold">Importado em:</q-item-label>
                                    <q-item-label caption>{{ formatDateTime(importData.created_at) }}</q-item-label>
                                </q-item-section>
                            </q-item>
                        </q-list>
                    </q-card-section>
                </q-card>
            </div>

            <!-- Estatísticas -->
            <div class="col-12 col-md-6">
                <q-card>
                    <q-card-section class="bg-info text-white">
                        <div class="text-h6">Estatísticas</div>
                    </q-card-section>
                    
                    <q-card-section>
                        <!-- Contadores -->
                        <div class="row q-col-gutter-md q-mb-md">
                            <div class="col-4">
                                <q-card bordered class="text-center">
                                    <q-card-section>
                                        <div class="text-h4 text-positive">{{ importData.success_count }}</div>
                                        <div class="text-caption text-grey">Sucessos</div>
                                    </q-card-section>
                                </q-card>
                            </div>
                            
                            <div class="col-4">
                                <q-card bordered class="text-center">
                                    <q-card-section>
                                        <div class="text-h4 text-negative">{{ importData.error_count }}</div>
                                        <div class="text-caption text-grey">Erros</div>
                                    </q-card-section>
                                </q-card>
                            </div>
                            
                            <div class="col-4">
                                <q-card bordered class="text-center">
                                    <q-card-section>
                                        <div class="text-h4 text-primary">{{ importData.total_records }}</div>
                                        <div class="text-caption text-grey">Total</div>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </div>

                        <!-- Taxa de Sucesso -->
                        <div v-if="importData.success_count > 0 && importData.total_records > 0">
                            <div class="text-weight-bold q-mb-sm">Taxa de Sucesso</div>
                            <q-linear-progress
                                :value="successRate / 100"
                                :color="progressColor"
                                size="20px"
                                class="q-mb-sm"
                            />
                            <div class="text-center text-weight-bold">
                                {{ successRate.toFixed(1) }}%
                            </div>
                        </div>
                    </q-card-section>
                </q-card>
            </div>

            <!-- Lista de Erros -->
            <div v-if="importData.errors && importData.errors.length > 0" class="col-12">
                <q-card>
                    <q-card-section class="bg-warning text-white">
                        <div class="text-h6">
                            <q-icon name="warning" class="q-mr-sm" />
                            Erros Encontrados ({{ importData.errors.length }})
                        </div>
                    </q-card-section>
                    
                    <q-card-section>
                        <q-banner class="bg-warning text-black q-mb-md">
                            <template v-slot:avatar>
                                <q-icon name="info" />
                            </template>
                            Os erros listados abaixo ocorreram durante o processo de importação:
                        </q-banner>

                        <q-table
                            :rows="importData.errors.map((error, index) => ({ index: index + 1, error }))"
                            :columns="[
                                { name: 'index', label: '#', align: 'left', field: 'index' },
                                { name: 'error', label: 'Erro', align: 'left', field: 'error' }
                            ]"
                            row-key="index"
                            flat
                            bordered
                            :pagination="{ rowsPerPage: 10 }"
                        >
                            <template v-slot:body-cell-index="props">
                                <q-td :props="props">
                                    <q-chip size="sm" color="grey" text-color="white">
                                        {{ props.value }}
                                    </q-chip>
                                </q-td>
                            </template>
                        </q-table>
                    </q-card-section>
                </q-card>
            </div>

            <!-- Ações -->
            <div class="col-12">
                <q-card>
                    <q-card-section>
                        <div class="text-h6 q-mb-md">Ações</div>
                        <q-btn
                            color="negative"
                            icon="delete"
                            label="Remover Registro"
                            @click="deleteImport"
                            :loading="isLoading"
                        />
                    </q-card-section>
                </q-card>
            </div>
        </div>

        <!-- Estado de erro -->
        <div v-else class="flex flex-center q-pa-xl">
            <q-card class="text-center">
                <q-card-section>
                    <q-icon name="error" size="50px" color="negative" />
                    <div class="text-h6 q-mt-md">Importação não encontrada</div>
                </q-card-section>
            </q-card>
        </div>
    </q-page>
</template>

<style scoped>
.q-table th {
    font-weight: bold;
}
</style>