/**
 * Agendamento Recorrente - JavaScript
 * Autor: Dante Testa (https://dantetesta.com.br)
 * Data: 02/11/2025 17:12
 * 
 * Gerencia a interface de agendamentos recorrentes
 */

class AgendamentoRecorrente {
    constructor() {
        this.checkboxRepetir = document.getElementById('repetir_agendamento');
        this.containerRecorrencia = document.getElementById('container_recorrencia');
        this.tipoRecorrencia = document.getElementById('tipo_recorrencia');
        this.previewContainer = document.getElementById('preview_datas');
        
        this.init();
    }
    
    init() {
        // Toggle container de recorrência
        if (this.checkboxRepetir) {
            this.checkboxRepetir.addEventListener('change', (e) => {
                this.toggleRecorrencia(e.target.checked);
            });
        }
        
        // Atualiza campos baseado no tipo
        if (this.tipoRecorrencia) {
            this.tipoRecorrencia.addEventListener('change', (e) => {
                this.atualizarCamposTipo(e.target.value);
            });
        }
        
        // Preview ao mudar qualquer campo
        this.setupPreviewListeners();
    }
    
    toggleRecorrencia(mostrar) {
        if (this.containerRecorrencia) {
            if (mostrar) {
                this.containerRecorrencia.classList.remove('hidden');
                this.atualizarCamposTipo(this.tipoRecorrencia.value);
            } else {
                this.containerRecorrencia.classList.add('hidden');
                this.limparPreview();
            }
        }
    }
    
    atualizarCamposTipo(tipo) {
        // Esconde todos os campos específicos
        document.getElementById('campo_dias_semana')?.classList.add('hidden');
        document.getElementById('campo_dia_mes')?.classList.add('hidden');
        document.getElementById('campo_intervalo')?.classList.add('hidden');
        
        // Mostra campos relevantes
        switch(tipo) {
            case 'diario':
                document.getElementById('campo_intervalo')?.classList.remove('hidden');
                document.querySelector('label[for="intervalo"]').textContent = 'A cada quantos dias?';
                break;
                
            case 'semanal':
                document.getElementById('campo_dias_semana')?.classList.remove('hidden');
                document.getElementById('campo_intervalo')?.classList.remove('hidden');
                document.querySelector('label[for="intervalo"]').textContent = 'A cada quantas semanas?';
                break;
                
            case 'mensal':
                document.getElementById('campo_dia_mes')?.classList.remove('hidden');
                document.getElementById('campo_intervalo')?.classList.remove('hidden');
                document.querySelector('label[for="intervalo"]').textContent = 'A cada quantos meses?';
                break;
        }
        
        // Atualiza preview
        this.atualizarPreview();
    }
    
