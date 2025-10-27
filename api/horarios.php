<?php
// api/horarios.php - API para buscar horários ocupados
session_start();
require_once '../config/database.php';
require_once '../classes/Reserva.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$reserva = new Reserva($db);

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = $_GET['data'] ?? '';
    
    if(empty($data)) {
        echo json_encode(['success' => false, 'message' => 'Data não fornecida']);
        exit();
    }
    
    try {
        // Buscar reservas aprovadas para a data específica
        $query = "SELECT hora_inicio, hora_fim, motivo, usuario_nome 
                  FROM reservas r
                  LEFT JOIN usuarios u ON r.usuario_id = u.id
                  WHERE r.data_reserva = :data 
                  AND r.status = 'APROVADO'
                  ORDER BY r.hora_inicio";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':data', $data);
        $stmt->execute();
        
        $horarios_ocupados = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $horarios_ocupados[] = [
                'hora_inicio' => $row['hora_inicio'],
                'hora_fim' => $row['hora_fim'],
                'motivo' => $row['motivo'],
                'usuario_nome' => $row['usuario_nome']
            ];
        }
        
        echo json_encode(['success' => true, 'horarios' => $horarios_ocupados]);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar horários']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
