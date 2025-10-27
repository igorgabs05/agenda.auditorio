<?php
// api/estatisticas.php - API para dados estatísticos do sistema
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar se usuário está logado e é admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores.']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'usuarios_novos':
            // Usuários cadastrados por mês nos últimos 12 meses
            $query = "SELECT 
                        DATE_FORMAT(data_cadastro, '%Y-%m') as mes,
                        COUNT(*) as total
                      FROM usuarios 
                      WHERE data_cadastro >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        AND tipo = 'usuario'
                      GROUP BY DATE_FORMAT(data_cadastro, '%Y-%m')
                      ORDER BY mes ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'dados' => $dados]);
            break;
            
        case 'reservas_mes':
            // Reservas por dia do mês atual
            $query = "SELECT 
                        DATE_FORMAT(data_reserva, '%d/%m') as dia,
                        COUNT(*) as total
                      FROM reservas 
                      WHERE YEAR(data_reserva) = YEAR(CURRENT_DATE())
                        AND MONTH(data_reserva) = MONTH(CURRENT_DATE())
                      GROUP BY data_reserva
                      ORDER BY data_reserva ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'dados' => $dados]);
            break;
            
        case 'reservas_ano':
            // Reservas por mês do ano atual
            $query = "SELECT 
                        DATE_FORMAT(data_reserva, '%m') as mes,
                        COUNT(*) as total
                      FROM reservas 
                      WHERE YEAR(data_reserva) = YEAR(CURRENT_DATE())
                      GROUP BY DATE_FORMAT(data_reserva, '%m')
                      ORDER BY mes ASC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Preencher meses faltantes com 0
            $meses = ['01'=>0, '02'=>0, '03'=>0, '04'=>0, '05'=>0, '06'=>0, 
                      '07'=>0, '08'=>0, '09'=>0, '10'=>0, '11'=>0, '12'=>0];
            
            foreach($dados as $row) {
                $meses[$row['mes']] = (int)$row['total'];
            }
            
            $resultado = [];
            $nomeMeses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 
                          'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            
            $i = 0;
            foreach($meses as $mes => $total) {
                $resultado[] = [
                    'mes' => $nomeMeses[$i],
                    'total' => $total
                ];
                $i++;
            }
            
            echo json_encode(['success' => true, 'dados' => $resultado]);
            break;
            
        case 'status_reservas':
            // Distribuição de reservas por status no ano atual
            $query = "SELECT 
                        status,
                        COUNT(*) as total
                      FROM reservas 
                      WHERE YEAR(data_reserva) = YEAR(CURRENT_DATE())
                      GROUP BY status";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'dados' => $dados]);
            break;
            
        case 'usuarios_ativos':
            // Total de usuários ativos vs inativos
            $query = "SELECT 
                        CASE WHEN ativo = 1 THEN 'Ativo' ELSE 'Inativo' END as status,
                        COUNT(*) as total
                      FROM usuarios 
                      WHERE tipo = 'usuario'
                      GROUP BY ativo";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'dados' => $dados]);
            break;
            
        case 'reservas_usuario':
            // Top 10 usuários com mais reservas aprovadas
            $query = "SELECT 
                        u.nome,
                        COUNT(*) as total
                      FROM reservas r
                      JOIN usuarios u ON r.usuario_id = u.id
                      WHERE r.status = 'APROVADO'
                        AND YEAR(r.data_reserva) = YEAR(CURRENT_DATE())
                      GROUP BY r.usuario_id, u.nome
                      ORDER BY total DESC
                      LIMIT 10";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'dados' => $dados]);
            break;
            
        case 'resumo':
            // Resumo geral do sistema
            $resumo = [];
            
            // Total de usuários
            $query = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'usuario' AND ativo = 1";
            $stmt = $db->query($query);
            $resumo['total_usuarios'] = $stmt->fetch()['total'];
            
            // Total de reservas este mês
            $query = "SELECT COUNT(*) as total FROM reservas 
                      WHERE MONTH(data_reserva) = MONTH(CURRENT_DATE()) 
                      AND YEAR(data_reserva) = YEAR(CURRENT_DATE())";
            $stmt = $db->query($query);
            $resumo['reservas_mes'] = $stmt->fetch()['total'];
            
            // Total de reservas este ano
            $query = "SELECT COUNT(*) as total FROM reservas 
                      WHERE YEAR(data_reserva) = YEAR(CURRENT_DATE())";
            $stmt = $db->query($query);
            $resumo['reservas_ano'] = $stmt->fetch()['total'];
            
            // Pendentes
            $query = "SELECT COUNT(*) as total FROM reservas WHERE status = 'PENDENTE'";
            $stmt = $db->query($query);
            $resumo['pendentes'] = $stmt->fetch()['total'];
            
            // Taxa de aprovação
            $query = "SELECT 
                        SUM(CASE WHEN status = 'APROVADO' THEN 1 ELSE 0 END) as aprovados,
                        COUNT(*) as total
                      FROM reservas 
                      WHERE status IN ('APROVADO', 'RECUSADO')
                        AND YEAR(data_reserva) = YEAR(CURRENT_DATE())";
            $stmt = $db->query($query);
            $row = $stmt->fetch();
            $resumo['taxa_aprovacao'] = $row['total'] > 0 ? round(($row['aprovados'] / $row['total']) * 100, 1) : 0;
            
            echo json_encode(['success' => true, 'dados' => $resumo]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao buscar estatísticas: ' . $e->getMessage()
    ]);
}
?>
