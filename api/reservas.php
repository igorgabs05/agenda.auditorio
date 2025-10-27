<?php
// api/reservas.php - API para gerenciar reservas
session_start();
require_once '../config/database.php';
require_once '../classes/Reserva.php';
require_once '../classes/Notificacao.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$reserva = new Reserva($db);
$notificacao = new Notificacao($db);

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    $action = $input['action'] ?? '';
    
    // Debug: Log da entrada
    error_log("API Reservas - Raw input: " . $raw_input);
    error_log("API Reservas - Action: " . $action);
    error_log("API Reservas - Input decoded: " . print_r($input, true));
    error_log("API Reservas - Session user_id: " . ($_SESSION['user_id'] ?? 'NOT_SET'));
    error_log("API Reservas - Session user_nome: " . ($_SESSION['user_nome'] ?? 'NOT_SET'));
    error_log("API Reservas - Session user_tipo: " . ($_SESSION['user_tipo'] ?? 'NOT_SET'));
    
    try {
        switch($action) {
            case 'create':
                error_log("API Reservas - Iniciando criação de reserva");
                
                // Validar dados obrigatórios
                $required_fields = ['data_reserva', 'hora_inicio', 'hora_fim', 'motivo'];
                $missing_fields = [];
                
                foreach($required_fields as $field) {
                    if(empty($input[$field])) {
                        $missing_fields[] = $field;
                    }
                }
                
                if(!empty($missing_fields)) {
                    error_log("API Reservas - Campos obrigatórios faltando: " . implode(', ', $missing_fields));
                    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios não preenchidos: ' . implode(', ', $missing_fields)]);
                    exit();
                }
                
                error_log("API Reservas - Validação de campos obrigatórios passou");
                
                // Validar formato da data
                if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['data_reserva'])) {
                    echo json_encode(['success' => false, 'message' => 'Formato de data inválido']);
                    exit();
                }
                
                // Validar formato das horas
                if(!preg_match('/^\d{2}:\d{2}$/', $input['hora_inicio']) || !preg_match('/^\d{2}:\d{2}$/', $input['hora_fim'])) {
                    echo json_encode(['success' => false, 'message' => 'Formato de hora inválido']);
                    exit();
                }
                
                // Validar se hora fim é posterior à hora início
                if($input['hora_inicio'] >= $input['hora_fim']) {
                    echo json_encode(['success' => false, 'message' => 'A hora de fim deve ser posterior à hora de início']);
                    exit();
                }
                
                // Validar se a data não é no passado
                $hoje = date('Y-m-d');
                if($input['data_reserva'] < $hoje) {
                    echo json_encode(['success' => false, 'message' => 'A data deve ser hoje ou no futuro']);
                    exit();
                }
                
                // Validar se o dia não está bloqueado
                if($reserva->verificarDiaBloqueado($input['data_reserva'])) {
                    echo json_encode(['success' => false, 'message' => 'Esta data não está disponível para reservas (feriado, domingo ou bloqueio administrativo)']);
                    exit();
                }
                
                // Criar nova reserva
                error_log("API Reservas - Configurando dados da reserva");
                $reserva->usuario_id = $_SESSION['user_id'];
                $reserva->data_reserva = $input['data_reserva'];
                $reserva->hora_inicio = $input['hora_inicio'];
                $reserva->hora_fim = $input['hora_fim'];
                $reserva->motivo = trim($input['motivo']);
                $reserva->observacoes = trim($input['observacoes'] ?? '');
                $reserva->status = 'PENDENTE';
                
                error_log("API Reservas - Dados da reserva configurados: usuario_id={$reserva->usuario_id}, data={$reserva->data_reserva}, hora_inicio={$reserva->hora_inicio}, hora_fim={$reserva->hora_fim}");
                
                // Verificar conflitos
                error_log("API Reservas - Verificando conflitos");
                if($reserva->verificarConflito($reserva->data_reserva, $reserva->hora_inicio, $reserva->hora_fim)) {
                    error_log("API Reservas - Conflito detectado");
                    echo json_encode(['success' => false, 'message' => 'Já existe uma reserva aprovada neste horário']);
                    exit();
                }
                
                error_log("API Reservas - Nenhum conflito detectado, criando reserva");
                $reserva_id = $reserva->criar();
                
                if($reserva_id) {
                    error_log("API Reservas - Reserva criada com sucesso, ID: " . $reserva_id);
                    // Notificar administradores
                    $notificacao->notificarNovaSolicitacao($reserva_id, $_SESSION['user_nome']);
                    
                    echo json_encode(['success' => true, 'message' => 'Solicitação enviada com sucesso']);
                } else {
                    error_log("API Reservas - Falha ao criar reserva");
                    echo json_encode(['success' => false, 'message' => 'Erro ao criar solicitação']);
                }
                break;
                
            case 'update_status':
                // Atualizar status (apenas admin)
                if($_SESSION['user_tipo'] !== 'admin') {
                    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                    exit();
                }
                
                $reserva_id = $input['id'];
                $novo_status = $input['status'];
                $observacoes = $input['observacoes'] ?? '';
                
                // Buscar reserva para notificar usuário
                if($reserva->buscarPorId($reserva_id)) {
                    $usuario_id = $reserva->usuario_id;
                    
                    // Verificar conflitos e bloqueios se for aprovação
                    if($novo_status === 'APROVADO') {
                        // Verificar se o dia não está bloqueado
                        if($reserva->verificarDiaBloqueado($reserva->data_reserva)) {
                            echo json_encode(['success' => false, 'message' => 'Esta data não está disponível para reservas (feriado, domingo ou bloqueio administrativo)']);
                            exit();
                        }
                        
                        if($reserva->verificarConflito($reserva->data_reserva, $reserva->hora_inicio, $reserva->hora_fim, $reserva_id)) {
                            echo json_encode(['success' => false, 'message' => 'Já existe uma reserva aprovada neste horário']);
                            exit();
                        }
                    }
                    
                    if($reserva->atualizarStatus($reserva_id, $novo_status, $_SESSION['user_id'], $observacoes)) {
                        // Notificar usuário
                        $titulo = '';
                        $mensagem = '';
                        
                        switch($novo_status) {
                            case 'APROVADO':
                                $titulo = 'Reserva Aprovada';
                                $mensagem = "Sua solicitação de reserva para {$reserva->data_reserva} foi aprovada.";
                                if(!empty($observacoes)) {
                                    $mensagem .= "\nObservações: {$observacoes}";
                                }
                                break;
                            case 'RECUSADO':
                                $titulo = 'Reserva Recusada';
                                $mensagem = "Sua solicitação de reserva para {$reserva->data_reserva} foi recusada.";
                                if(!empty($observacoes)) {
                                    $mensagem .= "\nMotivo: {$observacoes}";
                                }
                                break;
                            case 'CANCELADO':
                                $titulo = 'Reserva Cancelada';
                                $mensagem = "Sua reserva para {$reserva->data_reserva} foi cancelada.";
                                if(!empty($observacoes)) {
                                    $mensagem .= "\nMotivo: {$observacoes}";
                                }
                                break;
                        }
                        
                        $notificacao->notificarUsuario($usuario_id, strtolower($novo_status), $titulo, $mensagem, $reserva_id);
                        
                        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Reserva não encontrada']);
                }
                break;
                
            case 'create_admin':
                // Criar reserva diretamente pelo admin
                if($_SESSION['user_tipo'] !== 'admin') {
                    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                    exit();
                }
                
                $reserva->usuario_id = $input['usuario_id'];
                $reserva->data_reserva = $input['data_reserva'];
                $reserva->hora_inicio = $input['hora_inicio'];
                $reserva->hora_fim = $input['hora_fim'];
                $reserva->motivo = $input['motivo'];
                $reserva->observacoes = $input['observacoes'] ?? '';
                $reserva->status = 'APROVADO';
                
                // Validar se o dia não está bloqueado (admin pode criar em dias bloqueados se necessário)
                // Remova esta validação se quiser que admin possa forçar criação em qualquer dia
                if($reserva->verificarDiaBloqueado($reserva->data_reserva)) {
                    echo json_encode(['success' => false, 'message' => 'Esta data não está disponível para reservas. Desbloqueie o dia primeiro se necessário.']);
                    exit();
                }
                
                // Verificar conflitos
                if($reserva->verificarConflito($reserva->data_reserva, $reserva->hora_inicio, $reserva->hora_fim)) {
                    echo json_encode(['success' => false, 'message' => 'Já existe uma reserva aprovada neste horário']);
                    exit();
                }
                
                $reserva_id = $reserva->criar();
                
                if($reserva_id) {
                    echo json_encode(['success' => true, 'message' => 'Reserva criada com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro ao criar reserva']);
                }
                break;
                
            case 'cancel':
                // Cancelar reserva (usuário pode cancelar apenas suas próprias reservas pendentes)
                $reserva_id = $input['id'];
                
                if($reserva->buscarPorId($reserva_id)) {
                    // Verificar se é o dono da reserva ou admin
                    if($reserva->usuario_id != $_SESSION['user_id'] && $_SESSION['user_tipo'] !== 'admin') {
                        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                        exit();
                    }
                    
                    // Só pode cancelar se estiver pendente ou aprovado
                    if($reserva->status === 'PENDENTE' || $reserva->status === 'APROVADO') {
                        $motivo_cancelamento = $input['observacoes'] ?? '';
                        
                        if($reserva->atualizarStatus($reserva_id, 'CANCELADO', $_SESSION['user_id'], $motivo_cancelamento)) {
                            // Notificar usuário se não for ele mesmo cancelando
                            if($reserva->usuario_id != $_SESSION['user_id']) {
                                $mensagem = "Sua reserva para {$reserva->data_reserva} foi cancelada.";
                                if(!empty($motivo_cancelamento)) {
                                    $mensagem .= "\nMotivo: {$motivo_cancelamento}";
                                }
                                $notificacao->notificarUsuario($reserva->usuario_id, 'cancelamento', 'Reserva Cancelada', $mensagem, $reserva_id);
                            }
                            
                            echo json_encode(['success' => true, 'message' => 'Reserva cancelada com sucesso']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Erro ao cancelar reserva']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Esta reserva não pode ser cancelada']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Reserva não encontrada']);
                }
                break;
                
            case 'delete':
                // Excluir reserva definitivamente (apenas admin)
                if($_SESSION['user_tipo'] !== 'admin') {
                    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem excluir reservas']);
                    exit();
                }
                
                $reserva_id = $input['id'];
                
                if($reserva->buscarPorId($reserva_id)) {
                    // Guardar informações para notificação antes de excluir
                    $usuario_id = $reserva->usuario_id;
                    $data_reserva = $reserva->data_reserva;
                    $hora_inicio = $reserva->hora_inicio;
                    $hora_fim = $reserva->hora_fim;
                    
                    // Excluir a reserva do banco de dados
                    $query = "DELETE FROM reservas WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $reserva_id);
                    
                    if($stmt->execute()) {
                        // Notificar usuário sobre a exclusão
                        $mensagem = "Sua reserva para {$data_reserva} das {$hora_inicio} às {$hora_fim} foi excluída pelo administrador.";
                        $notificacao->notificarUsuario($usuario_id, 'exclusao', 'Reserva Excluída', $mensagem, null);
                        
                        error_log("API Reservas - Reserva ID {$reserva_id} excluída pelo admin {$_SESSION['user_nome']}");
                        echo json_encode(['success' => true, 'message' => 'Reserva excluída com sucesso']);
                    } else {
                        error_log("API Reservas - Erro ao excluir reserva ID {$reserva_id}");
                        echo json_encode(['success' => false, 'message' => 'Erro ao excluir reserva']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Reserva não encontrada']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        }
        
    } catch(Exception $e) {
        error_log("API Reservas - Erro: " . $e->getMessage());
        error_log("API Reservas - Stack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
} elseif($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    try {
        switch($action) {
            case 'get':
                // Buscar reserva por ID
                $reserva_id = $_GET['id'];
                
                if($reserva->buscarPorId($reserva_id)) {
                    // Verificar se é o dono da reserva ou admin
                    if($reserva->usuario_id != $_SESSION['user_id'] && $_SESSION['user_tipo'] !== 'admin') {
                        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                        exit();
                    }
                    
                    echo json_encode(['success' => true, 'reserva' => [
                        'id' => $reserva->id,
                        'data_reserva' => $reserva->data_reserva,
                        'hora_inicio' => $reserva->hora_inicio,
                        'hora_fim' => $reserva->hora_fim,
                        'motivo' => $reserva->motivo,
                        'status' => $reserva->status,
                        'observacoes' => $reserva->observacoes,
                        'data_solicitacao' => $reserva->data_solicitacao,
                        'data_aprovacao' => $reserva->data_aprovacao
                    ]]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Reserva não encontrada']);
                }
                break;
                
            case 'list_all':
                // Listar todas as reservas (apenas admin)
                if($_SESSION['user_tipo'] !== 'admin') {
                    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
                    exit();
                }
                
                $status_filtro = $_GET['status'] ?? '';
                
                // Query para buscar todas as reservas com informações do usuário
                $query = "SELECT r.*, u.nome as usuario_nome, u.email as usuario_email 
                          FROM reservas r 
                          INNER JOIN usuarios u ON r.usuario_id = u.id";
                
                // Adicionar filtro de status se fornecido
                if(!empty($status_filtro)) {
                    $query .= " WHERE r.status = :status";
                }
                
                $query .= " ORDER BY r.data_reserva DESC, r.hora_inicio DESC";
                
                $stmt = $db->prepare($query);
                
                if(!empty($status_filtro)) {
                    $stmt->bindParam(':status', $status_filtro);
                }
                
                $stmt->execute();
                $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'reservas' => $reservas]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        }
        
    } catch(Exception $e) {
        error_log("API Reservas GET - Erro: " . $e->getMessage());
        error_log("API Reservas GET - Stack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
