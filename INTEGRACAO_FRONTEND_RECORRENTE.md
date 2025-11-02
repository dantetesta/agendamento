# 🎨 INTEGRAÇÃO DO FRONTEND - AGENDAMENTOS RECORRENTES

## ✅ ARQUIVOS CRIADOS

### **JavaScript:**
- `/public/assets/js/agendamento-recorrente.js`

### **CSS:**
- `/public/assets/css/agendamento-recorrente.css`

### **HTML (Template):**
- `/public/assets/html/form-recorrencia.html`

---

## 📋 PASSO A PASSO DE INTEGRAÇÃO

### **1. Incluir CSS e JS no Dashboard**

Adicione no `<head>` do `dashboard.php`:

```php
<!-- CSS Agendamento Recorrente -->
<link rel="stylesheet" href="/assets/css/agendamento-recorrente.css">
```

Adicione antes do `</body>` do `dashboard.php`:

```php
<!-- JS Agendamento Recorrente -->
<script src="/assets/js/agendamento-recorrente.js"></script>
```

---

### **2. Adicionar Formulário no Modal**

Abra o modal de novo agendamento e adicione o conteúdo de `form-recorrencia.html` **após** os campos normais (cliente, data, horário, duração, tag).

**Localização:** Dentro do `<form>` do modal, antes dos botões de ação.

---

### **3. Atualizar Função de Salvar Agendamento**

Modifique a função que salva o agendamento para verificar se é recorrente:

```javascript
async function salvarAgendamento() {
    // Coleta dados normais do agendamento
    const dados = {
        cliente_id: document.getElementById('cliente_id').value,
        data_agendamento: document.getElementById('data_agendamento').value,
        horario: document.getElementById('horario').value,
        duracao: document.getElementById('duracao').value,
        tag_id: document.getElementById('tag_id').value,
        observacoes: document.getElementById('observacoes').value
    };
    
    // Verifica se é recorrente
    const isRecorrente = document.getElementById('repetir_agendamento')?.checked;
    
    if (isRecorrente) {
        // Valida formulário de recorrência
        if (!window.agendamentoRecorrente.validarFormulario()) {
            return;
        }
        
        // Salva como série
        const resultado = await window.agendamentoRecorrente.salvarSerie(dados);
        
        if (resultado.success) {
            alert(`✅ ${resultado.message}\n\n${resultado.total_gerados} agendamentos criados!`);
            calendar.refetchEvents(); // Recarrega calendário
            fecharModal();
        } else {
            alert(`❌ Erro: ${resultado.error}`);
        }
    } else {
        // Salva agendamento único (código existente)
        // ... seu código atual ...
    }
}
```

---

### **4. Adicionar Badge no Calendário**

Para mostrar que um agendamento é recorrente, adicione um badge:

```javascript
// No eventContent do FullCalendar
eventContent: function(arg) {
    const isRecorrente = arg.event.extendedProps?.is_recorrente;
    
    let html = `
        <div class="fc-event-main-frame">
            <div class="fc-event-time">${arg.timeText}</div>
            <div class="fc-event-title">${arg.event.title}</div>
            ${isRecorrente ? '<span class="badge-recorrente"><i class="fas fa-repeat"></i>Série</span>' : ''}
        </div>
    `;
    
    return { html: html };
}
```

---

### **5. Atualizar API de Eventos**

Modifique `/api/eventos.php` para incluir o campo `is_recorrente`:

```php
$stmt = $db->prepare("
    SELECT 
        a.*,
        c.nome as cliente_nome,
        c.cor as cliente_cor,
        t.nome as tag_nome,
        t.cor as tag_cor,
        t.icone as tag_icone,
        a.is_recorrente,  -- ← ADICIONE ESTA LINHA
        a.serie_id        -- ← ADICIONE ESTA LINHA
    FROM agendamentos a
    INNER JOIN clientes c ON a.cliente_id = c.id
    LEFT JOIN tags t ON a.tag_id = t.id
    WHERE a.professor_id = ?
    AND a.data_agendamento BETWEEN ? AND ?
    AND a.status != 'cancelado'
    ORDER BY a.data_agendamento, a.horario
");
```

E no retorno JSON:

```php
'extendedProps' => [
    'cliente_id' => $evento['cliente_id'],
    'tag_id' => $evento['tag_id'],
    'is_recorrente' => (bool)$evento['is_recorrente'],  -- ← ADICIONE
    'serie_id' => $evento['serie_id'],                   -- ← ADICIONE
    // ... outros campos
]
```

---

## 🎯 FUNCIONALIDADES IMPLEMENTADAS

