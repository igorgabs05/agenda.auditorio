<?php
// test-reserva-completo.php - Teste completo do fluxo de criação de reserva
session_start();

echo "<h2>Teste Completo de Criação de Reserva</h2>";

// Simular sessão de usuário para teste
$_SESSION['user_id'] = 1;
$_SESSION['user_nome'] = 'Usuário Teste';
$_SESSION['user_tipo'] = 'usuario';

echo "<h3>1. Verificando conexão com banco de dados...</h3>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Erro ao conectar ao banco de dados");
    }
    
    echo "<div style='color: green;'>✓ Conexão com banco estabelecida</div>";
    
    // Verificar se as tabelas existem
    $tables = ['usuarios', 'reservas', 'notificacoes'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div style='color: green;'>✓ Tabela '$table' existe</div>";
        } else {
            echo "<div style='color: red;'>✗ Tabela '$table' não existe</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ Erro: " . $e->getMessage() . "</div>";
    exit;
}

echo "<h3>2. Testando classe Reserva...</h3>";

try {
    require_once 'classes/Reserva.php';
    $reserva = new Reserva($db);
    echo "<div style='color: green;'>✓ Classe Reserva carregada</div>";
    
    // Testar método verificarConflito
    $conflito = $reserva->verificarConflito('2024-12-20', '14:00', '16:00');
    echo "<div style='color: green;'>✓ Método verificarConflito executado</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ Erro na classe Reserva: " . $e->getMessage() . "</div>";
}

echo "<h3>3. Testando classe Notificacao...</h3>";

try {
    require_once 'classes/Notificacao.php';
    $notificacao = new Notificacao($db);
    echo "<div style='color: green;'>✓ Classe Notificacao carregada</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>✗ Erro na classe Notificacao: " . $e->getMessage() . "</div>";
}

echo "<h3>4. Testando criação de reserva...</h3>";

try {
    // Configurar dados da reserva
    $reserva->usuario_id = $_SESSION['user_id'];
    $reserva->data_reserva = '2024-12-20';
    $reserva->hora_inicio = '14:00';
    $reserva->hora_fim = '16:00';
    $reserva->motivo = 'Reunião de teste completo';
    $reserva->observacoes = 'Teste realizado via script completo';
    $reserva->status = 'PENDENTE';
    
    echo "<div style='color: blue;'>Dados configurados:</div>";
    echo "<ul>";
    echo "<li>Usuário ID: " . $reserva->usuario_id . "</li>";
    echo "<li>Data: " . $reserva->data_reserva . "</li>";
    echo "<li>Hora Início: " . $reserva->hora_inicio . "</li>";
    echo "<li>Hora Fim: " . $reserva->hora_fim . "</li>";
    echo "<li>Motivo: " . $reserva->motivo . "</li>";
    echo "<li>Status: " . $reserva->status . "</li>";
    echo "</ul>";
    
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
            echo "<h4>Dados da reserva criada:</h4>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> " . $reserva->id . "</li>";
            echo "<li><strong>Usuário ID:</strong> " . $reserva->usuario_id . "</li>";
            echo "<li><strong>Data:</strong> " . $reserva->data_reserva . "</li>";
            echo "<li><strong>Hora Início:</strong> " . $reserva->hora_inicio . "</li>";
            echo "<li><strong>Hora Fim:</strong> " . $reserva->hora_fim . "</li>";
            echo "<li><strong>Motivo:</strong> " . $reserva->motivo . "</li>";
            echo "<li><strong>Status:</strong> " . $reserva->status . "</li>";
            echo "<li><strong>Observações:</strong> " . $reserva->observacoes . "</li>";
            echo "<li><strong>Data Solicitação:</strong> " . $reserva->data_solicitacao . "</li>";
            echo "</ul>";
        }
        
        echo "<h3>5. Testando notificação...</h3>";
        
        // Tentar criar notificação
        $notificacao_result = $notificacao->notificarNovaSolicitacao($reserva_id, $_SESSION['user_nome']);
        
        if ($notificacao_result) {
            echo "<div style='color: green;'>✓ Notificação criada com sucesso</div>";
        } else {
            echo "<div style='color: orange;'>⚠ Notificação não foi criada (pode não haver admins)</div>";
        }
        
    } else {
        echo "<div style='color: red; font-weight: bold;'>✗ FALHA! Erro ao criar reserva</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>✗ ERRO: " . $e->getMessage() . "</div>";
    echo "<pre>Stack trace:\n" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>6. Verificando logs de erro...</h3>";
echo "<p>Verifique o arquivo de log do PHP para mais detalhes sobre possíveis erros.</p>";

echo "<hr>";
echo "<p><a href='index.php'>Voltar ao sistema</a> | <a href='test-reserva-simple.php'>Teste Simples</a></p>";
?>
