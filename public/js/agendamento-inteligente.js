/**
 * Sistema Inteligente de Agendamentos
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 30/10/2025 13:17
 * 
 * Funcionalidades:
 * - Geração automática de slots de horário baseado na configuração
 * - Validação de conflitos em tempo real
 * - Horário personalizado opcional
 * - Interface visual intuitiva
 */

// Variáveis globais (serão preenchidas pelo PHP)
let disponibilidades = {};
let agendamentosPorData = {};
let duracaoAula = 60;
let intervaloAula = 15;
let horarioSelecionado = null;

/**
 * Inicializa o sistema com dados do PHP
 */
function inicializarSistema(disp, agend, duracao, intervalo) {
    disponibilidades = disp;
    agendamentosPorData = agend;
    duracaoAula = duracao;
    intervaloAula = intervalo;
}

/**
 * Atualiza horários disponíveis quando data é selecionada
 */
function atualizarHorariosDisponiveis() {
    console.log('🔄 atualizarHorariosDisponiveis() chamada');
    
    const dataInput = document.getElementById('data_agendamento');
    const alertaData = document.getElementById('alerta_data');
    const containerHorarios = document.getElementById('container_horarios');
    
    console.log('Elementos encontrados:', {
        dataInput: !!dataInput,
        alertaData: !!alertaData,
        containerHorarios: !!containerHorarios,
        dataValue: dataInput?.value
    });
    
    if (!dataInput.value) {
        console.log('⚠️ Nenhuma data selecionada');
        return;
    }
    
    const dataSelecionada = new Date(dataInput.value + 'T00:00:00');
    const diaSemana = dataSelecionada.getDay();
    
    // Verifica se o dia da semana tem disponibilidade
    if (!disponibilidades[diaSemana] || disponibilidades[diaSemana].length === 0) {
        alertaData.textContent = '⚠️ Você não trabalha neste dia da semana. Escolha outra data.';
        alertaData.className = 'text-xs mt-2 text-red-600 font-medium';
        alertaData.classList.remove('hidden');
        containerHorarios.classList.add('hidden');
        dataInput.classList.add('border-red-500');
        return;
    }
    
    // Data válida
    alertaData.textContent = '✅ Data disponível!';
    alertaData.className = 'text-xs mt-2 text-green-600 font-medium';
    alertaData.classList.remove('hidden');
    dataInput.classList.remove('border-red-500');
    dataInput.classList.add('border-green-500');
    containerHorarios.classList.remove('hidden');
    
    // Gera horários disponíveis
    gerarHorariosDisponiveis(dataInput.value, diaSemana);
}

/**
 * Gera slots de horários baseado na configuração do professor
 */
function gerarHorariosDisponiveis(data, diaSemana) {
    const listaHorarios = document.getElementById('lista_horarios');
    listaHorarios.innerHTML = '';
    
    const intervalosDisponiveis = disponibilidades[diaSemana];
    const agendamentosNaData = agendamentosPorData[data] || [];
    
    let totalSlots = 0;
    
    // Para cada intervalo de disponibilidade do dia
    intervalosDisponiveis.forEach(intervalo => {
        const slots = gerarSlots(intervalo.inicio, intervalo.fim, duracaoAula, intervaloAula);
        
        slots.forEach(slot => {
            // Verifica se o slot está livre (sem conflito)
            const temConflito = verificarConflito(slot.inicio, slot.fim, agendamentosNaData);
            
            const button = document.createElement('button');
            button.type = 'button';
            button.className = temConflito 
                ? 'px-3 py-2 text-xs border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'px-3 py-2 text-xs border-2 border-blue-300 rounded-lg hover:bg-blue-50 hover:border-blue-500 transition';
            
            button.innerHTML = `
                <div class="font-semibold">${slot.inicio} - ${slot.fim}</div>
                <div class="text-[10px] ${temConflito ? 'text-red-500' : 'text-gray-500'}">
                    ${temConflito ? '❌ Ocupado' : '✅ Livre'}
                </div>
            `;
            
            if (!temConflito) {
                button.onclick = () => selecionarHorario(slot.inicio, slot.fim, button);
                totalSlots++;
            } else {
                button.disabled = true;
                button.title = `Ocupado: ${temConflito.aluno}`;
            }
            
            listaHorarios.appendChild(button);
        });
    });
    
    // Se não há slots disponíveis
    if (totalSlots === 0) {
        listaHorarios.innerHTML = `
            <div class="col-span-full text-center py-6 text-gray-500">
                <i class="fas fa-calendar-times text-3xl mb-2"></i>
                <p class="text-sm">Não há horários disponíveis nesta data.</p>
                <p class="text-xs mt-1">Todos os horários estão ocupados.</p>
            </div>
        `;
    }
}

