-- Criar tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT,
    descricao TEXT,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir configurações padrão
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('bloquear_domingos', '1', 'Bloquear agendamentos aos domingos (1=sim, 0=não)'),
('feriados_desbloqueados', '[]', 'Lista de feriados desbloqueados em formato JSON (ex: ["01-01", "12-25"])')
ON DUPLICATE KEY UPDATE chave=chave;
