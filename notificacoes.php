<?php
// notificacoes.php - Página de notificações
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Notificacao.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$notificacao = new Notificacao($db);

$user->buscarPorId($_SESSION['user_id']);
$notificacoes_nao_lidas = $notificacao->contarNaoLidas($_SESSION['user_id']);

// Buscar todas as notificações do usuário
$stmt = $notificacao->listarPorUsuario($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Sistema de Reserva Senac</title>
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
                    <?php if($_SESSION['user_tipo'] == 'usuario'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="minhas-reservas.php">Minhas Reservas</a>
                    </li>
                    <?php endif; ?>
                    <?php if($_SESSION['user_tipo'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Painel Admin</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user->nome); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php">Meu Perfil</a></li>
                            <?php if($_SESSION['user_tipo'] == 'usuario'): ?>
                            <li><a class="dropdown-item" href="minhas-reservas.php">Minhas Reservas</a></li>
                            <?php endif; ?>
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
                            <i class="fas fa-bell me-2"></i>Notificações
                        </h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="marcarTodasComoLidas()">
                            <i class="fas fa-check-double me-1"></i>Marcar todas como lidas
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if($stmt->rowCount() > 0): ?>
                            <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="notification-item <?php echo $row['lida'] ? '' : 'unread'; ?> mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($row['titulo']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($row['mensagem']); ?></p>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($row['data_criacao'])); ?>
                                            </small>
                                        </div>
                                        <div class="ms-3">
                                            <?php if(!$row['lida']): ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="marcarComoLida(<?php echo $row['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhuma notificação</h5>
                                <p class="text-muted">Você não possui notificações no momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script>
        function marcarComoLida(notifId) {
            fetch('api/notificacoes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_read',
                    id: notifId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function marcarTodasComoLidas() {
            if (confirm('Tem certeza que deseja marcar todas as notificações como lidas?')) {
                fetch('api/notificacoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'mark_all_read'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html>
