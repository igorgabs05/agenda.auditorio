<?php
// test-reserva-simple.php - Teste simples de criação de reserva
session_start();

// Simular sessão de usuário para teste
$_SESSION['user_id'] = 1;
$_SESSION['user_nome'] = 'Usuário Teste';
$_SESSION['user_tipo'] = 'usuario';

echo "<h2>Teste Simples de Criação de Reserva</h2>";

try {
    // Incluir dependências
    require_once 'config/database.php';
    require_once 'classes/Reserva.php';
    require_once 'classes/Notificacao.php';
    
    // Conectar ao banco
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Erro ao conectar ao banco de dados");
    }
    
    echo "<div style='color: green;'>✓ Conexão com banco estabelecida</div>";
    
    // Criar instância da reserva
    $reserva = new Reserva($db);
    
    // Configurar dados da reserva
    $reserva->usuario_id = $_SESSION['user_id'];
    $reserva->data_reserva = '2024-12-20';
    $reserva->hora_inicio = '14:00';
    $reserva->hora_fim = '16:00';
    $reserva->motivo = 'Reunião de teste manual';
    $reserva->observacoes = 'Teste realizado via script manual';
    $reserva->status = 'PENDENTE';
    
    echo "<div style='color: green;'>✓ Dados da reserva configurados</div>";
    
    // Verificar conflitos
    $conflito = $reserva->verificarConflito($reserva->data_reserva, $reserva->hora_inicio, $reserva->hora_fim);
    
    if ($conflito) {
        echo "<div style='color: orange;'>⚠ Conflito detectado - já existe reserva neste horário</div>";
    } else {
        echo "<div style='color: green;'>✓ Nenhum conflito detectado</div>";
    }
    
    // Tentar criar a reserva
    $reserva_id = $reserva->criar();
    
    if ($reserva_id) {
        echo "<div style='color: green; font-weight: bold;'>✓ SUCESSO! Reserva criada com ID: " . $reserva_id . "</div>";
        
        // Buscar a reserva criada para confirmar
        if ($reserva->buscarPorId($reserva_id)) {
            echo "<h3>Dados da reserva criada:</h3>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> " . $reserva->id . "</li>";
            echo "<li><strong>Usuário ID:</strong> " . $reserva->usuario_id . "</li>";
            echo "<li><strong>Data:</strong> " . $reserva->data_reserva . "</li>";
            echo "<li><strong>Hora Início:</strong> " . $reserva->hora_inicio . "</li>";
            echo "<li><strong>Hora Fim:</strong> " . $reserva->hora_fim . "</li>";
            echo "<li><strong>Motivo:</strong> " . $reserva->motivo . "</li>";
            echo "<li><strong>Status:</strong> " . $reserva->status . "</li>";
            echo "<li><strong>Observações:</strong> " . $reserva->observacoes . "</li>";
            echo "</ul>";
        }
    } else {
        echo "<div style='color: red; font-weight: bold;'>✗ FALHA! Erro ao criar reserva</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>✗ ERRO: " . $e->getMessage() . "</div>";
    echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='index.php'>Voltar ao sistema</a></p>";
?>