    setupPreviewListeners() {
        const campos = [
            'tipo_recorrencia',
            'intervalo',
            'dia_mes',
            'data_inicio',
            'data_fim',
            'max_ocorrencias'
        ];
        
        campos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) {
                elemento.addEventListener('change', () => this.atualizarPreview());
            }
        });
        
        // Dias da semana (checkboxes)
        document.querySelectorAll('input[name="dias_semana[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.atualizarPreview());
        });
    }
    
    async atualizarPreview() {
        if (!this.checkboxRepetir?.checked) {
            return;
        }
        
        const dados = this.coletarDados();
        
        if (!dados.tipo_recorrencia || !dados.data_inicio) {
            this.limparPreview();
            return;
        }
        
        // Validação específica
        if (dados.tipo_recorrencia === 'semanal' && (!dados.dias_semana || dados.dias_semana.length === 0)) {
            this.mostrarPreview('⚠️ Selecione pelo menos um dia da semana', 'warning');
            return;
        }
        
        if (dados.tipo_recorrencia === 'mensal' && !dados.dia_mes) {
            this.mostrarPreview('⚠️ Selecione o dia do mês', 'warning');
            return;
        }
        
        try {
            this.mostrarPreview('🔄 Calculando datas...', 'loading');
            
            // Monta query string
            const params = new URLSearchParams({
                tipo: dados.tipo_recorrencia,
                data_inicio: dados.data_inicio,
                intervalo: dados.intervalo || 1,
                max_ocorrencias: 5 // Preview de 5 datas
            });
            
            if (dados.dias_semana) {
                params.append('dias_semana', dados.dias_semana);
            }
            
            if (dados.dia_mes) {
                params.append('dia_mes', dados.dia_mes);
            }
            
            if (dados.data_fim) {
                params.append('data_fim', dados.data_fim);
            }
            
            const response = await fetch(`/api/serie-preview.php?${params}`);
            const result = await response.json();
            
            if (result.success && result.datas.length > 0) {
                this.renderizarPreview(result);
            } else {
                this.mostrarPreview('⚠️ Nenhuma data gerada com essas configurações', 'warning');
            }
            
        } catch (error) {
            console.error('Erro ao buscar preview:', error);
            this.mostrarPreview('❌ Erro ao calcular datas', 'error');
        }
    }
    
    renderizarPreview(result) {
        const html = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-900 mb-2 flex items-center">
                    <i class="fas fa-calendar-check mr-2"></i>
                    Próximas ${result.total} datas:
                </h4>
                <ul class="space-y-2">
                    ${result.datas.map(data => `
                        <li class="flex items-center text-sm text-blue-800">
                            <i class="fas fa-circle text-blue-400 mr-2" style="font-size: 6px;"></i>
                            <span class="font-medium">${data.data_formatada}</span>
                        </li>
                    `).join('')}
                </ul>
                ${result.total >= 5 ? `
                    <p class="text-xs text-blue-600 mt-3 italic">
                        <i class="fas fa-info-circle mr-1"></i>
                        Mostrando apenas as primeiras 5 datas
                    </p>
                ` : ''}
            </div>
        `;
        
        this.previewContainer.innerHTML = html;
    }
    
    mostrarPreview(mensagem, tipo = 'info') {
        const cores = {
            loading: 'bg-gray-50 border-gray-200 text-gray-700',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            info: 'bg-blue-50 border-blue-200 text-blue-800'
        };
        
        this.previewContainer.innerHTML = `
            <div class="${cores[tipo]} border rounded-lg p-4">
                <p class="text-sm">${mensagem}</p>
            </div>
        `;
    }
    
    limparPreview() {
        if (this.previewContainer) {
            this.previewContainer.innerHTML = '';
        }
    }
    
    coletarDados() {
        const dados = {
            tipo_recorrencia: document.getElementById('tipo_recorrencia')?.value,
            intervalo: document.getElementById('intervalo')?.value,
            dia_mes: document.getElementById('dia_mes')?.value,
            data_inicio: document.getElementById('data_inicio')?.value,
            data_fim: document.getElementById('data_fim')?.value,
            max_ocorrencias: document.getElementById('max_ocorrencias')?.value
        };
        
        // Coleta dias da semana selecionados
        const diasSelecionados = [];
        document.querySelectorAll('input[name="dias_semana[]"]:checked').forEach(checkbox => {
            diasSelecionados.push(checkbox.value);
        });
        
        if (diasSelecionados.length > 0) {
            dados.dias_semana = diasSelecionados.join(',');
        }
        
        return dados;
    }
    
    validarFormulario() {
        if (!this.checkboxRepetir?.checked) {
            return true; // Agendamento único, validação normal
        }
        
        const dados = this.coletarDados();
        const erros = [];
        
        if (!dados.tipo_recorrencia) {
            erros.push('Selecione o tipo de recorrência');
        }
        
        if (!dados.data_inicio) {
            erros.push('Data de início é obrigatória');
        }
        
        if (dados.tipo_recorrencia === 'semanal' && (!dados.dias_semana || dados.dias_semana.length === 0)) {
            erros.push('Selecione pelo menos um dia da semana');
        }
        
        if (dados.tipo_recorrencia === 'mensal' && !dados.dia_mes) {
            erros.push('Selecione o dia do mês');
        }
        
        if (erros.length > 0) {
            alert('Erros no formulário:\n\n' + erros.join('\n'));
            return false;
        }
        
        return true;
    }
    
    async salvarSerie(dadosAgendamento) {
        const dadosRecorrencia = this.coletarDados();
        
        // Mescla dados do agendamento com dados de recorrência
        const payload = {
            ...dadosAgendamento,
            ...dadosRecorrencia,
            is_recorrente: true
        };
        
        try {
            const response = await fetch('/api/serie-criar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const result = await response.json();
            
            if (result.success) {
                return {
                    success: true,
                    message: result.message,
                    serie_id: result.serie_id,
                    total_gerados: result.total_gerados
                };
            } else {
                throw new Error(result.error || 'Erro ao criar série');
            }
            
        } catch (error) {
            console.error('Erro ao salvar série:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
}

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.agendamentoRecorrente = new AgendamentoRecorrente();
});
