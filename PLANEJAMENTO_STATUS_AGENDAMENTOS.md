# 📋 PLANEJAMENTO: SISTEMA DE STATUS DE AGENDAMENTOS
**Autor:** Dante Testa (https://dantetesta.com.br)  
**Data:** 03/11/2025 20:34  
**Objetivo:** Permitir marcar agendamentos como concluídos/realizados

---

## 🎯 FUNCIONALIDADES

### **1. Status de Agendamento**
- ✅ **Pendente** (padrão) - Agendamento futuro ou não realizado
- ✅ **Concluído** - Atendimento já foi realizado
- ✅ **Cancelado** - Agendamento foi cancelado

### **2. Ações Disponíveis**
- Marcar como concluído (botão rápido)
- Marcar como cancelado
- Voltar para pendente (desfazer)

### **3. Visualizações**
- **Calendário:** Cores diferentes por status
- **Listagem:** Filtro por status
- **Dashboard:** Estatísticas de conclusão

---

## 🗄️ BANCO DE DADOS

### **Alteração na tabela `agendamentos`**

```sql
-- Adicionar coluna de status
ALTER TABLE agendamentos 
ADD COLUMN status ENUM('pendente', 'concluido', 'cancelado') 
DEFAULT 'pendente' 
AFTER tag_servico_id;

-- Adicionar índice para performance
ALTER TABLE agendamentos 
ADD INDEX idx_status (status);

-- Adicionar coluna de data de conclusão (opcional)
ALTER TABLE agendamentos 
ADD COLUMN concluido_em DATETIME NULL 
AFTER status;
```

### **Estrutura final:**
```
agendamentos
├── id
├── professor_id
├── cliente_id
├── aluno
├── descricao
├── data
├── hora_inicio
├── hora_fim
├── tag_servico_id
├── status ← NOVO
├── concluido_em ← NOVO
└── criado_em
```

---

## 🎨 CORES POR STATUS

### **Calendário (FullCalendar)**
```javascript
// Pendente: Cor da tag do cliente (padrão atual)
backgroundColor: tagCliente.cor || '#3B82F6'

// Concluído: Verde com opacidade
backgroundColor: '#10B981' (verde)
borderColor: '#059669'
opacity: 0.7

// Cancelado: Cinza com riscado
backgroundColor: '#6B7280' (cinza)
textDecoration: 'line-through'
opacity: 0.5
```

### **Listagem**
```
┌─────────────────────────────────────┐
│ ✅ João Silva - 10:00 (Concluído)  │ ← Verde
│ 📅 Maria Santos - 14:00 (Pendente) │ ← Azul
│ ❌ Pedro Costa - 16:00 (Cancelado) │ ← Cinza
└─────────────────────────────────────┘
```

---

## 🔧 IMPLEMENTAÇÃO

### **FASE 1: Banco de Dados** ✅
**Arquivo:** `database/migrations/002_add_status_agendamentos.sql`
```sql
-- Migration para adicionar status
ALTER TABLE agendamentos 
ADD COLUMN status ENUM('pendente', 'concluido', 'cancelado') 
DEFAULT 'pendente' 
AFTER tag_servico_id;

ALTER TABLE agendamentos 
ADD COLUMN concluido_em DATETIME NULL 
AFTER status;

ALTER TABLE agendamentos 
ADD INDEX idx_status (status);
```

**Arquivo:** `database/migrations/APLICAR_STATUS.php`
```php
// Script para aplicar migration
```

---

### **FASE 2: Backend (PHP)** ✅

#### **1. Model Agendamento.php**
```php
/**
 * Atualiza status do agendamento
 */
public function updateStatus($id, $status) {
    $sql = "UPDATE agendamentos 
            SET status = :status,
                concluido_em = CASE 
                    WHEN :status = 'concluido' THEN NOW() 
                    ELSE NULL 
                END
            WHERE id = :id";
    
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([
        ':id' => $id,
        ':status' => $status
    ]);
}

/**
 * Busca agendamentos por status
 */
public function getByStatus($professorId, $status) {
    $sql = "SELECT * FROM agendamentos 
            WHERE professor_id = :professor_id 
            AND status = :status
            ORDER BY data DESC, hora_inicio DESC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        ':professor_id' => $professorId,
        ':status' => $status
    ]);
    
    return $stmt->fetchAll();
}

/**
 * Estatísticas de status
 */
public function getStatusStats($professorId, $mes = null) {
    $sql = "SELECT 
                status,
                COUNT(*) as total
            FROM agendamentos 
            WHERE professor_id = :professor_id";
    
    if ($mes) {
        $sql .= " AND DATE_FORMAT(data, '%Y-%m') = :mes";
    }
    
    $sql .= " GROUP BY status";
    
    $stmt = $this->db->prepare($sql);
    $params = [':professor_id' => $professorId];
    
    if ($mes) {
        $params[':mes'] = $mes;
    }
    
    $stmt->execute($params);
    return $stmt->fetchAll();
}
```

#### **2. API para atualizar status**
**Arquivo:** `public/api/agendamento-status.php`
```php
<?php
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../app/Models/Agendamento.php';

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$agendamentoModel = new Agendamento();

// Pega dados
$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;

// Valida
if (!$id || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Valida status
if (!in_array($status, ['pendente', 'concluido', 'cancelado'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Status inválido']);
    exit;
}

// Verifica se é do professor
$agendamento = $agendamentoModel->findById($id);
if (!$agendamento || $agendamento['professor_id'] != Auth::id()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// Atualiza
if ($agendamentoModel->updateStatus($id, $status)) {
    echo json_encode([
        'success' => true,
        'message' => 'Status atualizado com sucesso!'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao atualizar status']);
}
```

#### **3. Atualizar API eventos.php**
```php
// Adicionar status nos extendedProps
$evento['extendedProps'] = [
    'descricao' => $evento['descricao'] ?? '',
    'tagCliente' => $tagCliente,
    'tagServico' => $tagServico,
    'fotoCliente' => $fotoCliente,
    'status' => $evento['status'] ?? 'pendente', // NOVO
    'concluido_em' => $evento['concluido_em'] ?? null // NOVO
];

// Ajustar cor baseado no status
if ($evento['status'] === 'concluido') {
    $evento['backgroundColor'] = '#10B981'; // Verde
    $evento['borderColor'] = '#059669';
} else if ($evento['status'] === 'cancelado') {
    $evento['backgroundColor'] = '#6B7280'; // Cinza
    $evento['borderColor'] = '#4B5563';
}
```

---

### **FASE 3: Frontend** ✅

#### **1. Calendário (dashboard.php)**

**Adicionar opacidade e estilo por status:**
```javascript
eventContent: function(arg) {
    const status = arg.event.extendedProps?.status || 'pendente';
    const corCliente = arg.event.backgroundColor || '#3B82F6';
    
    // Ajusta opacidade
    let opacity = 1;
    let textDecoration = 'none';
    
    if (status === 'concluido') {
        opacity = 0.7;
    } else if (status === 'cancelado') {
        opacity = 0.5;
        textDecoration = 'line-through';
    }
    
    // Adiciona badge de status
    let badgeStatus = '';
    if (status === 'concluido') {
        badgeStatus = `
            <span style="
                background: #10B981;
                color: white;
                padding: 1px 4px;
                border-radius: 3px;
                font-size: 8px;
                margin-left: 2px;
            ">
                <i class="fas fa-check" style="font-size: 7px;"></i>
            </span>
        `;
    } else if (status === 'cancelado') {
        badgeStatus = `
            <span style="
                background: #6B7280;
                color: white;
                padding: 1px 4px;
                border-radius: 3px;
                font-size: 8px;
                margin-left: 2px;
            ">
                <i class="fas fa-times" style="font-size: 7px;"></i>
            </span>
        `;
    }
    
    return {
        html: `
            <div style="opacity: ${opacity}; text-decoration: ${textDecoration};">
                ${titulo} ${badgeStatus}
            </div>
        `
    };
}
```

**Adicionar botões no modal de detalhes:**
```html
<!-- Botões de ação -->
<div class="flex gap-2 mt-4">
    <?php if ($status === 'pendente'): ?>
        <button onclick="marcarConcluido(<?= $id ?>)" 
                class="btn-success">
            <i class="fas fa-check mr-2"></i>
            Marcar como Concluído
        </button>
        <button onclick="marcarCancelado(<?= $id ?>)" 
                class="btn-danger">
            <i class="fas fa-times mr-2"></i>
            Cancelar
        </button>
    <?php elseif ($status === 'concluido'): ?>
        <button onclick="marcarPendente(<?= $id ?>)" 
                class="btn-secondary">
            <i class="fas fa-undo mr-2"></i>
            Desfazer Conclusão
        </button>
    <?php elseif ($status === 'cancelado'): ?>
        <button onclick="marcarPendente(<?= $id ?>)" 
                class="btn-secondary">
            <i class="fas fa-undo mr-2"></i>
            Reativar
        </button>
    <?php endif; ?>
</div>
```

**JavaScript para atualizar status:**
```javascript
function marcarConcluido(id) {
    if (!confirm('Marcar este agendamento como concluído?')) return;
    
    atualizarStatus(id, 'concluido');
}

function marcarCancelado(id) {
    if (!confirm('Cancelar este agendamento?')) return;
    
    atualizarStatus(id, 'cancelado');
}

function marcarPendente(id) {
    if (!confirm('Voltar este agendamento para pendente?')) return;
    
    atualizarStatus(id, 'pendente');
}

function atualizarStatus(id, status) {
    fetch('/api/agendamento-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recarrega calendário
            calendar.refetchEvents();
            
            // Fecha modal
            fecharModal();
            
            // Mensagem de sucesso
            alert('Status atualizado com sucesso!');
        } else {
            alert('Erro ao atualizar status');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar status');
    });
}
```

#### **2. Listagem (agendamentos.php)**

**Adicionar filtro de status:**
```html
<!-- Filtros -->
<div class="flex gap-4 mb-6">
    <select id="filtro_status" 
            onchange="filtrarPorStatus(this.value)"
            class="px-4 py-2 border rounded-lg">
        <option value="">Todos os Status</option>
        <option value="pendente">📅 Pendentes</option>
        <option value="concluido">✅ Concluídos</option>
        <option value="cancelado">❌ Cancelados</option>
    </select>
</div>
```

**Badge de status na listagem:**
```php
<!-- Status Badge -->
<?php if ($agendamento['status'] === 'concluido'): ?>
    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
        <i class="fas fa-check mr-1"></i>
        Concluído
    </span>
<?php elseif ($agendamento['status'] === 'cancelado'): ?>
    <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">
        <i class="fas fa-times mr-1"></i>
        Cancelado
    </span>
<?php else: ?>
    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
        <i class="fas fa-clock mr-1"></i>
        Pendente
    </span>
<?php endif; ?>
```

**Botão rápido na listagem:**
```html
<!-- Ação rápida -->
<?php if ($agendamento['status'] === 'pendente'): ?>
    <button onclick="marcarConcluido(<?= $agendamento['id'] ?>)" 
            class="text-green-600 hover:text-green-800"
            title="Marcar como concluído">
        <i class="fas fa-check-circle text-xl"></i>
    </button>
<?php endif; ?>
```

---

### **FASE 4: Dashboard - Estatísticas** ✅

**Card de estatísticas:**
```html
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Pendentes -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-blue-600 font-semibold">Pendentes</p>
                <p class="text-3xl font-bold text-blue-800"><?= $stats['pendente'] ?? 0 ?></p>
            </div>
            <i class="fas fa-clock text-4xl text-blue-300"></i>
        </div>
    </div>
    
    <!-- Concluídos -->
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-green-600 font-semibold">Concluídos</p>
                <p class="text-3xl font-bold text-green-800"><?= $stats['concluido'] ?? 0 ?></p>
            </div>
            <i class="fas fa-check-circle text-4xl text-green-300"></i>
        </div>
    </div>
    
    <!-- Cancelados -->
    <div class="bg-gray-50 border-l-4 border-gray-500 p-4 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 font-semibold">Cancelados</p>
                <p class="text-3xl font-bold text-gray-800"><?= $stats['cancelado'] ?? 0 ?></p>
            </div>
            <i class="fas fa-times-circle text-4xl text-gray-300"></i>
        </div>
    </div>
</div>
```

---

## 📊 RESUMO DE IMPLEMENTAÇÃO

### **Arquivos a criar:**
1. `database/migrations/002_add_status_agendamentos.sql`
2. `database/migrations/APLICAR_STATUS.php`
3. `public/api/agendamento-status.php`

### **Arquivos a modificar:**
1. `app/Models/Agendamento.php` - Adicionar métodos de status
2. `public/api/eventos.php` - Incluir status nos eventos
3. `public/dashboard.php` - Visual de status no calendário
4. `public/agendamentos.php` - Filtros e badges de status

---

## 🎯 BENEFÍCIOS

### **Organização:**
- ✅ Saber quais agendamentos já foram atendidos
- ✅ Histórico de conclusões
- ✅ Controle de cancelamentos

### **Visual:**
- ✅ Cores diferentes por status
- ✅ Calendário mais informativo
- ✅ Badges claros

### **Produtividade:**
- ✅ Ação rápida (1 clique para concluir)
- ✅ Filtros na listagem
- ✅ Estatísticas de desempenho

### **Relatórios:**
- ✅ Quantos atendimentos por mês
- ✅ Taxa de conclusão
- ✅ Taxa de cancelamento

---

## 🚀 PRÓXIMOS PASSOS

1. ✅ Aprovar planejamento
2. ✅ Criar migration do banco
3. ✅ Implementar backend (Model + API)
4. ✅ Implementar frontend (Calendário + Listagem)
5. ✅ Testar funcionalidades
6. ✅ Deploy

---

## 💡 MELHORIAS FUTURAS

- Motivo do cancelamento (campo texto)
- Notificação antes do agendamento
- Auto-marcar como concluído após horário
- Relatório de produtividade
- Exportar agendamentos concluídos
- Gráficos de desempenho

---

**Planejamento completo! Pronto para implementação!** 🎉
