<?php
// api/dias_bloqueados.php - API para gerenciar dias bloqueados/desbloqueados
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar se usuário está logado
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Faça login.']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Para operações de modificação (POST, DELETE), verificar se é admin
if(($method === 'POST' || $method === 'DELETE') && $_SESSION['user_tipo'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem modificar bloqueios.']);
    exit();
}

try {
    switch($method) {
        case 'GET':
            // Listar todos os dias bloqueados/desbloqueados
            $ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
            
            $query = "SELECT * FROM dias_bloqueados 
                      WHERE YEAR(data) = :ano 
                      ORDER BY data ASC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':ano', $ano);
            $stmt->execute();
            
            $dias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'dias' => $dias,
                'ano' => $ano
            ]);
            break;
            
        case 'POST':
            $action = $input['action'] ?? null;
            
            if($action === 'toggle') {
                // Alternar status de bloqueio de um dia
                $data = $input['data'] ?? null;
                $tipo = $input['tipo'] ?? 'outro';
                $descricao = $input['descricao'] ?? '';
                
                if(!$data) {
                    throw new Exception('Data é obrigatória');
                }
                
                // Verificar se o dia já existe
                $query = "SELECT id, bloqueado FROM dias_bloqueados WHERE data = :data";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':data', $data);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    // Dia já existe, alternar status
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $novo_status = !$row['bloqueado'];
                    
                    $query = "UPDATE dias_bloqueados 
                              SET bloqueado = :bloqueado, 
                                  descricao = :descricao,
                                  tipo = :tipo
                              WHERE data = :data";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':bloqueado', $novo_status, PDO::PARAM_BOOL);
                    $stmt->bindParam(':descricao', $descricao);
                    $stmt->bindParam(':tipo', $tipo);
                    $stmt->bindParam(':data', $data);
                    $stmt->execute();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => $novo_status ? 'Dia bloqueado com sucesso' : 'Dia desbloqueado com sucesso',
                        'bloqueado' => $novo_status
                    ]);
                } else {
                    // Dia não existe, criar novo registro como desbloqueado
                    $query = "INSERT INTO dias_bloqueados 
                              (data, tipo, descricao, bloqueado, criado_por) 
                              VALUES (:data, :tipo, :descricao, FALSE, :criado_por)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':data', $data);
                    $stmt->bindParam(':tipo', $tipo);
                    $stmt->bindParam(':descricao', $descricao);
                    $stmt->bindParam(':criado_por', $_SESSION['user_id']);
                    $stmt->execute();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Dia desbloqueado com sucesso',
                        'bloqueado' => false
                    ]);
                }
                
            } else if($action === 'bloquear') {
                // Bloquear um dia específico
                $data = $input['data'] ?? null;
                $tipo = $input['tipo'] ?? 'outro';
                $descricao = $input['descricao'] ?? '';
                
                if(!$data) {
                    throw new Exception('Data é obrigatória');
                }
                
                // Verificar se já existe
                $query = "SELECT id FROM dias_bloqueados WHERE data = :data";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':data', $data);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    // Atualizar
                    $query = "UPDATE dias_bloqueados 
                              SET bloqueado = TRUE, descricao = :descricao, tipo = :tipo 
                              WHERE data = :data";
                } else {
                    // Inserir
                    $query = "INSERT INTO dias_bloqueados 
                              (data, tipo, descricao, bloqueado, criado_por) 
                              VALUES (:data, :tipo, :descricao, TRUE, :criado_por)";
                }
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':data', $data);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':descricao', $descricao);
                
                if(strpos($query, 'INSERT') !== false) {
                    $stmt->bindParam(':criado_por', $_SESSION['user_id']);
                }
                
                $stmt->execute();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Dia bloqueado com sucesso'
                ]);
                
            } else if($action === 'check') {
                // Verificar status de uma data específica
                $data = $input['data'] ?? null;
                
                if(!$data) {
                    throw new Exception('Data é obrigatória');
                }
                
                $query = "SELECT bloqueado FROM dias_bloqueados WHERE data = :data";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':data', $data);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode([
                        'success' => true,
                        'bloqueado' => (bool)$row['bloqueado'],
                        'customizado' => true
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'bloqueado' => null,
                        'customizado' => false
                    ]);
                }
            } else {
                throw new Exception('Ação inválida');
            }
            break;
            
        case 'DELETE':
            // Remover customização (volta ao comportamento padrão)
            $data = $input['data'] ?? null;
            
            if(!$data) {
                throw new Exception('Data é obrigatória');
            }
            
            $query = "DELETE FROM dias_bloqueados WHERE data = :data";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':data', $data);
            $stmt->execute();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Customização removida. Dia voltou ao comportamento padrão.'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
    }
    
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
