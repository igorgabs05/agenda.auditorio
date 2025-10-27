<?php
// test-api.php - Arquivo para testar as APIs
session_start();

// Simular login de usuário para teste
$_SESSION['user_id'] = 1;
$_SESSION['user_nome'] = 'Usuário Teste';
$_SESSION['user_email'] = 'teste@teste.com';
$_SESSION['user_tipo'] = 'usuario';

echo "<h2>Teste das APIs</h2>";

echo "<h3>1. Teste de Conexão com Banco</h3>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Conexão com banco OK<br>";
} catch(Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
}

echo "<h3>2. Teste de Horários Ocupados</h3>";
echo "<a href='api/horarios.php?data=2024-01-15' target='_blank'>Testar API de Horários</a><br>";

echo "<h3>3. Teste de Calendário</h3>";
echo "<a href='api/calendario.php' target='_blank'>Testar API de Calendário</a><br>";

echo "<h3>4. Teste de Notificações</h3>";
echo "<a href='api/notificacoes.php?action=list' target='_blank'>Testar API de Notificações</a><br>";

echo "<h3>5. Teste de Reservas</h3>";
echo "<a href='api/reservas.php?action=get&id=1' target='_blank'>Testar API de Reservas</a><br>";

echo "<h3>6. Páginas Principais</h3>";
echo "<a href='login.php'>Login</a> | ";
echo "<a href='index.php'>Página Principal</a> | ";
echo "<a href='admin.php'>Admin</a><br>";

echo "<h3>7. Verificar Logs de Erro</h3>";
echo "Verifique os logs do PHP para erros detalhados.<br>";
echo "No Windows: C:\\xampp\\apache\\logs\\error.log<br>";
echo "No Linux: /var/log/apache2/error.log<br>";
?>
