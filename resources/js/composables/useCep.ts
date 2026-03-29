import { ref } from 'vue';

export type EnderecoViaCep = {
    logradouro: string;
    bairro: string;
    cidade: string;
    estado: string;
};

export function useCep() {
    const buscandoCep = ref(false);
    const cepNaoEncontrado = ref(false);

    async function buscarCep(cep: string): Promise<EnderecoViaCep | null> {
        const digits = cep.replace(/\D/g, '');
        if (digits.length !== 8) return null;

        buscandoCep.value = true;
        cepNaoEncontrado.value = false;

        try {
            const res = await fetch(`https://viacep.com.br/ws/${digits}/json/`);
            const data = await res.json();

            if (data.erro) {
                cepNaoEncontrado.value = true;
                return null;
            }

            return {
                logradouro: data.logradouro ?? '',
                bairro: data.bairro ?? '',
                cidade: data.localidade ?? '',
                estado: data.uf ?? '',
            };
        } catch {
            return null;
        } finally {
            buscandoCep.value = false;
        }
    }

    return { buscarCep, buscandoCep, cepNaoEncontrado };
}
