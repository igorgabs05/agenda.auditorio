<?php
// minhas-reservas.php - Página para usuários visualizarem suas reservas
session_start();

if(!isset($_SESSION['user_id'])) {
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

// Buscar reservas do usuário
$reservas_usuario = $reserva->listarPorUsuario($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas - Sistema de Reserva Senac</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/senac-theme.css" rel="stylesheet">
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
                        <a class="nav-link active" href="minhas-reservas.php">Minhas Reservas</a>
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
                            <li><a class="dropdown-item" href="minhas-reservas.php">Minhas Reservas</a></li>
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
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Minhas Reservas
                        </h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSolicitar">
                            <i class="fas fa-plus me-1"></i>Nova Solicitação
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if($reservas_usuario->rowCount() > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Horário</th>
                                            <th>Motivo</th>
                                            <th>Status</th>
                                            <th>Solicitado em</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $reservas_usuario->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($row['data_reserva'])); ?></td>
                                                <td><?php echo $row['hora_inicio'] . ' - ' . $row['hora_fim']; ?></td>
                                                <td><?php echo htmlspecialchars($row['motivo']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                                        <?php echo $row['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($row['data_solicitacao'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalhes(<?php echo $row['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if($row['status'] === 'PENDENTE'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelarReserva(<?php echo $row['id']; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhuma reserva encontrada</h5>
                                <p class="text-muted">Você ainda não fez nenhuma solicitação de reserva.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSolicitar">
                                    <i class="fas fa-plus me-1"></i>Fazer Primeira Solicitação
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Solicitar Reserva -->
    <div class="modal fade" id="modalSolicitar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Solicitar Reserva de Auditório</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formSolicitar">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="data_reserva" class="form-label">Data da Reserva</label>
                                    <input type="date" class="form-control" id="data_reserva" name="data_reserva" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="hora_inicio" class="form-label">Hora Início</label>
                                    <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="hora_fim" class="form-label">Hora Fim</label>
                                    <input type="time" class="form-control" id="hora_fim" name="hora_fim" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo da Reserva</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações (opcional)</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                        </div>
                        
                        <!-- Área para mostrar horários ocupados -->
                        <div id="horarios-ocupados" class="mb-3">
                            <!-- Horários ocupados serão carregados aqui -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Solicitar Reserva</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script src="assets/js/minhas-reservas.js"></script>
</body>
</html>
