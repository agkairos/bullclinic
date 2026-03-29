export interface User {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    crmv: string | null;
    cargo: string | null;
    ativo: boolean;
    clinica_ativa_id: number | null;
    clinica_ativa?: Clinica;
}

export interface Clinica {
    id: number;
    nome: string;
    slug: string;
    logo: string | null;
    status_pagamento: 'trial' | 'pendente' | 'pago' | 'atrasado' | 'cancelado';
    data_trial_fim: string | null;
    plano?: Plano;
}

export interface Plano {
    id: number;
    nome: string;
    slug: string;
}

export interface PageProps {
    auth: {
        user: User;
    };
    clinicas: Clinica[];
    flash: {
        success?: string;
        error?: string;
    };
    chat_nao_lidas: number;
    [key: string]: unknown;
}
