# 🔁 AGENDAMENTOS RECORRENTES - IMPLEMENTAÇÃO

## ✅ FASE 1: BANCO DE DADOS (CONCLUÍDA)

### Arquivos Criados:

1. **`/database/migrations/001_create_agendamentos_series.sql`**
   - Cria tabela `agendamentos_series`
   - Adiciona colunas `serie_id` e `is_recorrente` em `agendamentos`
   - Índices para performance
   - Chaves estrangeiras

2. **`/database/migrations/APLICAR_MIGRATION.php`**
   - Script para aplicar a migration
   - Interface web amigável
   - Tratamento de erros

### Estrutura da Tabela:

```sql
agendamentos_series:
- id (PK)
- professor_id (FK)
- cliente_id (FK)
- horario
- duracao
- tag_id (FK)
- observacoes
- tipo_recorrencia (diario, semanal, mensal, personalizado)
- dias_semana (ex: "2,4" = terça e quinta)
- intervalo (a cada X semanas/dias)
- dia_mes (para recorrência mensal)
- data_inicio
- data_fim (NULL = sem fim)
- max_ocorrencias (limite de eventos)
- status (ativo, pausado, finalizado)
- total_gerados (contador)
- created_at, updated_at
```

---

## ✅ FASE 2: BACKEND (CONCLUÍDA)

### Arquivo Criado:

**`/core/AgendamentoSerie.php`**

### Métodos Implementados:

1. **`criarSerie($dados)`**
   - Cria série no banco
   - Gera agendamentos automaticamente
   - Retorna total gerado

2. **`gerarAgendamentos($serieId)`**
   - Calcula datas baseado na regra
   - Cria agendamentos individuais
   - Verifica conflitos

3. **`calcularDatas($serie)`**
   - Algoritmo para cada tipo:
     - Diário: A cada X dias
     - Semanal: Dias específicos da semana
     - Mensal: Dia X do mês
   - Respeita data fim e max ocorrências

4. **`verificarConflito($professorId, $data, $horario)`**
   - Evita agendamentos duplicados
   - Verifica disponibilidade

5. **`cancelarSerie($serieId, $cancelarFuturos)`**
   - Cancela série inteira
   - Opção de cancelar apenas futuros

6. **`buscarSeriesAtivas($professorId)`**
   - Lista séries ativas
   - Join com clientes e tags

### Validações Implementadas:

- ✅ Campos obrigatórios
- ✅ Dias da semana para tipo semanal
- ✅ Dia do mês para tipo mensal
- ✅ Data início não pode ser passado
- ✅ Data fim > data início
- ✅ Intervalo entre 1-12
- ✅ Max ocorrências entre 1-100
- ✅ Verificação de conflitos

---

## ✅ FASE 3: API (CONCLUÍDA)

### Endpoints Criados:

1. **`POST /api/serie-criar.php`**
   - Cria nova série
   - Validações de segurança
   - Log de auditoria
   - Retorna total gerado

2. **`GET /api/serie-preview.php`**
   - Preview das próximas datas
   - Máximo 10 datas
   - Formatação em português
   - Mostra dia da semana

### Parâmetros da API:

**Criar Série:**
```json
{
  "cliente_id": 1,
  "horario": "15:00",
  "duracao": 60,
  "tag_id": 2,
  "observacoes": "Aula de violão",
  "tipo_recorrencia": "semanal",
  "dias_semana": "2,4",
  "intervalo": 1,
  "data_inicio": "2025-11-05",
  "data_fim": "2025-12-31",
  "max_ocorrencias": 20
}
```

**Preview:**
```
GET /api/serie-preview.php?tipo=semanal&dias_semana=2,4&data_inicio=2025-11-05&intervalo=1
```

---

## 🔄 PRÓXIMAS FASES

### FASE 4: FRONTEND (PENDENTE)

**Arquivos a criar:**

1. **Modal de Novo Agendamento (atualizado)**
   - Checkbox "Repetir agendamento"
   - Campos de recorrência
   - Preview de datas
   - Validação client-side

