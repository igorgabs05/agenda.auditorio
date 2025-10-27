<?php
// login.php - Página de login
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$erro = '';

if($_POST) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    if($user->login($email, $senha)) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_nome'] = $user->nome;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_tipo'] = $user->tipo;
        
        header("Location: index.php");
        exit();
    } else {
        $erro = "Email ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Reserva de Auditório Senac</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/senac-theme.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="login-card">
                        <div class="login-header">
                            <img src="senacno.png" alt="SENAC" class="logo-senac mb-3">
                            <h2 class="mb-0">Sistema de Reserva de Auditório</h2>
                        </div>
                        <div class="p-4">
                    
                        <?php if($erro): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $erro; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="seu.email@exemplo.com">
                            </div>
                            
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required placeholder="Sua senha">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>
                        </form>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Apenas administradores podem cadastrar novos usuários.<br>
                                Entre em contato com o administrador do sistema.
                            </small>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