### **1. Interface Interativa**
- ✅ Checkbox para ativar recorrência
- ✅ Campos aparecem/desaparecem baseado no tipo
- ✅ Dias da semana com checkboxes visuais
- ✅ Preview em tempo real das próximas datas

### **2. Validações**
- ✅ Campos obrigatórios
- ✅ Dias da semana para tipo semanal
- ✅ Dia do mês para tipo mensal
- ✅ Data início não pode ser passado
- ✅ Limites de intervalo (1-12)
- ✅ Limites de ocorrências (1-100)

### **3. Preview Dinâmico**
- ✅ Atualiza ao mudar qualquer campo
- ✅ Mostra próximas 5 datas
- ✅ Formatação em português
- ✅ Mostra dia da semana

### **4. Tipos de Recorrência**
- ✅ **Diário:** A cada X dias
- ✅ **Semanal:** Dias específicos da semana
- ✅ **Mensal:** Dia X do mês

### **5. Opções de Término**
- ✅ **Nunca:** Continua indefinidamente (3 meses)
- ✅ **Data específica:** Até uma data
- ✅ **Após X ocorrências:** Limite de eventos

---

## 🎨 VISUAL

### **Checkbox de Repetir:**
```
┌─────────────────────────────────────┐
│ ☑ 🔁 Repetir este agendamento      │
│   (fundo azul gradiente)            │
└─────────────────────────────────────┘
```

### **Dias da Semana:**
```
┌───┬───┬───┬───┬───┬───┬───┐
│ S │ T │ Q │ Q │ S │ S │ D │
│Seg│Ter│Qua│Qui│Sex│Sáb│Dom│
└───┴───┴───┴───┴───┴───┴───┘
  ✓       ✓   ✓
(azul)  (azul)(azul)
```

### **Preview:**
```
┌─────────────────────────────────────┐
│ 📅 Próximas 5 datas:                │
│                                     │
│ • 05/11/2025 (Terça-feira)         │
│ • 07/11/2025 (Quinta-feira)        │
│ • 12/11/2025 (Terça-feira)         │
│ • 14/11/2025 (Quinta-feira)        │
│ • 19/11/2025 (Terça-feira)         │
│                                     │
│ ℹ️ Mostrando apenas as primeiras 5  │
└─────────────────────────────────────┘
```

---

## 📱 RESPONSIVIDADE

### **Desktop (>768px):**
- 7 colunas para dias da semana
- Layout horizontal

### **Tablet (768px):**
- 7 colunas (ajustado)
- Espaçamento reduzido

### **Mobile (<480px):**
- 4 colunas para dias da semana
- Layout adaptado

---

## 🧪 COMO TESTAR

### **1. Teste Básico:**
1. Abra modal de novo agendamento
2. Marque "Repetir agendamento"
3. Selecione "Semanalmente"
4. Marque Terça e Quinta
5. Veja o preview aparecer

### **2. Teste de Validação:**
1. Marque "Repetir"
2. Selecione "Semanalmente"
3. NÃO marque nenhum dia
4. Tente salvar
5. ✅ Deve mostrar erro

### **3. Teste de Preview:**
1. Marque "Repetir"
2. Mude o tipo de recorrência
3. ✅ Preview deve atualizar automaticamente

### **4. Teste de Salvamento:**
1. Preencha todos os campos
2. Marque "Repetir"
3. Configure recorrência
4. Salve
5. ✅ Deve criar múltiplos agendamentos

---

## 🔒 SEGURANÇA

Todas as validações são feitas tanto no **frontend** quanto no **backend**:

- ✅ Validação JavaScript (UX)
- ✅ Validação PHP (Segurança)
- ✅ Prepared statements
- ✅ Sanitização de dados
- ✅ Limites de segurança

---

## 📊 EXEMPLO DE USO

### **Cenário: Aula de violão toda terça e quinta, 15:00**

1. Cliente: João Silva
2. Data: 05/11/2025
3. Horário: 15:00
4. Duração: 60min
5. Tag: Aula de Violão
6. ☑ Repetir agendamento
7. Tipo: Semanalmente
8. Dias: ☑ Terça ☑ Quinta
9. Intervalo: 1 (toda semana)
10. Início: 05/11/2025
11. Termina: Nunca

**Resultado:** Cria agendamentos para todas as terças e quintas pelos próximos 3 meses.

---

## 🎯 PRÓXIMOS PASSOS

Após integrar o frontend:

1. ⏳ Testar criação de séries
2. ⏳ Implementar edição de séries
3. ⏳ Implementar cancelamento de séries
4. ⏳ Criar página de gerenciamento de séries
5. ⏳ Adicionar notificações
6. ⏳ Documentação de usuário

---

**Status:** Frontend 100% implementado, pronto para integração!

**Autor:** Dante Testa  
**Data:** 02/11/2025
