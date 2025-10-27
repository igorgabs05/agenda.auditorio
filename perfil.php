<?php
// perfil.php - Página de perfil do usuário
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$user->buscarPorId($_SESSION['user_id']);

$erro = '';
$sucesso = '';

if($_POST) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Validações
    if(empty($nome) || empty($email)) {
        $erro = "Nome e email são obrigatórios.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido.";
    } else {
        // Verificar se email já existe em outro usuário
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $erro = "Este email já está sendo usado por outro usuário.";
        } else {
            // Atualizar dados básicos
            $query = "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $_SESSION['user_id']);
            
            if($stmt->execute()) {
                $_SESSION['user_nome'] = $nome;
                $_SESSION['user_email'] = $email;
                $user->nome = $nome;
                $user->email = $email;
                $sucesso = "Dados atualizados com sucesso!";
            } else {
                $erro = "Erro ao atualizar dados.";
            }
        }
    }
    
    // Atualizar senha se fornecida
    if(!empty($nova_senha)) {
        if(empty($senha_atual)) {
            $erro = "Senha atual é obrigatória para alterar a senha.";
        } elseif($nova_senha !== $confirmar_senha) {
            $erro = "Nova senha e confirmação não coincidem.";
        } elseif(strlen($nova_senha) < 6) {
            $erro = "A nova senha deve ter pelo menos 6 caracteres.";
        } else {
            // Verificar senha atual
            $stmt = $db->prepare("SELECT senha FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $_SESSION['user_id']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($senha_atual, $row['senha'])) {
                $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
                $stmt->bindParam(':senha', $nova_senha_hash);
                $stmt->bindParam(':id', $_SESSION['user_id']);
                
                if($stmt->execute()) {
                    $sucesso = "Senha alterada com sucesso!";
                } else {
                    $erro = "Erro ao alterar senha.";
                }
            } else {
                $erro = "Senha atual incorreta.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Sistema de Reserva Senac</title>
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
                            <li><a class="dropdown-item active" href="perfil.php">Meu Perfil</a></li>
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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Meu Perfil
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if($erro): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $erro; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($sucesso): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $sucesso; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($user->nome); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user->email); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo de Usuário</label>
                                <input type="text" class="form-control" id="tipo" 
                                       value="<?php echo ucfirst($user->tipo); ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="data_cadastro" class="form-label">Data de Cadastro</label>
                                <input type="text" class="form-control" id="data_cadastro" 
                                       value="<?php echo date('d/m/Y H:i', strtotime($user->data_cadastro)); ?>" readonly>
                            </div>
                            
                            <hr>
                            <h6 class="mb-3">Alterar Senha (opcional)</h6>
                            
                            <div class="mb-3">
                                <label for="senha_atual" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                            </div>
                            
                            <div class="mb-3">
                                <label for="nova_senha" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
