<?php
// admin.php - Painel administrativo
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Reserva.php';
require_once 'classes/Notificacao.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$reserva = new Reserva($db);
$notificacao = new Notificacao($db);

$user->buscarPorId($_SESSION['user_id']);
$notificacoes_nao_lidas = $notificacao->contarNaoLidas($_SESSION['user_id']);

// Buscar dados para dashboard
$reservas_pendentes = $reserva->listarPendentes();
$total_usuarios = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'usuario' AND ativo = 1")->fetch()['total'];
$total_reservas_mes = $db->query("SELECT COUNT(*) as total FROM reservas WHERE MONTH(data_reserva) = MONTH(CURRENT_DATE()) AND YEAR(data_reserva) = YEAR(CURRENT_DATE())")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Sistema de Reserva Senac</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="assets/css/senac-theme.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                   <img src="logosenac.png" alt="SENAC" class="logo-senac me-2">
                Sistema de Reserva
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Calendário</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">Painel Admin</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle notification-badge" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if($notificacoes_nao_lidas > 0): ?>
                                <span class="notification-count"><?php echo $notificacoes_nao_lidas; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="width: 350px;">
                            <li><h6 class="dropdown-header">Notificações</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <div id="notificacoes-lista">
                                <!-- Notificações serão carregadas via AJAX -->
                            </div>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="notificacoes.php">Ver todas</a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user->nome); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php">Meu Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="container mt-4">
        <!-- Dashboard Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">Pendentes</h5>
                        <h3 class="text-warning"><?php echo $reservas_pendentes->rowCount(); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h5 class="card-title">Usuários</h5>
                        <h3 class="text-primary"><?php echo $total_usuarios; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar fa-2x text-success mb-2"></i>
                        <h5 class="card-title">Este Mês</h5>
                        <h3 class="text-success"><?php echo $total_reservas_mes; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-bell fa-2x text-danger mb-2"></i>
                        <h5 class="card-title">Notificações</h5>
                        <h3 class="text-danger"><?php echo $notificacoes_nao_lidas; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="calendario-tab" data-bs-toggle="tab" data-bs-target="#calendario" type="button" role="tab">
                    <i class="fas fa-calendar-alt me-1"></i>Calendário e Reservas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button" role="tab">
                    <i class="fas fa-users me-1"></i>Gerenciar Usuários
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="bloqueios-tab" data-bs-toggle="tab" data-bs-target="#bloqueios" type="button" role="tab">
                    <i class="fas fa-ban me-1"></i>Gerenciar Bloqueios
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="gerenciar-reservas-tab" data-bs-toggle="tab" data-bs-target="#gerenciar-reservas" type="button" role="tab">
                    <i class="fas fa-list me-1"></i>Gerenciar Reservas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="estatisticas-tab" data-bs-toggle="tab" data-bs-target="#estatisticas" type="button" role="tab">
                    <i class="fas fa-chart-bar me-1"></i>Estatísticas
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="adminTabsContent">
            <!-- Tab Calendário -->
            <div class="tab-pane fade show active" id="calendario" role="tabpanel">
                <div class="row">
                    <!-- Calendário Admin -->
                    <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Calendário Administrativo
                        </h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCriarReserva">
                            <i class="fas fa-plus me-1"></i>Nova Reserva
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="calendar-admin"></div>
                    </div>
                </div>
            </div>

            <!-- Solicitações Pendentes -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Solicitações Pendentes
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <?php if($reservas_pendentes->rowCount() > 0): ?>
                            <?php while($row = $reservas_pendentes->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="notification-item mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <strong><?php echo htmlspecialchars($row['usuario_nome']); ?></strong>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($row['data_solicitacao'])); ?></small>
                                    </div>
                                    <p class="mb-1"><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($row['data_reserva'])); ?></p>
                                    <p class="mb-1"><strong>Horário:</strong> <?php echo $row['hora_inicio'] . ' - ' . $row['hora_fim']; ?></p>
                                    <p class="mb-2"><strong>Motivo:</strong> <?php echo htmlspecialchars($row['motivo']); ?></p>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-success btn-sm" onclick="aprovarReserva(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-check"></i> Aprovar
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="recusarReserva(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-times"></i> Recusar
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted text-center">Nenhuma solicitação pendente</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
                </div>
            </div>
            
            <!-- Tab Usuários -->
            <div class="tab-pane fade" id="usuarios" role="tabpanel">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>Usuários do Sistema
                                </h5>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCriarUsuario">
                                    <i class="fas fa-user-plus me-1"></i>Novo Usuário
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="tabelaUsuarios">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>Tipo</th>
                                                <th>Status</th>
                                                <th>Data Cadastro</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Usuários serão carregados via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab Bloqueios -->
            <div class="tab-pane fade" id="bloqueios" role="tabpanel">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-ban me-2"></i>Gerenciar Dias Bloqueados/Desbloqueados
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Como funciona:</strong> Por padrão, domingos e feriados são bloqueados para reservas. 
                                    Aqui você pode desbloquear esses dias ou bloquear dias específicos.
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6>Desbloquear Feriado/Domingo</h6>
                                        <form id="formDesbloquear">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="data_desbloquear" required>
                                                <input type="text" class="form-control" id="descricao_desbloquear" placeholder="Descrição (opcional)">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-unlock me-1"></i>Desbloquear
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Bloquear Dia Específico</h6>
                                        <form id="formBloquear">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="data_bloquear" required>
                                                <input type="text" class="form-control" id="descricao_bloquear" placeholder="Motivo (opcional)">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-lock me-1"></i>Bloquear
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="ano_filtro" class="form-label">Filtrar por Ano:</label>
                                    <select class="form-select" id="ano_filtro" style="width: 150px;">
                                        <option value="2025" selected>2025</option>
                                        <option value="2026">2026</option>
                                        <option value="2027">2027</option>
                                    </select>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-striped" id="tabelaBloqueios">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Tipo</th>
                                                <th>Descrição</th>
                                                <th>Status</th>
                                                <th>Modificado em</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Será preenchido via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab Gerenciar Reservas -->
            <div class="tab-pane fade" id="gerenciar-reservas" role="tabpanel">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-list me-2"></i>Todas as Reservas
                                </h5>
                                <div>
                                    <select class="form-select form-select-sm d-inline-block w-auto me-2" id="filtro-status-reservas">
                                        <option value="">Todos os Status</option>
                                        <option value="PENDENTE">Pendente</option>
                                        <option value="APROVADO">Aprovado</option>
                                        <option value="RECUSADO">Recusado</option>
                                        <option value="CANCELADO">Cancelado</option>
                                    </select>
                                    <button class="btn btn-sm btn-primary" onclick="carregarTodasReservas()">
                                        <i class="fas fa-sync"></i> Atualizar
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="tabelaTodasReservas">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Usuário</th>
                                                <th>Data</th>
                                                <th>Horário</th>
                                                <th>Motivo</th>
                                                <th>Status</th>
                                                <th>Solicitado em</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Reservas serão carregadas via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tab Estatísticas -->
            <div class="tab-pane fade" id="estatisticas" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Estatísticas do Sistema
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Cards de Resumo -->
                                <div class="row mb-4" id="cards-resumo">
                                    <div class="col-md-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body text-center">
                                                <h6>Usuários Ativos</h6>
                                                <h2 id="stat-usuarios">-</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <h6>Reservas Este Mês</h6>
                                                <h2 id="stat-reservas-mes">-</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body text-center">
                                                <h6>Reservas Este Ano</h6>
                                                <h2 id="stat-reservas-ano">-</h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-dark">
                                            <div class="card-body text-center">
                                                <h6>Taxa de Aprovação</h6>
                                                <h2 id="stat-taxa-aprovacao">-</h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Gráficos -->
                                <div class="row">
                                    <!-- Reservas por Mês -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">Reservas por Mês (Ano Atual)</h6>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="chartReservasAno" style="width:100%;max-height:300px"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Status das Reservas -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">Status das Reservas (Ano Atual)</h6>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="chartStatusReservas" style="width:100%;max-height:300px"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Novos Usuários -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">Novos Usuários (Últimos 12 Meses)</h6>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="chartUsuariosNovos" style="width:100%;max-height:300px"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Top Usuários -->
                                    <div class="col-md-6 mb-4">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0">Top 10 Usuários (Reservas Aprovadas)</h6>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="chartTopUsuarios" style="width:100%;max-height:300px"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Criar Reserva -->
    <div class="modal fade" id="modalCriarReserva" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Nova Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCriarReserva">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuario_reserva" class="form-label">Usuário</label>
                                    <select class="form-control" id="usuario_reserva" name="usuario_id" required>
                                        <option value="">Selecione um usuário</option>
                                        <?php
                                        $usuarios = $user->listarUsuarios();
                                        while($usuario = $usuarios->fetch(PDO::FETCH_ASSOC)):
                                            if($usuario['tipo'] === 'usuario'):
                                        ?>
                                            <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nome']); ?></option>
                                        <?php endif; endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="data_reserva_admin" class="form-label">Data da Reserva</label>
                                    <input type="date" class="form-control" id="data_reserva_admin" name="data_reserva" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="hora_inicio_admin" class="form-label">Hora Início</label>
                                    <input type="time" class="form-control" id="hora_inicio_admin" name="hora_inicio" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="hora_fim_admin" class="form-label">Hora Fim</label>
                                    <input type="time" class="form-control" id="hora_fim_admin" name="hora_fim" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="motivo_admin" class="form-label">Motivo da Reserva</label>
                            <textarea class="form-control" id="motivo_admin" name="motivo" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes_admin" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_admin" name="observacoes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Reserva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Criar Usuário -->
    <div class="modal fade" id="modalCriarUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formCriarUsuario">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nome_usuario" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome_usuario" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="email_usuario" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email_usuario" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="senha_usuario" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha_usuario" name="senha" required minlength="6">
                            <small class="form-text text-muted">Mínimo 6 caracteres</small>
                        </div>
                        <div class="mb-3">
                            <label for="tipo_usuario" class="form-label">Tipo de Usuário</label>
                            <select class="form-control" id="tipo_usuario" name="tipo" required>
                                <option value="">Selecione o tipo</option>
                                <option value="usuario">Usuário Comum</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Criar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Usuário -->
    <div class="modal fade" id="modalEditarUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEditarUsuario">
                    <input type="hidden" id="edit_usuario_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nome_usuario" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="edit_nome_usuario" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email_usuario" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email_usuario" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_senha_usuario" class="form-label">Nova Senha (deixe em branco para manter)</label>
                            <input type="password" class="form-control" id="edit_senha_usuario" name="senha" minlength="6">
                            <small class="form-text text-muted">Mínimo 6 caracteres</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tipo_usuario" class="form-label">Tipo de Usuário</label>
                            <select class="form-control" id="edit_tipo_usuario" name="tipo" required>
                                <option value="usuario">Usuário Comum</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_ativo_usuario" class="form-label">Status</label>
                            <select class="form-control" id="edit_ativo_usuario" name="ativo" required>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>
