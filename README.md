# 📅 Sistema de Reserva de Auditório - SENAC

![Logo SENAC](assets/img/logo-senac.png)

Sistema completo de gerenciamento de reservas de auditório desenvolvido para o SENAC, com interface moderna, responsiva e recursos avançados de administração.

---

## 📋 Índice

- [Sobre o Sistema](#-sobre-o-sistema)
- [Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [Funcionalidades](#-funcionalidades)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Como Usar](#-como-usar)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [API Endpoints](#-api-endpoints)
- [Personalizações](#-personalizações)
- [Troubleshooting](#-troubleshooting)
- [Segurança](#-segurança)

---

## 🎯 Sobre o Sistema

O **Sistema de Reserva de Auditório SENAC** é uma aplicação web completa que permite o gerenciamento eficiente de reservas de espaços, com aprovação administrativa, notificações em tempo real e controle de disponibilidade.

### Principais Características:

- 🗓️ **Calendário Interativo** - Visualização mensal/semanal/diária
- 👥 **Dois Níveis de Acesso** - Usuários comuns e Administradores
- 🔔 **Sistema de Notificações** - Alertas em tempo real
- 📊 **Estatísticas e Gráficos** - Dashboard administrativo completo
- 🚫 **Gerenciamento de Bloqueios** - Controle de feriados e dias especiais
- 📱 **100% Responsivo** - Funciona em desktop, tablet e mobile
- 🎨 **Design Institucional** - Seguindo identidade visual SENAC

---

## 🛠️ Tecnologias Utilizadas

### Backend
- **PHP 7.4+** - Linguagem principal
- **MySQL 5.7+** - Banco de dados
- **PDO** - Abstração de banco de dados
- **Sessions** - Gerenciamento de autenticação

### Frontend
- **HTML5** - Estrutura
- **CSS3** - Estilização com tema institucional
- **JavaScript (ES6+)** - Interatividade
- **Bootstrap 5.1.3** - Framework CSS responsivo
- **FullCalendar 5.11.3** - Biblioteca de calendário
- **Chart.js 3.9.1** - Gráficos estatísticos
- **Font Awesome** - Ícones

### Arquitetura
- **MVC Pattern** - Separação de responsabilidades
- **RESTful API** - Comunicação cliente-servidor
- **AJAX** - Requisições assíncronas
- **POO** - Orientação a objetos

---

## ✨ Funcionalidades

### Para Usuários Comuns

#### 1. Visualização de Calendário
- ✅ Calendário interativo com reservas em cores
- ✅ Visualização mensal, semanal e diária
- ✅ Indicadores visuais de feriados e domingos
- ✅ Dias bloqueados/liberados pelo admin visíveis

#### 2. Solicitação de Reservas
- ✅ Clique no dia desejado para solicitar
- ✅ Formulário com validação
- ✅ Verificação automática de conflitos
- ✅ Visualização de horários já ocupados
- ✅ Campo de motivo e observações

#### 3. Gerenciamento de Reservas
- ✅ Lista de todas as suas reservas
- ✅ Status visual (Pendente, Aprovado, Recusado, Cancelado)
- ✅ Cancelamento de reservas pendentes
- ✅ Visualização de observações do admin

#### 4. Notificações
- ✅ Notificações em tempo real
- ✅ Badge com contagem de não lidas
- ✅ Histórico completo
- ✅ Notificações de aprovação/recusa com motivo

#### 5. Perfil
- ✅ Visualização de dados pessoais
- ✅ Alteração de senha
- ✅ Edição de informações

### Para Administradores

#### 1. Dashboard Completo
- ✅ Cards com métricas principais
- ✅ Reservas pendentes de aprovação
- ✅ Total de usuários ativos
- ✅ Estatísticas mensais e anuais

#### 2. Gestão de Reservas
- ✅ Visualização de todas as reservas
- ✅ Aprovação/Recusa com observações
- ✅ Criação direta de reservas
- ✅ Calendário administrativo
- ✅ Cancelamento de reservas

#### 3. Gerenciamento de Usuários
- ✅ Lista completa de usuários
- ✅ Criação de novos usuários
- ✅ Edição de dados
- ✅ Ativação/Desativação
- ✅ Alteração de tipo (admin/usuário)

#### 4. Gerenciamento de Bloqueios
- ✅ **Desbloqueio de feriados** - Liberar feriados para eventos especiais
- ✅ **Desbloqueio de domingos** - Permitir reservas em domingos específicos
- ✅ **Bloqueio de dias específicos** - Bloquear dias para manutenção
- ✅ Lista de customizações com histórico
- ✅ Restauração ao padrão

#### 5. Estatísticas e Gráficos
- 📊 **Reservas por Mês** - Gráfico de barras
- 🍩 **Status das Reservas** - Gráfico de rosca
- 📈 **Novos Usuários** - Gráfico de linha (12 meses)
- 📊 **Top 10 Usuários** - Ranking de reservas aprovadas
- 📋 **Cards de Resumo** - Métricas principais

---

## 🚀 Instalação

### Pré-requisitos

- XAMPP 7.4+ ou servidor com:
  - Apache 2.4+
  - PHP 7.4+
  - MySQL 5.7+
- Navegador moderno (Chrome, Firefox, Edge, Safari)

### Passo a Passo

#### 1. Clonar/Baixar o Projeto

```bash
# Coloque os arquivos em:
C:\xampp\htdocs\agenda auditorio\
```

#### 2. Criar Banco de Dados

```sql
-- Execute no phpMyAdmin ou MySQL Workbench:
CREATE DATABASE IF NOT EXISTS agenda_auditorio;
USE agenda_auditorio;

-- Execute o arquivo:
source database.sql
```

Ou importe manualmente:
- Acesse: `http://localhost/phpmyadmin`
- Crie banco: `agenda_auditorio`
- Importe: `database.sql`

#### 3. Executar Script de Bloqueios

```sql
-- Para habilitar gerenciamento de dias bloqueados:
source database/add_dias_bloqueados.sql
```

#### 4. Configurar Banco de Dados

Edite `config/database.php`:

```php
private $host = "localhost";
private $db_name = "agenda_auditorio";
private $username = "root";
private $password = ""; // Sua senha do MySQL
```

#### 5. Iniciar Servidor

```bash
# Inicie o XAMPP
# Apache e MySQL devem estar rodando
```

#### 6. Acessar o Sistema

```
http://localhost/agenda%20auditorio/
```

### Credenciais Padrão

**Administrador:**
- Email: `admin@auditorio.com`
- Senha: `password`

> ⚠️ **IMPORTANTE**: Altere a senha padrão após primeiro login!

---

## ⚙️ Configuração

### Configurações de Banco de Dados

**Arquivo:** `config/database.php`

```php
class Database {
    private $host = "localhost";      // Host do MySQL
    private $db_name = "agenda_auditorio"; // Nome do banco
    private $username = "root";       // Usuário
    private $password = "";           // Senha
}
```

### Feriados Personalizados

**Arquivo:** `assets/js/utils.js`

```javascript
const feriados = {
    fixos: [
        { mes: 1, dia: 1, nome: 'Ano Novo' },
        { mes: 4, dia: 21, nome: 'Tiradentes' },
        // Adicione mais feriados fixos aqui
    ],
    moveis: {
        2025: [
            { mes: 3, dia: 4, nome: 'Carnaval' },
            // Adicione feriados móveis por ano
        ]
    }
};
```

### Cores do Tema

**Arquivo:** `assets/css/senac-theme.css`

```css
:root {
    --senac-azul: #003C7E;
    --senac-laranja: #F58220;
    --senac-branco: #FFFFFF;
}
```

---

## 📖 Como Usar

### Para Usuários

#### 1. Fazer Login
1. Acesse o sistema
2. Digite email e senha
3. Clique em "Entrar"

#### 2. Solicitar Reserva
1. Visualize o calendário
2. Clique no dia desejado (verde = disponível)
3. Preencha o formulário:
   - Data da reserva
   - Horário início e fim
   - Motivo da reserva
   - Observações (opcional)
4. Clique em "Solicitar Reserva"
5. Aguarde aprovação do admin

#### 3. Acompanhar Reservas
1. Menu → "Minhas Reservas"
2. Visualize status:
   - 🟡 **Pendente** - Aguardando aprovação
   - 🟢 **Aprovado** - Reserva confirmada
   - 🔴 **Recusado** - Reserva não aprovada
   - ⚫ **Cancelado** - Reserva cancelada

### Para Administradores

#### 1. Aprovar Reservas
1. Login como admin
2. Painel Admin → Solicitações Pendentes
3. Revise a solicitação
4. Clique em ✅ **Aprovar** ou ❌ **Recusar**

#### 2. Gerenciar Bloqueios de Dias

**Desbloquear Feriado/Domingo:**
1. Painel Admin → Gerenciar Bloqueios
2. Selecione a data
3. Adicione descrição (ex: "Evento Cultural")
4. Clique em "Desbloquear"
5. ✅ Dia ficará disponível para todos

**Bloquear Dia Específico:**
1. Painel Admin → Gerenciar Bloqueios
2. Selecione a data
3. Adicione motivo (ex: "Manutenção")
4. Clique em "Bloquear"
5. 🚫 Dia ficará indisponível

#### 3. Visualizar Estatísticas
1. Painel Admin → Estatísticas
2. Visualize gráficos e métricas

---

## 📁 Estrutura do Projeto

```
agenda auditorio/
├── api/                          # APIs REST
│   ├── calendario.php
│   ├── dias_bloqueados.php
│   ├── estatisticas.php
│   ├── horarios.php
│   ├── notificacoes.php
│   ├── reservas.php
│   └── usuarios.php
│
├── assets/
│   ├── css/
│   │   └── senac-theme.css      # Tema + responsivo
│   ├── img/
│   │   └── logo-senac.png       # Logo SENAC
│   └── js/
│       ├── admin.js
│       ├── main.js
│       ├── minhas-reservas.js
│       └── utils.js
│
├── classes/                      # POO
│   ├── Notificacao.php
│   ├── Reserva.php
│   └── User.php
│
├── config/
│   └── database.php
│
├── database/
│   └── add_dias_bloqueados.sql
│
├── admin.php
├── index.php
├── login.php
└── README.md
```

---

## 🔌 API Endpoints

### Dias Bloqueados
- **GET** `/api/dias_bloqueados.php?ano=2025` - Listar (todos)
- **POST** `/api/dias_bloqueados.php` - Modificar (admin)
- **DELETE** `/api/dias_bloqueados.php` - Remover (admin)

### Reservas
- **POST** `/api/reservas.php` - Criar/Atualizar
- **GET** `/api/reservas.php?action=get&id=X` - Buscar

### Estatísticas (Admin)
- **GET** `/api/estatisticas.php?action=resumo`
- **GET** `/api/estatisticas.php?action=reservas_ano`
- **GET** `/api/estatisticas.php?action=status_reservas`

---

## 🎨 Personalizações

### Alterar Logo
Substitua: `assets/img/logo-senac.png`

### Alterar Cores
Edite: `assets/css/senac-theme.css`

### Adicionar Feriados
Edite: `assets/js/utils.js`

---

## 🐛 Troubleshooting

### Erro: "Banco de dados não conectado"
1. Verifique `config/database.php`
2. MySQL está rodando?
3. Banco existe?

### Dias desbloqueados não funcionam
Consulte: `TESTE_DIAS_DESBLOQUEADOS.md`

### Gráficos não aparecem
1. Verifique internet (CDN)
2. Console → Erros?
3. Clique na aba Estatísticas

---

## 🔒 Segurança

✅ Autenticação com sessões PHP
✅ SQL Injection protection (PDO)
✅ XSS Protection
✅ Password hashing (bcrypt)
✅ CSRF validation
✅ Input validation

**Recomendações:**
- Altere senha padrão
- Use HTTPS em produção
- Backup regular

---

## 📱 Compatibilidade

- ✅ Chrome, Firefox, Safari, Edge
- ✅ Desktop, Tablet, Mobile
- ✅ Resoluções de 320px a 1920px+

---

## 📝 Notas de Versão

### v2.0.0 (Atual)
- ✨ Sistema de bloqueios customizados
- ✨ Estatísticas e gráficos
- ✨ Responsividade completa
- ✨ Notificações com motivo

---

**Sistema de Reserva de Auditório SENAC**
*Versão 2.0.0 - 2025*

© 2025 SENAC - Todos os direitos reservados.
