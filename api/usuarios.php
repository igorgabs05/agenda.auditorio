<?php
// api/usuarios.php - API para gerenciar usuários (apenas admin)
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

// Verificar se é admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    $action = $input['action'] ?? '';
    
    error_log("API Usuarios - Action: " . $action);
    
    try {
        switch($action) {
            case 'create':
                // Criar novo usuário
                error_log("API Usuarios - Tentando criar usuário");
                error_log("API Usuarios - Input: " . print_r($input, true));
                
                $nome = trim($input['nome'] ?? '');
                $email = trim($input['email'] ?? '');
                $senha = $input['senha'] ?? '';
                $tipo = $input['tipo'] ?? '';
                
                error_log("API Usuarios - Nome: $nome, Email: $email, Tipo: $tipo");
                
                // Validar dados
                if(empty($nome) || empty($email) || empty($senha) || empty($tipo)) {
                    error_log("API Usuarios - Campos obrigatórios faltando");
                    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
                    exit();
                }
                
                if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Email inválido']);
                    exit();
                }
                
                if(strlen($senha) < 6) {
                    echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres']);
                    exit();
                }
                
                if(!in_array($tipo, ['usuario', 'admin'])) {
                    echo json_encode(['success' => false, 'message' => 'Tipo de usuário inválido']);
                    exit();
                }
                
                // Verificar se email já existe
                $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Este email já está cadastrado']);
                    exit();
                }
                
                // Criar usuário
                error_log("API Usuarios - Criando usuário na base de dados");
                $user->nome = $nome;
                $user->email = $email;
                $user->senha = $senha;
                $user->tipo = $tipo;
                
                try {
                    if($user->cadastrar()) {
                        error_log("API Usuarios - Usuário criado com sucesso");
                        echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso']);
                    } else {
                        error_log("API Usuarios - Falha ao cadastrar usuário");
                        echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário']);
                    }
                } catch(Exception $e) {
                    error_log("API Usuarios - Exceção ao criar: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário: ' . $e->getMessage()]);
                }
                break;
                
            case 'update':
                // Atualizar usuário
                $id = $input['id'];
                $nome = trim($input['nome']);
                $email = trim($input['email']);
                $tipo = $input['tipo'];
                $ativo = $input['ativo'];
                
                // Validar dados
                if(empty($nome) || empty($email) || empty($tipo)) {
                    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
                    exit();
                }
                
                if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Email inválido']);
                    exit();
                }
                
                if(!in_array($tipo, ['usuario', 'admin'])) {
                    echo json_encode(['success' => false, 'message' => 'Tipo de usuário inválido']);
                    exit();
                }
                
                // Verificar se email já existe em outro usuário
                $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    echo json_encode(['success' => false, 'message' => 'Este email já está sendo usado por outro usuário']);
                    exit();
                }
                
                // Atualizar usuário
                $query = "UPDATE usuarios SET nome = :nome, email = :email, tipo = :tipo, ativo = :ativo WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':ativo', $ativo);
                $stmt->bindParam(':id', $id);
                
                if($stmt->execute()) {
                    // Atualizar senha se fornecida
                    if(!empty($input['senha'])) {
                        if(strlen($input['senha']) < 6) {
                            echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres']);
                            exit();
                        }
                        
                        $senha_hash = password_hash($input['senha'], PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
                        $stmt->bindParam(':senha', $senha_hash);
                        $stmt->bindParam(':id', $id);
                        $stmt->execute();
                    }
                    
                    echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuário']);
                }
                break;
                
            case 'delete':
                // Desativar usuário (não deletar fisicamente)
                $id = $input['id'];
                
                // Não permitir desativar o próprio usuário
                if($id == $_SESSION['user_id']) {
                    echo json_encode(['success' => false, 'message' => 'Você não pode desativar sua própria conta']);
                    exit();
                }
                
                $query = "UPDATE usuarios SET ativo = 0 WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Usuário desativado com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao desativar usuário']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        }
        
    } catch(Exception $e) {
        error_log("API Usuarios - Erro: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
    
} elseif($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch($action) {
            case 'list':
                // Listar todos os usuários
                $query = "SELECT id, nome, email, tipo, ativo, data_cadastro FROM usuarios ORDER BY nome ASC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                $usuarios = [];
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $usuarios[] = $row;
                }
                
                echo json_encode(['success' => true, 'usuarios' => $usuarios]);
                break;
                
            case 'get':
                // Buscar usuário por ID
                $id = $_GET['id'];
                
                $query = "SELECT id, nome, email, tipo, ativo, data_cadastro FROM usuarios WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'usuario' => $usuario]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        }
        
    } catch(Exception $e) {
        error_log("API Usuarios GET - Erro: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
