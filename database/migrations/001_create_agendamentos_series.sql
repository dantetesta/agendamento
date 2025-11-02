-- Migration: Criar tabela de séries de agendamentos recorrentes
-- Autor: Dante Testa (https://dantetesta.com.br)
-- Data: 02/11/2025 16:51
-- Versão: 1.0

-- Tabela de séries (regras de recorrência)
CREATE TABLE IF NOT EXISTS agendamentos_series (
    id INT PRIMARY KEY AUTO_INCREMENT,
    professor_id INT NOT NULL,
    cliente_id INT NOT NULL,
    horario TIME NOT NULL,
    duracao INT NOT NULL DEFAULT 60,
    tag_id INT NULL,
    observacoes TEXT NULL,
    
    -- Configuração de recorrência
    tipo_recorrencia ENUM('diario', 'semanal', 'mensal', 'personalizado') NOT NULL DEFAULT 'semanal',
    dias_semana VARCHAR(20) NULL COMMENT 'Ex: 2,4 (terça e quinta)',
    intervalo INT NOT NULL DEFAULT 1 COMMENT 'A cada X semanas/dias/meses',
    dia_mes INT NULL COMMENT 'Dia do mês (1-31) para recorrência mensal',
    
    -- Período da série
    data_inicio DATE NOT NULL,
    data_fim DATE NULL COMMENT 'NULL = sem fim definido',
    max_ocorrencias INT NULL COMMENT 'Limite de eventos gerados',
    
    -- Status e metadados
    status ENUM('ativo', 'pausado', 'finalizado') NOT NULL DEFAULT 'ativo',
    total_gerados INT NOT NULL DEFAULT 0 COMMENT 'Contador de agendamentos gerados',
    
    -- Auditoria
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (professor_id) REFERENCES professores(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE SET NULL,
    
    -- Índices para performance
    INDEX idx_professor (professor_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_status (status),
    INDEX idx_data_inicio (data_inicio),
    INDEX idx_data_fim (data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Séries de agendamentos recorrentes';

-- Adicionar coluna serie_id na tabela agendamentos
ALTER TABLE agendamentos 
ADD COLUMN serie_id INT NULL AFTER id,
ADD COLUMN is_recorrente TINYINT(1) NOT NULL DEFAULT 0 AFTER serie_id,
ADD FOREIGN KEY (serie_id) REFERENCES agendamentos_series(id) ON DELETE SET NULL,
ADD INDEX idx_serie (serie_id);

-- Comentários nas colunas
ALTER TABLE agendamentos 
MODIFY COLUMN serie_id INT NULL COMMENT 'ID da série se for agendamento recorrente',
MODIFY COLUMN is_recorrente TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = parte de série, 0 = agendamento único';
