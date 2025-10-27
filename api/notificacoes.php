<?php
// api/notificacoes.php - API para gerenciar notificações
session_start();
require_once '../config/database.php';
require_once '../classes/Notificacao.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$notificacao = new Notificacao($db);

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch($action) {
            case 'list':
                // Listar notificações (últimas 5)
                $stmt = $notificacao->listarPorUsuario($_SESSION['user_id'], 5);
                $notifications = [];
                
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $notifications[] = [
                        'id' => $row['id'],
                        'titulo' => $row['titulo'],
                        'mensagem' => $row['mensagem'],
                        'tipo' => $row['tipo'],
                        'lida' => (bool)$row['lida'],
                        'data_criacao' => $row['data_criacao']
                    ];
                }
                
                echo json_encode(['success' => true, 'notifications' => $notifications]);
                break;
                
            case 'count':
                // Contar notificações não lidas
                $count = $notificacao->contarNaoLidas($_SESSION['user_id']);
                echo json_encode(['success' => true, 'count' => $count]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        }
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
    
} elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    try {
        switch($action) {
            case 'mark_read':
                // Marcar notificação como lida
                $notif_id = $input['id'];
                
                if($notificacao->marcarComoLida($notif_id)) {
                    echo json_encode(['success' => true, 'message' => 'Notificação marcada como lida']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao marcar notificação']);
                }
                break;
                
            case 'mark_all_read':
                // Marcar todas as notificações como lidas
                $stmt = $db->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = :usuario_id AND lida = 0");
                $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
                
                if($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Todas as notificações foram marcadas como lidas']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao marcar notificações']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        }
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
