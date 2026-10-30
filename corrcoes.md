Geral> OK

- avisos de erros do backend serem exibidos no front. Validar todos os campos obrigatórios do front, caso um campo obrigatório não seja informado já dá o aviso, exemplo, campo nome cliquei no campo e sai dele sem preencher, já exibir abaixo o aviso de que é obrigado.
- ao trocar de clinica vai para /dashboard essa rota não existe.

Cliente> OK

- mascara cpf, telefone e cep
- busca automática de cep campo UF com select
- ver cliente em clientes/1 não abre o modal drawner, fazer igual ao criar/editar cliente

Agenda> OK

- busca ser pelo tutor e depois selecionar seu pet, lembrando q podem ser vários pets.

Admin> OK

- clinicas: Fazer sistema funcionar baseado no fuso horário escolhido (conferir se já funciona) - cada unidade pode ter seu fuso horário diferente
- Usuários:
    - Não tem opção de criar perfis, já temos as roles, mas preciso definir os perfis, precisa ser customizável
    - Cada usuário precisa está vinculado com especialidades e no agendamento ao selecionar o profissional, exibir as especialidades desse profissional.
- Horários:
    - cada dia da semana de cada usuário precisa ter ao menos 2 intervalos por dia configuráveis exemplo prático:
      Intervalo 1: hora inicio, hora fim, Duração da consulta, Intervalo entre consultas
      Intervalo 2: hora inicio, hora fim, Duração da consulta, Intervalo entre consultas
    - Opção de ativar/desativar dia da semana, talvez um profissional só atenda 3 dias na semana

30/03/2026
PRONTUARIO

- prontuarios/consulta/XXX
  -Medicamentos: ter cadastro de medicamentos para usuário não ter que digitar na mão. (nome medicamento, dosagem, duração, frequência (ter cadastro de frenquencias), via de administração)
    - Procedimentos: ter cadastro de procedimentos, mesma situação usuario não pode ter q informar na mão toda vez.
    - Vacinas: ter cadastro de vacinas para usuario não ter que digitar na mão.