2. **JavaScript**
   - `/public/assets/js/agendamento-recorrente.js`
   - Lógica de preview
   - Envio para API
   - Feedback visual

3. **CSS**
   - `/public/assets/css/agendamento-recorrente.css`
   - Estilos do formulário
   - Animações

### FASE 5: VISUALIZAÇÃO (PENDENTE)

1. **Calendário**
   - Ícone diferenciado para séries (🔁)
   - Badge "Recorrente"
   - Cor diferente

2. **Modal de Detalhes**
   - Informações da série
   - Botões "Editar este" / "Editar todos"
   - Botões "Cancelar este" / "Cancelar todos"

3. **Página de Gerenciamento**
   - Lista de séries ativas
   - Pausar/Retomar série
   - Editar série
   - Cancelar série

---

## 📊 EXEMPLOS DE USO

### Exemplo 1: Terça e Quinta, 15:00

```json
{
  "tipo_recorrencia": "semanal",
  "dias_semana": "2,4",
  "horario": "15:00",
  "data_inicio": "2025-11-05",
  "intervalo": 1
}
```

**Gera:**
- 05/11/2025 (Terça)
- 07/11/2025 (Quinta)
- 12/11/2025 (Terça)
- 14/11/2025 (Quinta)
- ...

### Exemplo 2: Todo dia útil, 09:00

```json
{
  "tipo_recorrencia": "semanal",
  "dias_semana": "1,2,3,4,5",
  "horario": "09:00",
  "data_inicio": "2025-11-04",
  "data_fim": "2025-12-31"
}
```

### Exemplo 3: Quinzenal (a cada 2 semanas)

```json
{
  "tipo_recorrencia": "semanal",
  "dias_semana": "3",
  "intervalo": 2,
  "horario": "14:00",
  "max_ocorrencias": 10
}
```

### Exemplo 4: Todo dia 15 do mês

```json
{
  "tipo_recorrencia": "mensal",
  "dia_mes": 15,
  "horario": "10:00",
  "intervalo": 1
}
```

---

## 🔒 SEGURANÇA IMPLEMENTADA

- ✅ Autenticação obrigatória
- ✅ Validação de todos os campos
- ✅ Proteção contra SQL Injection (prepared statements)
- ✅ Limite de ocorrências (máx 100)
- ✅ Limite de preview (máx 10)
- ✅ Verificação de conflitos
- ✅ Transações no banco
- ✅ Logs de auditoria
- ✅ Validação de datas (não permite passado)
- ✅ Validação de intervalos (1-12)

---

## 🧪 COMO TESTAR

### 1. Aplicar Migration

Acesse: `http://localhost/database/migrations/APLICAR_MIGRATION.php`

Ou via CLI:
```bash
php /database/migrations/APLICAR_MIGRATION.php
```

### 2. Testar Preview (via curl)

```bash
curl "http://localhost/api/serie-preview.php?tipo=semanal&dias_semana=2,4&data_inicio=2025-11-05&intervalo=1"
```

### 3. Criar Série (via curl)

```bash
curl -X POST http://localhost/api/serie-criar.php \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 1,
    "horario": "15:00",
    "duracao": 60,
    "tipo_recorrencia": "semanal",
    "dias_semana": "2,4",
    "data_inicio": "2025-11-05",
    "intervalo": 1
  }'
```

---

## 📝 PRÓXIMOS PASSOS

1. ✅ Aplicar migration no banco
2. ⏳ Criar interface frontend
3. ⏳ Integrar com modal de agendamento
4. ⏳ Adicionar visualização no calendário
5. ⏳ Criar página de gerenciamento de séries
6. ⏳ Testes completos
7. ⏳ Documentação de usuário

---

## 🎯 STATUS ATUAL

- **Banco de Dados:** ✅ 100% Concluído
- **Backend:** ✅ 100% Concluído
- **API:** ✅ 100% Concluído
- **Frontend:** ⏳ 0% (próxima fase)
- **Testes:** ⏳ 0% (próxima fase)

---

**Implementado por:** Dante Testa  
**Data:** 02/11/2025  
**Versão:** 1.0
