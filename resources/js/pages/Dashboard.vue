<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
  kpis: {
    agendamentos_hoje: number;
    realizados_hoje: number;
    pendentes_hoje: number;
    em_atendimento: number;
    em_espera: number;
    total_pacientes: number;
    novos_pacientes_hoje: number;
    taxa_comparecimento: number;
  };
  agendamentos_dia: Array<{
    id: number;
    hora_inicio: string;
    hora_fim: string;
    status: string;
    paciente: { nome: string };
    cliente: { nome: string };
    tipo_servico: { nome: string; cor: string } | null;
    veterinario: { name: string };
  }>;
  estoque_critico: Array<{
    id: number;
    nome: string;
    quantidade_estoque: string;
    estoque_minimo: string;
    unidade_medida: string;
  }>;
  financeiro_mes: {
    receitas: number;
    despesas: number;
    saldo: number;
  };
  atendimentos_chart: Array<{
    data: string;
    label: string;
    count: number;
  }>;
  financeiro_chart: Array<{
    data: string;
    label: string;
    receitas: number;
    despesas: number;
  }>;
  proximas_vacinacoes: Array<{
    id: number;
    paciente: { id: number; nome: string };
    vacina_nome: string;
    proxima_dose_em: string;
  }>;
}>();

const hoje = new Date();
const dataFormatada = hoje.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });

function formatCurrency(v: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);
}

function statusColor(status: string) {
  const map: Record<string, string> = {
    agendado: 'bg-blue-100 text-blue-700',
    confirmado: 'bg-green-100 text-green-700',
    aguardando: 'bg-yellow-100 text-yellow-700',
    em_atendimento: 'bg-primary-100 text-primary-700',
    finalizado: 'bg-gray-100 text-gray-500',
    cancelado: 'bg-red-100 text-red-500',
  }

  return map[status] ?? 'bg-gray-100 text-gray-600'
}

function diasAteVencimento(data: string) {
  const hoje = new Date()
  const vencimento = new Date(data)
  const diff = Math.floor((vencimento.getTime() - hoje.getTime()) / (1000 * 60 * 60 * 24))

  return diff
}

// Gráfico de Atendimentos (últimos 7 dias)
const atendimentosChartOptions = computed(() => ({
  chart: {
    type: 'area' as const,
    height: 280,
    toolbar: { show: false },
    zoom: { enabled: false },
  },
  colors: ['#00c1b3'],
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth' as const,
    width: 3,
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.1,
      stops: [0, 90, 100],
    },
  },
  xaxis: {
    categories: props.atendimentos_chart.map(d => d.label),
    labels: {
      style: {
        colors: '#9CA3AF',
        fontSize: '12px',
      },
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: {
        colors: '#9CA3AF',
        fontSize: '12px',
      },
    },
  },
  grid: {
    borderColor: '#F3F4F6',
    strokeDashArray: 4,
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: (val: number) => `${val} atendimentos`,
    },
  },
}));

const atendimentosSeries = computed(() => [{
  name: 'Atendimentos',
  data: props.atendimentos_chart.map(d => d.count),
}]);

// Gráfico de Receitas vs Despesas (últimos 30 dias)
const financeiroChartOptions = computed(() => ({
  chart: {
    type: 'area' as const,
    height: 320,
    toolbar: { show: false },
    zoom: { enabled: false },
  },
  colors: ['#10B981', '#EF4444'],
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth' as const,
    width: 2,
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.3,
      opacityTo: 0.05,
      stops: [0, 90, 100],
    },
  },
  xaxis: {
    categories: props.financeiro_chart.map(d => d.label),
    labels: {
      style: {
        colors: '#9CA3AF',
        fontSize: '11px',
      },
      rotate: -45,
      rotateAlways: true,
    },
    axisBorder: { show: false },
    axisTicks: { show: false },
  },
  yaxis: {
    labels: {
      style: {
        colors: '#9CA3AF',
        fontSize: '12px',
      },
      formatter: (val: number) => formatCurrency(val),
    },
  },
  grid: {
    borderColor: '#F3F4F6',
    strokeDashArray: 4,
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: (val: number) => formatCurrency(val),
    },
  },
  legend: {
    position: 'top' as const,
    horizontalAlign: 'right' as const,
    fontSize: '13px',
    fontWeight: 500,
  },
}));