/**
 * Gera slots de horário com base na duração e intervalo
 */
function gerarSlots(horaInicio, horaFim, duracao, intervalo) {
    const slots = [];
    let [horaAtual, minutoAtual] = horaInicio.split(':').map(Number);
    const [horaFinal, minutoFinal] = horaFim.split(':').map(Number);
    
    const minutosInicio = horaAtual * 60 + minutoAtual;
    const minutosFim = horaFinal * 60 + minutoFinal;
    
    let minutosAtual = minutosInicio;
    
    while (minutosAtual + duracao <= minutosFim) {
        const inicio = minutosParaHora(minutosAtual);
        const fim = minutosParaHora(minutosAtual + duracao);
        
        slots.push({ inicio, fim });
        
        // Próximo slot = duração da aula + intervalo
        minutosAtual += duracao + intervalo;
    }
    
    return slots;
}

/**
 * Converte minutos para formato HH:MM
 */
function minutosParaHora(minutos) {
    const horas = Math.floor(minutos / 60);
    const mins = minutos % 60;
    return `${String(horas).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}

/**
 * Verifica se há conflito de horário
 */
function verificarConflito(inicio, fim, agendamentos) {
    for (const ag of agendamentos) {
        // Verifica sobreposição de horários
        if (
            (inicio >= ag.inicio && inicio < ag.fim) ||
            (fim > ag.inicio && fim <= ag.fim) ||
            (inicio <= ag.inicio && fim >= ag.fim)
        ) {
            return ag; // Retorna o agendamento conflitante
        }
    }
    return null;
}

/**
 * Seleciona um horário da lista
 */
function selecionarHorario(inicio, fim, botao) {
    // Remove seleção anterior
    document.querySelectorAll('#lista_horarios button').forEach(btn => {
        btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-600');
        btn.classList.add('border-blue-300');
    });
    
    // Adiciona seleção ao botão clicado
    botao.classList.add('bg-blue-500', 'text-white', 'border-blue-600');
    botao.classList.remove('border-blue-300');
    
    // Atualiza campos hidden
    document.getElementById('hora_inicio').value = inicio;
    document.getElementById('hora_fim').value = fim;
    
    horarioSelecionado = { inicio, fim };
    
    // Mostra confirmação
    const alertaHorario = document.getElementById('alerta_horario');
    alertaHorario.textContent = `✅ Horário selecionado: ${inicio} - ${fim}`;
    alertaHorario.className = 'text-xs mt-2 text-green-600 font-medium';
    alertaHorario.classList.remove('hidden');
}

/**
 * Toggle entre horário sugerido e personalizado
 */
function toggleHorarioPersonalizado() {
    const checkbox = document.getElementById('horario_personalizado');
    const horariosSugeridos = document.getElementById('horarios_sugeridos');
    const horarioManual = document.getElementById('horario_manual');
    
    if (checkbox.checked) {
        horariosSugeridos.classList.add('hidden');
        horarioManual.classList.remove('hidden');
        
        // Limpa seleção de horários sugeridos
        document.querySelectorAll('#lista_horarios button').forEach(btn => {
            btn.classList.remove('bg-blue-500', 'text-white', 'border-blue-600');
        });
        
        // Foca no campo de início
        document.getElementById('hora_inicio_manual').focus();
    } else {
        horariosSugeridos.classList.remove('hidden');
        horarioManual.classList.add('hidden');
        
        // Limpa campos manuais
        document.getElementById('hora_inicio_manual').value = '';
        document.getElementById('hora_fim_manual').value = '';
    }
}

/**
 * Valida horário personalizado
 */
function validarHorarioPersonalizado() {
    const inicioManual = document.getElementById('hora_inicio_manual').value;
    const fimManual = document.getElementById('hora_fim_manual').value;
    const alertaHorario = document.getElementById('alerta_horario');
    const dataInput = document.getElementById('data_agendamento');
    
    if (!inicioManual || !fimManual) return;
    
    // Valida se fim > início
    if (fimManual <= inicioManual) {
        alertaHorario.textContent = '⚠️ Horário de fim deve ser maior que o de início.';
        alertaHorario.className = 'text-xs mt-2 text-red-600 font-medium';
        alertaHorario.classList.remove('hidden');
        return;
    }
    
    // Verifica se está dentro da disponibilidade
    const dataSelecionada = new Date(dataInput.value + 'T00:00:00');
    const diaSemana = dataSelecionada.getDay();
    const intervalosDisponiveis = disponibilidades[diaSemana] || [];
    
    let dentroDisponibilidade = false;
    for (const intervalo of intervalosDisponiveis) {
        if (inicioManual >= intervalo.inicio && fimManual <= intervalo.fim) {
            dentroDisponibilidade = true;
            break;
        }
    }
    
    if (!dentroDisponibilidade) {
        alertaHorario.textContent = '⚠️ Horário fora da sua disponibilidade configurada.';
        alertaHorario.className = 'text-xs mt-2 text-orange-600 font-medium';
        alertaHorario.classList.remove('hidden');
    }
    
    // Verifica conflitos
    const agendamentosNaData = agendamentosPorData[dataInput.value] || [];
    const conflito = verificarConflito(inicioManual, fimManual, agendamentosNaData);
    
    if (conflito) {
        alertaHorario.textContent = `❌ Conflito com agendamento existente: ${conflito.aluno} (${conflito.inicio}-${conflito.fim})`;
        alertaHorario.className = 'text-xs mt-2 text-red-600 font-medium';
        alertaHorario.classList.remove('hidden');
        return;
    }
    
    // Horário válido
    alertaHorario.textContent = '✅ Horário personalizado válido!';
    alertaHorario.className = 'text-xs mt-2 text-green-600 font-medium';
    alertaHorario.classList.remove('hidden');
    
    // Atualiza campos hidden
    document.getElementById('hora_inicio').value = inicioManual;
    document.getElementById('hora_fim').value = fimManual;
}

/**
 * Limpa seleção ao fechar modal
 */
function limparSelecao() {
    horarioSelecionado = null;
    document.getElementById('hora_inicio').value = '';
    document.getElementById('hora_fim').value = '';
    document.getElementById('container_horarios').classList.add('hidden');
    
    const alertaData = document.getElementById('alerta_data');
    const alertaHorario = document.getElementById('alerta_horario');
    
    alertaData.classList.add('hidden');
    alertaHorario.classList.add('hidden');
    
    // Desmarca checkbox de horário personalizado
    document.getElementById('horario_personalizado').checked = false;
    document.getElementById('horarios_sugeridos').classList.remove('hidden');
    document.getElementById('horario_manual').classList.add('hidden');
}

/**
 * Validação antes de enviar o formulário
 */
function validarFormulario(event) {
    const horaInicio = document.getElementById('hora_inicio').value;
    const horaFim = document.getElementById('hora_fim').value;
    
    if (!horaInicio || !horaFim) {
        event.preventDefault();
        alert('⚠️ Por favor, selecione um horário para a aula.');
        return false;
    }
    
    return true;
}

// Adiciona validação ao formulário quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#modal form');
    if (form) {
        form.addEventListener('submit', validarFormulario);
    }
});
