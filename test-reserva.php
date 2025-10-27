<?php
// test-reserva.php - Teste manual para criação de reservas
session_start();

// Simular sessão de usuário para teste
$_SESSION['user_id'] = 1;
$_SESSION['user_nome'] = 'Usuário Teste';
$_SESSION['user_tipo'] = 'usuario';

echo "<h2>Teste de Criação de Reserva</h2>";

// Testar dados válidos
$test_data = [
    'action' => 'create',
    'data_reserva' => '2024-12-20',
    'hora_inicio' => '14:00',
    'hora_fim' => '16:00',
    'motivo' => 'Reunião de teste',
    'observacoes' => 'Teste manual do sistema'
];

echo "<h3>Dados de teste:</h3>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

// Simular requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capturar output
ob_start();

// Incluir a API
include 'api/reservas.php';

$output = ob_get_clean();

echo "<h3>Resposta da API:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Tentar decodificar JSON
$response = json_decode($output, true);
if ($response) {
    echo "<h3>Resposta decodificada:</h3>";
    echo "<pre>" . print_r($response, true) . "</pre>";
    
    if ($response['success']) {
        echo "<div style='color: green; font-weight: bold;'>✓ Teste PASSOU - Reserva criada com sucesso!</div>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>✗ Teste FALHOU - " . $response['message'] . "</div>";
    }
} else {
    echo "<div style='color: red; font-weight: bold;'>✗ Erro ao decodificar resposta JSON</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>Voltar ao sistema</a></p>";
?>
