<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Importações" />

    <div class="flex flex-col space-y-6 max-w-2xl mx-auto">
      <HeadingSmall
        title="Importar Planilha"
        description="Faça upload de arquivos para importar dados no sistema"
      />

      <form @submit.prevent="onSubmit" class="space-y-6">
        <!-- Tipo de Importação -->
        <div class="grid gap-2">
          <Label for="type">Tipo de Importação</Label>
          <select
            id="type"
            v-model="type"
            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            required
          >
            <option value="" disabled>Selecione o tipo de importação</option>
            <option
              v-for="option in importTypes"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
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
              ✕
            </Button>
          </div>
          <div
            v-if="selectedFile"
            class="flex items-center gap-2 text-sm text-muted-foreground"
          >
            <span>📄</span>
            <span>{{ selectedFile.name }}</span>
            <span class="text-xs">({{ formatFileSize(selectedFile.size) }})</span>
          </div>
          <InputError class="mt-2" :message="errors.file" />
        </div>

        <!-- Botões de Ação -->
        <div class="flex items-center gap-4">
          <Button :disabled="loading" type="submit">
            <span v-if="!loading">📤</span>
            <span v-else class="animate-spin">⏳</span>
            <span class="ml-2">{{ loading ? "Importando..." : "Importar" }}</span>
          </Button>

          <Button
            v-if="selectedFile || type"
            type="button"
            variant="outline"
            @click="clearForm"
            :disabled="loading"
          >
            Limpar
          </Button>

          <Transition
            enter-active-class="transition ease-in-out"
            enter-from-class="opacity-0"
            leave-active-class="transition ease-in-out"
            leave-to-class="opacity-0"
          >
            <p v-show="recentlySuccessful" class="text-sm text-green-600">
              Importação realizada com sucesso!
            </p>
          </Transition>
        </div>

        <!-- Mensagem de Resultado -->
        <div v-if="message" class="mt-4">
          <div
            :class="[
              'rounded-lg p-4 text-sm',
              messageType === 'success'
                ? 'bg-green-50 text-green-800 border border-green-200'
                : 'bg-red-50 text-red-800 border border-red-200',
            ]"
          >
            <div class="flex items-center gap-2">
              <span v-if="messageType === 'success'">✅</span>
              <span v-else>⚠️</span>
              <span>{{ message }}</span>
            </div>
          </div>
        </div>
      </form>

      <!-- Informações Adicionais -->
      <div class="rounded-lg border border-border p-4 bg-muted/50">
        <h4 class="font-medium mb-2">Formatos aceitos</h4>
        <ul class="text-sm text-muted-foreground space-y-1">
          <li>• Arquivos Excel (.xlsx)</li>
          <li>• Arquivos CSV (.csv)</li>
          <li>• Tamanho máximo: 10MB</li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { notify } from '@kyvg/vue3-notification'

import AppLayout from '@/layouts/AppLayout.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
// Removido: Select components não disponíveis

import { type BreadcrumbItem } from '@/types';

interface ImportType {
  label: string;
  value: string;
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Importações',
    href: '/imports',
  },
];

// Reactive state
const type = ref<string>('');
const selectedFile = ref<File | null>(null);
const loading = ref<boolean>(false);
const message = ref<string>('');
const messageType = ref<'success' | 'error'>('success');
const recentlySuccessful = ref<boolean>(false);
const errors = ref<Record<string, string>>({});
// Import types options
const importTypes: ImportType[] = [
  { label: 'Emissoras', value: 'emissoras' },
  { label: 'Placas', value: 'placas' },
  { label: 'Portais', value: 'portais' },
];

// File handling
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
  message.value = '';
  errors.value = {};
};

// Utility functions
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Form validation
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

// Form submission handler
const onSubmit = async () => {
  if (!validateForm()) return;

  loading.value = true;
  message.value = '';
  recentlySuccessful.value = false;

  const formData = new FormData();
  formData.append('type', type.value);
  formData.append('file', selectedFile.value!);

  try {
    const response = await axios.post('/imports/process', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    message.value = response.data.message || 'Planilha importada com sucesso!';
    messageType.value = 'success';
    recentlySuccessful.value = true;

    // Clear form on success
    setTimeout(() => {
      clearForm();
      recentlySuccessful.value = false;
    }, 3000);

  } catch (error: any) {
    const errorMessage = error.response?.data?.message || 'Erro ao importar planilha.';
    message.value = errorMessage;
    messageType.value = 'error';

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
  } finally {
    loading.value = false;
  }
};
</script>
