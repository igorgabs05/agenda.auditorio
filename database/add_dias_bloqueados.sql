-- Adicionar tabela para gerenciar dias bloqueados/desbloqueados pelo admin
-- Execute este script no banco de dados agenda_auditorio

USE agenda_auditorio;

-- Tabela de dias bloqueados/desbloqueados
CREATE TABLE IF NOT EXISTS dias_bloqueados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL,
    tipo ENUM('feriado', 'domingo', 'outro') NOT NULL,
    descricao VARCHAR(200),
    bloqueado BOOLEAN DEFAULT TRUE,
    criado_por INT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_modificacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_data (data),
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_data (data),
    INDEX idx_bloqueado (bloqueado)
);

-- Comentários explicativos:
-- bloqueado = TRUE: dia está bloqueado para reservas (não pode agendar)
-- bloqueado = FALSE: dia foi desbloqueado pelo admin (pode agendar mesmo sendo feriado/domingo)