const financeiroSeries = computed(() => [
  {
    name: 'Receitas',
    data: props.financeiro_chart.map(d => d.receitas),
  },
  {
    name: 'Despesas',
    data: props.financeiro_chart.map(d => d.despesas),
  },
]);

// Gauge para taxa de comparecimento
const taxaGaugeOptions = computed(() => ({
  chart: {
    type: 'radialBar' as const,
    height: 200,
  },
  colors: ['#00c1b3'],
  plotOptions: {
    radialBar: {
      hollow: {
        size: '60%',
      },
      dataLabels: {
        name: {
          show: true,
          fontSize: '13px',
          fontWeight: 500,
          color: '#6B7280',
          offsetY: 10,
        },
        value: {
          show: true,
          fontSize: '28px',
          fontWeight: 700,
          color: '#111827',
          offsetY: -10,
          formatter: (val: number) => `${val}%`,
        },
      },
    },
  },
  labels: ['Taxa de comparecimento'],
}));

const taxaGaugeSeries = computed(() => [props.kpis.taxa_comparecimento]);
</script>

<template>
  <div class="h-full overflow-y-auto bg-gray-50 px-8 py-6">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Resumo</h1>
        <p class="mt-1 text-sm text-gray-500">Visão geral da sua clínica</p>
      </div>
      <div
        class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 shadow-sm">
        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
          <line x1="16" y1="2" x2="16" y2="6" />
          <line x1="8" y1="2" x2="8" y2="6" />
          <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
        <span class="ml-1 font-medium">{{ dataFormatada }}</span>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="mb-6 grid grid-cols-4 gap-4">
      <!-- Consultas hoje -->
      <div class="group rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
          </div>
          <span class="text-xs font-medium text-green-600">+{{ kpis.realizados_hoje }}</span>
        </div>
        <p class="text-sm font-medium text-gray-500">Consultas hoje</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ kpis.agendamentos_hoje }}</p>
        <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
          <span class="font-medium text-yellow-600">{{ kpis.pendentes_hoje }} pendentes</span>
          <span>•</span>
          <Link href="/agenda" class="font-medium text-primary-600 hover:underline">Ver agenda</Link>
        </div>
      </div>

      <!-- Pacientes -->
      <div class="group rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
              <circle cx="12" cy="7" r="4" />
            </svg>
          </div>
          <span class="text-xs font-medium text-green-600">+{{ kpis.novos_pacientes_hoje }}</span>
        </div>
        <p class="text-sm font-medium text-gray-500">Total de pacientes</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ kpis.total_pacientes }}</p>
        <div class="mt-3 text-xs">
          <Link href="/pacientes" class="font-medium text-primary-600 hover:underline">Ver todos</Link>
        </div>
      </div>

      <!-- Em atendimento -->
      <div class="group rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="h-2 w-2 animate-pulse rounded-full bg-purple-500"></div>
        </div>
        <p class="text-sm font-medium text-gray-500">Em atendimento</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ kpis.em_atendimento }}</p>
        <div class="mt-3 text-xs text-gray-500">
          <span>{{ kpis.em_espera }} em espera</span>
        </div>
      </div>

      <!-- Receita do mês -->
      <div class="group rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition-all hover:shadow-md">
        <div class="mb-3 flex items-center justify-between">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="1" x2="12" y2="23" />
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            </svg>
          </div>
          <span :class="financeiro_mes.saldo >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
            {{ financeiro_mes.saldo >= 0 ? '↑' : '↓' }} {{ formatCurrency(Math.abs(financeiro_mes.saldo)) }}
          </span>
        </div>
        <p class="text-sm font-medium text-gray-500">Receita do mês</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ formatCurrency(financeiro_mes.receitas) }}</p>
        <div class="mt-3 text-xs text-gray-500">
          <span>Despesas: {{ formatCurrency(financeiro_mes.despesas) }}</span>
        </div>
      </div>
    </div>

    <!-- Gráficos e Cards  -->
    <div class="grid grid-cols-3 gap-6">
      <!-- Coluna principal (2/3) -->
      <div class="col-span-2 space-y-6">
        <!-- Gráfico de Atendimentos -->
        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold text-gray-900">Atendimentos realizados</h3>
              <p class="text-sm text-gray-500">Últimos 7 dias</p>
            </div>
            <div class="rounded-lg bg-primary-50 px-3 py-1.5">
              <span class="text-sm font-semibold text-primary-600">
                {{atendimentos_chart.reduce((sum, d) => sum + d.count, 0)}} total
              </span>
            </div>
          </div>
          <VueApexCharts type="area" height="280" :options="atendimentosChartOptions" :series="atendimentosSeries" />
        </div>

        <!-- Gráfico Financeiro -->
        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
          <div class="mb-4">
            <h3 class="text-base font-semibold text-gray-900">Receitas vs Despesas</h3>
            <p class="text-sm text-gray-500">Últimos 30 dias</p>
          </div>
          <VueApexCharts type="area" height="320" :options="financeiroChartOptions" :series="financeiroSeries" />
        </div>

        <!-- Taxa de comparecimento -->
        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
          <h3 class="mb-4 text-base font-semibold text-gray-900">Taxa de comparecimento</h3>
          <div class="flex items-center justify-center">
            <VueApexCharts type="radialBar" height="200" :options="taxaGaugeOptions" :series="taxaGaugeSeries" />
          </div>
          <p class="mt-2 text-center text-sm text-gray-500">Últimos 30 dias</p>
        </div>
      </div>

      <!-- Coluna lateral (1/3) -->
      <div class="space-y-6">
        <!-- Agendamentos de hoje -->
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Hoje</h3>
            <span class="text-xs text-gray-400">{{ agendamentos_dia.length }} agendamentos</span>
          </div>

          <div v-if="!agendamentos_dia.length" class="py-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
              stroke-width="1.5">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <p class="mt-2 text-sm text-gray-400">Nenhum agendamento</p>
          </div>

          <div class="space-y-2.5">
            <div v-for="ag in agendamentos_dia.slice(0, 5)" :key="ag.id"
              class="rounded-lg border border-gray-100 p-3 transition-all hover:border-primary-200 hover:bg-primary-50/30">
              <div class="mb-1 flex items-start justify-between gap-2">
                <p class="text-sm font-medium leading-tight text-gray-800">{{ ag.paciente?.nome }}</p>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium" :class="statusColor(ag.status)">
                  {{ ag.status.replace('_', ' ') }}
                </span>
              </div>
              <p class="text-xs text-gray-500">{{ ag.tipo_servico?.nome ?? 'Consulta' }}</p>
              <div class="mt-1.5 flex items-center justify-between text-xs">
                <span class="font-medium text-gray-600">{{ ag.hora_inicio }}</span>
                <span class="text-gray-400">{{ ag.veterinario?.name }}</span>
              </div>
            </div>
          </div>

          <Link v-if="agendamentos_dia.length > 5" href="/agenda"
            class="mt-3 block text-center text-xs font-medium text-primary-600 hover:underline">
            Ver todos ({{ agendamentos_dia.length }})
          </Link>
        </div>

        <!-- Estoque crítico -->
        <div v-if="estoque_critico.length" class="rounded-xl border border-red-100 bg-red-50/30 p-5 shadow-sm">
          <div class="mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="text-sm font-semibold text-red-900">Estoque crítico</h3>
          </div>
          <div class="space-y-2">
            <div v-for="p in estoque_critico" :key="p.id"
              class="flex items-center justify-between rounded-lg bg-white p-2.5 text-sm">
              <span class="font-medium text-gray-700">{{ p.nome }}</span>
              <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                {{ p.quantidade_estoque }} {{ p.unidade_medida }}
              </span>
            </div>
          </div>
          <Link href="/estoque?tab=baixo_estoque"
            class="mt-3 block text-center text-xs font-medium text-red-600 hover:underline">
            Ver estoque completo
          </Link>
        </div>

        <!-- Próximas vacinações -->
        <div v-if="proximas_vacinacoes.length"
          class="rounded-xl border border-yellow-100 bg-yellow-50/30 p-5 shadow-sm">
          <div class="mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-sm font-semibold text-yellow-900">Próximas vacinações</h3>
          </div>
          <div class="space-y-2">
            <div v-for="v in proximas_vacinacoes" :key="v.id" class="rounded-lg bg-white p-2.5">
              <p class="text-sm font-medium text-gray-800">{{ v.paciente?.nome }}</p>
              <p class="text-xs text-gray-600">{{ v.vacina_nome }}</p>
              <div class="mt-1 flex items-center justify-between">
                <span class="text-xs text-gray-500">
                  {{ new Date(v.proxima_dose_em).toLocaleDateString('pt-BR') }}
                </span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                  :class="diasAteVencimento(v.proxima_dose_em) <= 7 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'">
                  {{ diasAteVencimento(v.proxima_dose_em) }}d
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
