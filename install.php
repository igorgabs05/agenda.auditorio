<?php
// install.php - Script de instalação/configuração
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Sistema de Reserva de Auditório</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Instalação do Sistema
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5>Passos para Instalação:</h5>
                            <ol>
                                <li><strong>Banco de Dados:</strong> Execute o arquivo <code>database.sql</code> no seu MySQL</li>
                                <li><strong>Configuração:</strong> Edite o arquivo <code>config/database.php</code> com suas credenciais</li>
                                <li><strong>Servidor Web:</strong> Configure seu servidor web (Apache/Nginx) para servir os arquivos PHP</li>
                                <li><strong>Acesso:</strong> Acesse o sistema através do navegador</li>
                            </ol>
                        </div>
                        
                        <h5>Credenciais Padrão do Administrador:</h5>
                        <div class="alert alert-warning">
                            <strong>Email:</strong> admin@auditorio.com<br>
                            <strong>Senha:</strong> password
                        </div>
                        
                        <h5>Verificação do Sistema:</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-database fa-2x text-primary mb-2"></i>
                                        <h6>Banco de Dados</h6>
                                        <?php
                                        try {
                                            require_once 'config/database.php';
                                            $database = new Database();
                                            $db = $database->getConnection();
                                            echo '<span class="badge bg-success">Conectado</span>';
                                        } catch(Exception $e) {
                                            echo '<span class="badge bg-danger">Erro</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-server fa-2x text-primary mb-2"></i>
                                        <h6>Servidor PHP</h6>
                                        <?php
                                        if(version_compare(PHP_VERSION, '7.4.0') >= 0) {
                                            echo '<span class="badge bg-success">PHP ' . PHP_VERSION . '</span>';
                                        } else {
                                            echo '<span class="badge bg-danger">PHP ' . PHP_VERSION . ' (Requer 7.4+)</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>Extensões PHP Necessárias:</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    PDO
                                    <?php echo extension_loaded('pdo') ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Faltando</span>'; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    PDO_MySQL
                                    <?php echo extension_loaded('pdo_mysql') ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Faltando</span>'; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    JSON
                                    <?php echo extension_loaded('json') ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Faltando</span>'; ?>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <a href="login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Acessar Sistema
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
