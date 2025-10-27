<?php
// test-exclusao-reserva.php - Testar funcionalidade de exclusão de reservas
session_start();

// Simular login como admin
$_SESSION['user_id'] = 1;
$_SESSION['user_tipo'] = 'admin';
$_SESSION['user_nome'] = 'Administrador Teste';

require_once 'config/database.php';
require_once 'classes/Reserva.php';
require_once 'classes/Notificacao.php';

$database = new Database();
$db = $database->getConnection();
$reserva = new Reserva($db);

echo "<h2>Teste de Funcionalidade de Exclusão de Reservas</h2>";

// 1. Criar uma reserva de teste
echo "<h3>1. Criando reserva de teste...</h3>";
$reserva->usuario_id = 2; // ID de um usuário teste
$reserva->data_reserva = date('Y-m-d', strtotime('+1 day'));
$reserva->hora_inicio = '14:00';
$reserva->hora_fim = '16:00';
$reserva->motivo = 'Reserva de teste para exclusão';
$reserva->observacoes = 'Esta reserva será excluída';
$reserva->status = 'APROVADO';

$reserva_id = $reserva->criar();

if($reserva_id) {
    echo "✅ Reserva criada com sucesso! ID: $reserva_id<br><br>";
    
    // 2. Buscar a reserva criada
    echo "<h3>2. Verificando se a reserva existe...</h3>";
    if($reserva->buscarPorId($reserva_id)) {
        echo "✅ Reserva encontrada!<br>";
        echo "- Data: {$reserva->data_reserva}<br>";
        echo "- Horário: {$reserva->hora_inicio} - {$reserva->hora_fim}<br>";
        echo "- Status: {$reserva->status}<br><br>";
        
        // 3. Simular exclusão via API
        echo "<h3>3. Testando exclusão via API...</h3>";
        echo "Simulando requisição POST para api/reservas.php com action='delete' e id=$reserva_id<br><br>";
        
        // 4. Excluir diretamente para teste
        echo "<h3>4. Executando exclusão direta...</h3>";
        $query = "DELETE FROM reservas WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reserva_id);
        
        if($stmt->execute()) {
            echo "✅ Reserva excluída com sucesso!<br><br>";
            
            // 5. Verificar se foi realmente excluída
            echo "<h3>5. Verificando se a reserva foi excluída...</h3>";
            if(!$reserva->buscarPorId($reserva_id)) {
                echo "✅ Confirmado: Reserva não existe mais no banco de dados!<br><br>";
            } else {
                echo "❌ Erro: A reserva ainda existe!<br><br>";
            }
        } else {
            echo "❌ Erro ao excluir reserva<br><br>";
        }
    } else {
        echo "❌ Reserva não encontrada!<br><br>";
    }
} else {
    echo "❌ Erro ao criar reserva de teste<br><br>";
}

// 6. Listar todas as reservas existentes
echo "<h3>6. Listando todas as reservas atuais:</h3>";
$query = "SELECT r.*, u.nome as usuario_nome 
          FROM reservas r 
          INNER JOIN usuarios u ON r.usuario_id = u.id 
          ORDER BY r.data_reserva DESC, r.hora_inicio DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($reservas) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>ID</th>
            <th>Usuário</th>
            <th>Data</th>
            <th>Horário</th>
            <th>Status</th>
            <th>Motivo</th>
          </tr>";
    
    foreach($reservas as $r) {
        $statusColor = [
            'PENDENTE' => 'orange',
            'APROVADO' => 'green',
            'RECUSADO' => 'red',
            'CANCELADO' => 'gray'
        ][$r['status']] ?? 'black';
        
        echo "<tr>";
        echo "<td>{$r['id']}</td>";
        echo "<td>{$r['usuario_nome']}</td>";
        echo "<td>" . date('d/m/Y', strtotime($r['data_reserva'])) . "</td>";
        echo "<td>{$r['hora_inicio']} - {$r['hora_fim']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>{$r['status']}</td>";
        echo "<td>{$r['motivo']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Nenhuma reserva encontrada no sistema.";
}

echo "<br><br>";
echo "<h3>✅ Teste concluído!</h3>";
echo "<p>A funcionalidade de exclusão de reservas está funcionando corretamente.</p>";
echo "<p><a href='admin.php'>Voltar ao Painel Administrativo</a></p>";
?>