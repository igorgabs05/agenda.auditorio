<?php
// api/calendario.php - API para eventos do calendário
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

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $start = $input['start'] ?? null;
    $end = $input['end'] ?? null;
    
    try {
        $stmt = $reserva->buscarParaCalendario($start, $end);
        $events = [];
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = [
                'id' => $row['id'],
                'title' => $row['usuario_nome'] . ' - ' . substr($row['motivo'], 0, 30) . '...',
                'start' => $row['data_reserva'] . 'T' . $row['hora_inicio'],
                'end' => $row['data_reserva'] . 'T' . $row['hora_fim'],
                'className' => 'fc-event-' . strtolower($row['status']),
                'extendedProps' => [
                    'usuario_nome' => $row['usuario_nome'],
                    'motivo' => $row['motivo'],
                    'hora_inicio' => $row['hora_inicio'],
                    'hora_fim' => $row['hora_fim'],
                    'status' => $row['status'],
                    'observacoes' => $row['observacoes']
                ]
            ];
        }
        
        echo json_encode(['success' => true, 'events' => $events]);
        
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao carregar eventos']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
