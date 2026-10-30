<script setup lang="ts">
import { ref, computed } from 'vue';

function pageLabel(label: string): string {
    return label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');
}
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type Documento = {
    id: number;
    nome: string;
    arquivo_url: string;
    mime_type: string;
    tamanho_bytes: number;
    categoria: string;
    created_at: string;
    uploaded_by: string | null;
    documentavel_type: string | null;
    documentavel_id: number | null;
    documentavel_nome: string | null;
};

type UploadItem = {
    id: string;
    file: File;
    nome: string;
    categoria: string;
    progresso: number;
    status: 'aguardando' | 'enviando' | 'processando' | 'concluido' | 'erro';
    erro: string | null;
};

const props = defineProps<{
    documentos: {
        data: Documento[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    storage: { usado_gb: number; limite_gb: number; percentual: number };
    filters: { search?: string; categoria?: string; data_inicio?: string; data_fim?: string };
}>();

// ── Filtros ───────────────────────────────────────────────────────────────────
const search = ref(props.filters.search ?? '');
const filtroCategoria = ref(props.filters.categoria ?? '');
const dataInicio = ref(props.filters.data_inicio ?? '');
const dataFim = ref(props.filters.data_fim ?? '');

function buscar() {
    router.get('/documentos', {
        search: search.value,
        categoria: filtroCategoria.value,
        data_inicio: dataInicio.value || undefined,
        data_fim: dataFim.value || undefined,
    }, { preserveState: true, replace: true });
}

function limparFiltros() {
    search.value = '';
    filtroCategoria.value = '';
    dataInicio.value = '';
    dataFim.value = '';
    buscar();
}

// ── Drag & Drop ───────────────────────────────────────────────────────────────
const isDragging = ref(false);
const fila = ref<UploadItem[]>([]);

function onDragOver(e: DragEvent) {
    e.preventDefault();
    isDragging.value = true;
}

function onDragLeave() {
    isDragging.value = false;
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    isDragging.value = false;
    adicionarArquivos(Array.from(e.dataTransfer?.files ?? []));
}

function onFileInput(e: Event) {
    adicionarArquivos(Array.from((e.target as HTMLInputElement).files ?? []));
    (e.target as HTMLInputElement).value = '';
}

function adicionarArquivos(files: File[]) {
    for (const file of files) {
        fila.value.push({
            id: Math.random().toString(36).slice(2),
            file,
            nome: file.name,
            categoria: file.type.startsWith('image/') ? 'imagem' : 'outro',
            progresso: 0,
            status: 'aguardando',
            erro: null,
        });
    }
}

function removerDaFila(id: string) {
    fila.value = fila.value.filter(i => i.id !== id);
}

const temPendentes = computed(() => fila.value.some(i => i.status === 'aguardando'));

async function enviarTodos() {
    const pendentes = fila.value.filter(i => i.status === 'aguardando');
    // Enviar em paralelo (máx 3 simultâneos)
    const chunks: UploadItem[][] = [];
    for (let i = 0; i < pendentes.length; i += 3) chunks.push(pendentes.slice(i, i + 3));
    for (const chunk of chunks) {
        await Promise.all(chunk.map(uploadItem));
    }
    const concluidosCount = fila.value.filter(i => i.status === 'concluido').length;
    if (concluidosCount > 0) {
        router.reload({ only: ['documentos', 'storage'] });
    }
}

function getCsrf(): string {
    const token = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '';
    return decodeURIComponent(token);
}

async function uploadItem(item: UploadItem) {
    item.status = 'enviando';
    item.progresso = 0;

    try {
        // Upload através do servidor Laravel (igual aos outros módulos)
        const formData = new FormData();
        formData.append('arquivo', item.file);
        formData.append('nome', item.nome);
        formData.append('categoria', item.categoria);

        await new Promise<void>((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/documentos');
            xhr.setRequestHeader('X-XSRF-TOKEN', getCsrf());
            xhr.setRequestHeader('Accept', 'application/json');
            
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const progress = Math.round((e.loaded / e.total) * 100);
                    item.progresso = progress;
                    // Quando upload completo, servidor está processando
                    if (progress === 100) {
                        item.status = 'processando';
                    }
                }
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve();
                } else {
                    const err = JSON.parse(xhr.responseText);
                    reject(new Error(err.error ?? err.message ?? `Erro ${xhr.status}`));
                }
            };

            xhr.onerror = () => reject(new Error('Falha de rede.'));
            xhr.send(formData);
        });

        item.status = 'concluido';
    } catch (e: any) {
        item.status = 'erro';
        item.erro = e.message ?? 'Erro desconhecido';
    }
}

