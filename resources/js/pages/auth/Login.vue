<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const isSubmitting = ref(false);

const submit = async () => {
    isSubmitting.value = true;
    await form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <AuthBase title=" " description="Insira seu e-mail e senha abaixo para fazer login">
        <Head title="Login" />

        <!-- Mensagem de Status -->
        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- Formulário de Login -->
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Campo de E-mail -->
                <div class="grid gap-2">
                    <Label for="email">E-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Campo de Senha -->
                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Senha</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="5">
                            Esqueceu sua senha?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Senha"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <!-- Caixa de Seleção "Lembrar-me" -->
                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                        <span>Lembrar-me</span>
                    </Label>
                </div>

                <!-- Botão de Envio -->
                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="isSubmitting || form.processing">
                    <LoaderCircle v-if="isSubmitting || form.processing" class="h-4 w-4 animate-spin mr-2" />
                    Entrar
                </Button>
            </div>

            <!-- Link para Registro -->
            <div class="text-center text-sm text-muted-foreground">
                Não tem uma conta?
                <TextLink :href="route('register')" :tabindex="5">Cadastre-se</TextLink>
            </div>
        </form>
    </AuthBase>
</template>