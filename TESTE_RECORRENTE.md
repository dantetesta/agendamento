# 🧪 TESTE RÁPIDO - AGENDAMENTOS RECORRENTES

## ✅ CHECKLIST DE VERIFICAÇÃO

### **1. Banco de Dados**
```bash
# Verifique se as tabelas foram criadas
mysql -u seu_usuario -p seu_banco -e "SHOW TABLES LIKE '%series%';"

# Deve retornar: agendamentos_series
```

```bash
# Verifique as colunas em agendamentos
mysql -u seu_usuario -p seu_banco -e "DESCRIBE agendamentos;" | grep -E "serie_id|is_recorrente"

# Deve mostrar:
# serie_id | int | YES
# is_recorrente | tinyint(1) | NO | 0
```

---

### **2. Arquivos Criados**

Verifique se todos os arquivos existem:

```bash
cd /Users/dantetesta/Desktop/projeto-base

# Backend
ls -la core/AgendamentoSerie.php
ls -la database/migrations/001_create_agendamentos_series.sql
ls -la database/migrations/APLICAR_MIGRATION.php

# API
ls -la public/api/serie-criar.php
ls -la public/api/serie-preview.php

# Frontend
ls -la public/assets/js/agendamento-recorrente.js
ls -la public/assets/css/agendamento-recorrente.css
ls -la public/assets/html/form-recorrencia.html
```

---

### **3. Teste da API de Preview**

```bash
# Teste 1: Semanal (Terça e Quinta)
curl "http://danteflix.com.br/api/serie-preview.php?tipo=semanal&dias_semana=2,4&data_inicio=2025-11-05&intervalo=1" \
  -H "Cookie: PHPSESSID=seu_session_id"

# Deve retornar JSON com 5 datas (terças e quintas)
```

```bash
# Teste 2: Diário
curl "http://danteflix.com.br/api/serie-preview.php?tipo=diario&data_inicio=2025-11-05&intervalo=1" \
  -H "Cookie: PHPSESSID=seu_session_id"

# Deve retornar JSON com 5 datas consecutivas
```

```bash
# Teste 3: Mensal
curl "http://danteflix.com.br/api/serie-preview.php?tipo=mensal&dia_mes=15&data_inicio=2025-11-05&intervalo=1" \
  -H "Cookie: PHPSESSID=seu_session_id"

# Deve retornar JSON com dia 15 de cada mês
```

---

### **4. Teste de Criação de Série**

```bash
curl -X POST http://danteflix.com.br/api/serie-criar.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=seu_session_id" \
  -d '{
    "cliente_id": 1,
    "horario": "15:00",
    "duracao": 60,
    "tag_id": 1,
    "tipo_recorrencia": "semanal",
    "dias_semana": "2,4",
    "intervalo": 1,
    "data_inicio": "2025-11-05",
    "max_ocorrencias": 10
  }'

# Deve retornar:
# {
#   "success": true,
#   "serie_id": 1,
#   "total_gerados": 10,
#   "message": "Série criada com sucesso! 10 agendamentos gerados."
# }
```

---

### **5. Verificar no Banco**

```bash
# Verificar série criada
mysql -u seu_usuario -p seu_banco -e "SELECT * FROM agendamentos_series LIMIT 1;"

# Verificar agendamentos gerados
mysql -u seu_usuario -p seu_banco -e "SELECT id, data_agendamento, horario, is_recorrente, serie_id FROM agendamentos WHERE is_recorrente = 1 LIMIT 5;"
```

---

### **6. Teste no Dashboard**

1. **Acesse:** http://danteflix.com.br/dashboard
2. **Abra o console do navegador (F12)**
3. **Verifique:**
   - ✅ CSS carregado: `/assets/css/agendamento-recorrente.css`
   - ✅ JS carregado: `/assets/js/agendamento-recorrente.js`
   - ✅ Objeto criado: `window.agendamentoRecorrente`

```javascript
// No console:
console.log(window.agendamentoRecorrente);
// Deve retornar: AgendamentoRecorrente {checkboxRepetir: ..., ...}
```

---

### **7. Verificar Eventos no Calendário**

1. **Abra o dashboard**
2. **Veja se eventos recorrentes têm badge roxo** 🔁
3. **Clique em um evento recorrente**
4. **Verifique no console:**

```javascript
// Deve mostrar:
is_recorrente: true
serie_id: 1
```

---

## 🐛 TROUBLESHOOTING

### **Erro: "Call to undefined function getConnection()"**
✅ **Resolvido!** Migration atualizada para usar `Database::getInstance()`

### **Erro: "Table 'agendamentos_series' doesn't exist"**
```bash
# Execute a migration novamente:
php database/migrations/APLICAR_MIGRATION.php
```

### **Erro: "Unknown column 'serie_id' in 'field list'"**
```bash
# Verifique se a coluna foi adicionada:
mysql -u seu_usuario -p seu_banco -e "DESCRIBE agendamentos;"

# Se não existir, execute:
mysql -u seu_usuario -p seu_banco -e "ALTER TABLE agendamentos ADD COLUMN serie_id INT NULL, ADD COLUMN is_recorrente TINYINT(1) NOT NULL DEFAULT 0;"
```

### **CSS/JS não carrega**
```bash
# Verifique permissões:
chmod 644 public/assets/css/agendamento-recorrente.css
chmod 644 public/assets/js/agendamento-recorrente.js

# Limpe cache do navegador (Ctrl+Shift+R)
```

### **API retorna 401 (Não autenticado)**
- Faça login no sistema primeiro
- Copie o cookie PHPSESSID do navegador
- Use no curl: `-H "Cookie: PHPSESSID=valor_do_cookie"`

---

## ✅ TESTE COMPLETO PASSO A PASSO

### **Cenário: Criar aula de violão toda terça e quinta**

1. **Acesse:** http://danteflix.com.br/agendamentos
2. **Clique:** "Novo Agendamento"
3. **Preencha:**
   - Cliente: João Silva
   - Data: 05/11/2025
   - Horário: 15:00
   - Duração: 60min
   - Tag: Aula de Violão
4. **Marque:** ☑ Repetir agendamento
5. **Configure:**
   - Tipo: Semanalmente
   - Dias: ☑ Terça ☑ Quinta
   - Intervalo: 1
   - Início: 05/11/2025
   - Termina: Após 10 ocorrências
6. **Veja o preview:** Deve mostrar 5 datas
7. **Clique:** Salvar
8. **Resultado:** "✅ Série criada com sucesso! 10 agendamentos gerados!"
9. **Verifique:** Calendário deve mostrar eventos com badge 🔁

---

## 📊 RESULTADO ESPERADO

### **No Banco:**
```
agendamentos_series:
- 1 registro com tipo_recorrencia='semanal'

agendamentos:
- 10 registros com is_recorrente=1 e serie_id=1
- Datas: 05/11, 07/11, 12/11, 14/11, 19/11, 21/11, 26/11, 28/11, 03/12, 05/12
```

### **No Calendário:**
- ✅ 10 eventos visíveis
- ✅ Badge roxo 🔁 em cada um
- ✅ Cor do cliente
- ✅ Tag do serviço

---

**Tudo funcionando!** ✨