// ── Preview ───────────────────────────────────────────────────────────────────
const preview = ref<Documento | null>(null);
const previewUrl = ref<string | null>(null);
const loadingPreview = ref(false);

async function abrirPreview(doc: Documento) {
    if (doc.mime_type?.startsWith('image/') || doc.mime_type === 'application/pdf') {
        preview.value = doc;
        loadingPreview.value = true;
        previewUrl.value = null;
        
        try {
            // Obter URL assinada temporária
            const res = await fetch(`/documentos/${doc.id}/signed-url`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            previewUrl.value = data.url;
        } catch (e) {
            console.error('Erro ao obter URL:', e);
            preview.value = null;
        } finally {
            loadingPreview.value = false;
        }
    } else {
        // Para outros tipos, baixar diretamente
        baixarDocumento(doc);
    }
}

async function baixarDocumento(doc: Documento) {
    try {
        const res = await fetch(`/documentos/${doc.id}/signed-url`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        window.open(data.url, '_blank');
    } catch (e) {
        console.error('Erro ao baixar documento:', e);
    }
}

// ── Excluir ───────────────────────────────────────────────────────────────────
function excluir(doc: Documento) {
    if (!confirm(`Excluir "${doc.nome}"?`)) return;
    router.delete(`/documentos/${doc.id}`);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtBytes(b: number) {
    if (b < 1024) return `${b} B`;
    if (b < 1024 ** 2) return `${(b / 1024).toFixed(1)} KB`;
    if (b < 1024 ** 3) return `${(b / 1024 ** 2).toFixed(1)} MB`;
    return `${(b / 1024 ** 3).toFixed(2)} GB`;
}

function categoriaLabel(c: string) {
    return { exame: 'Exame', receita: 'Receita', atestado: 'Atestado', contrato: 'Contrato', imagem: 'Imagem', outro: 'Outro' }[c] ?? c;
}

function categoriaColor(c: string) {
    const m: Record<string, string> = {
        exame: 'bg-blue-50 text-blue-600', receita: 'bg-green-50 text-green-600',
        atestado: 'bg-purple-50 text-purple-600', contrato: 'bg-orange-50 text-orange-600',
        imagem: 'bg-pink-50 text-pink-600', outro: 'bg-gray-100 text-gray-500',
    };
    return m[c] ?? 'bg-gray-100 text-gray-500';
}

function fileIcon(mime: string) {
    if (mime?.startsWith('image/')) return '🖼️';
    if (mime === 'application/pdf') return '📄';
    if (mime?.includes('word')) return '📝';
    if (mime?.includes('excel') || mime?.includes('sheet')) return '📊';
    if (mime?.startsWith('video/')) return '🎥';
    return '📎';
}

const storageColor = computed(() => {
    if (props.storage.percentual >= 90) return 'bg-red-400';
    if (props.storage.percentual >= 70) return 'bg-orange-400';
    return 'bg-primary-400';
});
</script>

<template>
    <div class="px-8 py-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-900">Documentos</h1>
            <label class="flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Enviar arquivo
                <input type="file" multiple class="sr-only" @change="onFileInput" />
            </label>
        </div>

        <!-- Barra de storage -->
        <div class="mb-6 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="mb-2 flex items-center justify-between text-xs">
                <span class="font-medium text-gray-600">Armazenamento utilizado</span>
                <span class="text-gray-500">{{ storage.usado_gb.toFixed(2) }} GB de {{ storage.limite_gb }} GB ({{ storage.percentual }}%)</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full transition-all duration-500" :class="storageColor" :style="`width:${storage.percentual}%`"></div>
            </div>
            <p v-if="storage.percentual >= 90" class="mt-1.5 text-xs font-medium text-red-500">
                ⚠️ Espaço quase esgotado. Considere fazer upgrade do plano.
            </p>
        </div>

        <!-- Zona drag & drop -->
        <div
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            class="mb-6 flex flex-col items-center justify-center rounded-xl border-2 border-dashed py-10 transition-colors"
            :class="isDragging ? 'border-primary-400 bg-primary-50' : 'border-gray-200 bg-gray-50/40 hover:border-gray-300'"
        >
            <svg class="mb-3 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            <p class="text-sm text-gray-500">
                <span v-if="isDragging" class="font-semibold text-primary-600">Solte para adicionar</span>
                <span v-else>Arraste arquivos aqui ou <label class="cursor-pointer text-primary-600 hover:underline">selecione do computador<input type="file" multiple class="sr-only" @change="onFileInput" /></label></span>
            </p>
            <p class="mt-1 text-xs text-gray-400">PDF, imagens, documentos — até 500 MB por arquivo</p>
        </div>

        <!-- Fila de upload -->
        <div v-if="fila.length" class="mb-6 rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-700">
                    Fila de upload
                    <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-gray-500">{{ fila.length }}</span>
                </h2>
                <div class="flex gap-2">
                    <button v-if="fila.some(i => i.status === 'concluido')"
                        @click="fila = fila.filter(i => i.status !== 'concluido')"
                        class="text-xs text-gray-400 hover:text-gray-600">Limpar concluídos</button>
                    <button v-if="temPendentes" @click="enviarTodos"
                        class="rounded-lg bg-primary-500 px-4 py-1.5 text-xs font-semibold text-white hover:bg-primary-600">
                        Enviar todos
                    </button>
                </div>
            </div>
            <ul class="divide-y divide-gray-50">
                <li v-for="item in fila" :key="item.id" class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="shrink-0 text-xl">{{ fileIcon(item.file.type) }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="mb-1.5 flex items-center gap-2">
                                <input v-model="item.nome" type="text"
                                    class="min-w-0 flex-1 rounded border border-gray-200 px-2 py-0.5 text-xs outline-none focus:border-primary-400"
                                    :disabled="item.status !== 'aguardando'" />
                                <select v-model="item.categoria"
                                    class="shrink-0 rounded border border-gray-200 px-2 py-0.5 text-xs outline-none focus:border-primary-400"
                                    :disabled="item.status !== 'aguardando'">
                                    <option value="exame">Exame</option>
                                    <option value="receita">Receita</option>
                                    <option value="atestado">Atestado</option>
                                    <option value="contrato">Contrato</option>
                                    <option value="imagem">Imagem</option>
                                    <option value="outro">Outro</option>
                                </select>
                                <span class="shrink-0 text-[10px] text-gray-400">{{ fmtBytes(item.file.size) }}</span>
                            </div>
                            <!-- Barra de progresso -->
                            <div v-if="item.status === 'enviando' || item.status === 'processando'" class="space-y-1">
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full bg-primary-400 transition-all" :style="`width:${item.progresso}%`"></div>
                                </div>
                                <p v-if="item.status === 'processando'" class="text-[10px] text-gray-400">
                                    Salvando no servidor...
                                </p>
                            </div>
                            <p v-if="item.status === 'erro'" class="mt-0.5 text-xs text-red-500">{{ item.erro }}</p>
                        </div>
                        <!-- Estado -->
                        <div class="shrink-0 w-16 text-right">
                            <svg v-if="item.status === 'concluido'" class="ml-auto h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else-if="item.status === 'erro'" class="ml-auto h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span v-else-if="item.status === 'enviando'" class="text-xs font-semibold text-primary-500">{{ item.progresso }}%</span>
                            <div v-else-if="item.status === 'processando'" class="ml-auto flex items-center justify-end">
                                <div class="h-4 w-4 animate-spin rounded-full border-2 border-gray-200 border-t-primary-500"></div>
                            </div>
                            <button v-else @click="removerDaFila(item.id)" class="ml-auto text-gray-300 hover:text-gray-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Filtros -->
        <div class="mb-4 flex items-center gap-3">
            <div class="relative">
                <input v-model="search" @keydown.enter="buscar" type="text" placeholder="Buscar documento..."
                    class="w-60 rounded-lg border border-gray-200 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-500" />
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
            <select v-model="filtroCategoria" @change="buscar" class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500">
                <option value="">Todas as categorias</option>
                <option value="exame">Exame</option>
                <option value="receita">Receita</option>
                <option value="atestado">Atestado</option>
                <option value="contrato">Contrato</option>
                <option value="imagem">Imagem</option>
                <option value="outro">Outro</option>
            </select>
            <div>
                <input v-model="dataInicio" type="date"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500"
                    placeholder="Data início" />
            </div>
            <div>
                <input v-model="dataFim" type="date"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-primary-500"
                    placeholder="Data fim" />
            </div>
            <button @click="buscar" class="rounded-lg bg-primary-500 px-4 py-2 text-sm text-white hover:bg-primary-600">
                Filtrar
            </button>
            <button v-if="search || filtroCategoria || dataInicio || dataFim" @click="limparFiltros"
                class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                Limpar
            </button>
        </div>

        <!-- Lista de documentos -->
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <th class="px-5 py-3 text-left">Arquivo</th>
                        <th class="px-5 py-3 text-left">Categoria</th>
                        <th class="px-5 py-3 text-left">Vinculado a</th>
                        <th class="px-5 py-3 text-left">Enviado por</th>
                        <th class="px-5 py-3 text-right">Tamanho</th>
                        <th class="px-5 py-3 text-left">Data</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="!documentos.data.length">
                        <td colspan="7" class="py-14 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <span class="text-5xl">📁</span>
                                <p class="text-sm font-medium text-gray-400">Nenhum documento encontrado</p>
                                <p class="text-xs text-gray-300">Arraste arquivos para a área acima para começar</p>
                            </div>
                        </td>
                    </tr>
                    <tr v-for="doc in documentos.data" :key="doc.id" class="hover:bg-gray-50/50">
                        <td class="px-5 py-3">
                            <button @click="abrirPreview(doc)" class="flex items-center gap-2 text-left hover:text-primary-600">
                                <span class="text-lg">{{ fileIcon(doc.mime_type) }}</span>
                                <span class="max-w-48 truncate font-medium text-gray-800">{{ doc.nome }}</span>
                            </button>
                        </td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="categoriaColor(doc.categoria)">
                                {{ categoriaLabel(doc.categoria) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500">{{ doc.documentavel_nome ?? '—' }}</td>
                        <td class="px-5 py-3 text-xs text-gray-500">{{ doc.uploaded_by ?? '—' }}</td>
                        <td class="px-5 py-3 text-right text-xs text-gray-400">{{ fmtBytes(doc.tamanho_bytes) }}</td>
                        <td class="px-5 py-3 text-xs text-gray-500">{{ doc.created_at }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="baixarDocumento(doc)"
                                    class="rounded p-1 text-gray-400 hover:text-primary-500" title="Baixar">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </button>
                                <button @click="excluir(doc)" class="rounded p-1 text-gray-400 hover:text-red-500" title="Excluir">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6m4-6v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginação -->
            <div v-if="documentos.last_page > 1" class="flex items-center justify-center gap-1 border-t border-gray-50 px-5 py-3">
                <template v-for="link in documentos.links" :key="link.label">
                    <button v-if="link.url" @click="router.get(link.url)"
                        class="rounded px-2.5 py-1 text-xs"
                        :class="link.active ? 'bg-primary-500 text-white' : 'text-gray-500 hover:bg-gray-100'"
                        >{{ pageLabel(link.label) }}</button>
                    <span v-else class="px-1 text-xs text-gray-300">{{ pageLabel(link.label) }}</span>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Preview -->
    <div v-if="preview" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="preview = null">
        <div class="relative flex max-h-[90vh] w-full max-w-4xl flex-col rounded-xl bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-3">
                <span class="max-w-lg truncate text-sm font-medium text-gray-800">{{ preview.nome }}</span>
                <div class="flex items-center gap-2">
                    <button @click="baixarDocumento(preview)"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                        Baixar
                    </button>
                    <button @click="preview = null" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-auto p-2">
                <div v-if="loadingPreview" class="flex h-[75vh] items-center justify-center">
                    <div class="text-center">
                        <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-primary-500"></div>
                        <p class="text-sm text-gray-400">Carregando...</p>
                    </div>
                </div>
                <img v-else-if="preview.mime_type?.startsWith('image/') && previewUrl"
                    :src="previewUrl" :alt="preview.nome"
                    class="mx-auto max-h-[75vh] rounded object-contain" />
                <iframe v-else-if="preview.mime_type === 'application/pdf' && previewUrl"
                    :src="previewUrl"
                    class="h-[75vh] w-full rounded border-0"></iframe>
            </div>
        </div>
    </div>
</template>
