# Sistema de Gerenciamento de Dias Bloqueados

## 📋 O que foi implementado

Foi adicionado um sistema completo para que o administrador possa **desbloquear feriados e domingos** ou **bloquear dias específicos** de acordo com suas necessidades.

## 🚀 Como Instalar

### Passo 1: Executar o SQL

Execute o script SQL no banco de dados MySQL:

```sql
-- Localize e execute o arquivo:
database/add_dias_bloqueados.sql
```

Ou execute manualmente:

```sql
USE agenda_auditorio;

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
```

### Passo 2: Verificar arquivos

Certifique-se de que os seguintes arquivos foram criados/atualizados:

**Novos arquivos:**
- `api/dias_bloqueados.php` - API para gerenciar bloqueios
- `database/add_dias_bloqueados.sql` - Script de criação da tabela

**Arquivos atualizados:**
- `admin.php` - Adicionada nova aba "Gerenciar Bloqueios"
- `assets/js/admin.js` - Funções de gerenciamento de bloqueios
- `assets/js/utils.js` - Funções de verificação com suporte a customização
- `assets/js/main.js` - Atualização do calendário para mostrar dias desbloqueados
- `classes/Reserva.php` - Método de validação de dias bloqueados
- `api/reservas.php` - Validação de bloqueios ao criar/aprovar reservas

## 📖 Como Usar

### Para Administradores

1. **Acessar o Painel Admin**
   - Login como administrador
   - Ir para "Painel Admin"
   - Clicar na aba "Gerenciar Bloqueios"

2. **Desbloquear um Feriado ou Domingo**
   - Selecione a data desejada
   - Adicione uma descrição opcional (ex: "Evento especial")
   - Clique em "Desbloquear"
   - O dia ficará disponível para reservas

3. **Bloquear um Dia Específico**
   - Selecione a data desejada
   - Adicione o motivo (ex: "Manutenção do auditório")
   - Clique em "Bloquear"
   - O dia ficará indisponível para reservas

4. **Alternar Status**
   - Na tabela de dias customizados, clique em "Alternar"
   - O status mudará entre bloqueado/desbloqueado

5. **Resetar para Padrão**
   - Clique em "Resetar" para remover a customização
   - O dia voltará ao comportamento padrão do sistema

### Comportamento no Calendário

- **Dias desbloqueados**: Aparecem com badge verde "✔ Liberado"
- **Dias bloqueados**: Aparecem com badge vermelho "❌ Bloqueado"
- **Feriados (padrão)**: Aparecem com o nome do feriado
- **Domingos (padrão)**: Aparecem desabilitados sem badge especial

### Para Usuários Comuns

Os usuários verão automaticamente:
- Dias normalmente bloqueados (feriados/domingos) que foram liberados pelo admin
- Dias normais que foram bloqueados pelo admin
- Mensagens claras ao tentar reservar dias indisponíveis

## 🔧 Funcionamento Técnico

### Lógica de Bloqueio

1. **Sem customização**: Comportamento padrão
   - Domingos: bloqueados
   - Feriados fixos: bloqueados
   - Outros dias: disponíveis

2. **Com customização do admin**:
   - Se `bloqueado = TRUE`: dia bloqueado (independente de ser feriado/domingo)
   - Se `bloqueado = FALSE`: dia desbloqueado (mesmo sendo feriado/domingo)

### Validações

- Frontend (JavaScript): Verificação visual no calendário
- Backend (PHP): Validação ao criar/aprovar reservas
- Cache: Sistema de cache de 1 minuto no frontend para performance

### Arquivos da API

**GET /api/dias_bloqueados.php?ano=2025**
- Lista todos os dias customizados do ano

**POST /api/dias_bloqueados.php**
- `action: "toggle"` - Alterna bloqueio/desbloqueio
- `action: "bloquear"` - Força bloqueio de um dia
- `action: "check"` - Verifica status de uma data

**DELETE /api/dias_bloqueados.php**
- Remove customização, volta ao padrão

## 🎯 Casos de Uso

### Exemplo 1: Evento Especial no Domingo
1. Admin acessa "Gerenciar Bloqueios"
2. Seleciona o domingo do evento
3. Adiciona descrição: "Evento cultural especial"
4. Clica em "Desbloquear"
5. Usuários podem solicitar reservas neste domingo

### Exemplo 2: Manutenção em Dia Normal
1. Admin acessa "Gerenciar Bloqueios"
2. Seleciona a data da manutenção
3. Adiciona motivo: "Manutenção preventiva do ar-condicionado"
4. Clica em "Bloquear"
5. Usuários não podem solicitar reservas neste dia

### Exemplo 3: Cancelar Liberação
1. Admin acessa "Gerenciar Bloqueios"
2. Localiza o dia na tabela
3. Clica em "Resetar"
4. Dia volta ao comportamento padrão (bloqueado se for feriado/domingo)

## ⚠️ Observações Importantes

1. **Reservas Existentes**: Dias já reservados não são afetados ao bloquear/desbloquear
2. **Feriados Móveis**: Carnaval, Páscoa e Corpus Christi estão programados para 2025 e 2026
3. **Admin Override**: Admin pode criar reservas diretamente, mas precisa desbloquear o dia primeiro
4. **Auditoria**: Todas as mudanças ficam registradas com data e usuário que fez a modificação

## 🐛 Solução de Problemas

**Erro "Tabela dias_bloqueados não existe"**
- Execute o script SQL do Passo 1

**Dias não aparecem desbloqueados no calendário**
- Recarregue a página (F5)
- Verifique se a API está acessível em `api/dias_bloqueados.php`

**Admin não consegue criar reserva em dia desbloqueado**
- Certifique-se de que o dia foi realmente desbloqueado (verifique na tabela)
- Tente recarregar o cache do navegador (Ctrl+Shift+R)

## 📞 Suporte

Para dúvidas ou problemas, verifique:
1. Console do navegador (F12) para erros JavaScript
2. Logs do PHP em `error_log`
3. Permissões do banco de dados
