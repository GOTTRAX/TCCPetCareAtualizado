<?php
session_start();
require '../conexao.php';

if (!isset($_SESSION['id']) || ($_SESSION['tipo_usuario'] !== 'Veterinario' && $_SESSION['tipo_usuario'] !== 'Secretaria')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

// Buscar solicitações pendentes
$stmt = $pdo->prepare("
    SELECT a.*, an.nome as animal_nome, s.nome as servico_nome, u.nome as cliente_nome
    FROM Agendamentos a
    INNER JOIN Animais an ON a.animal_id = an.id
    INNER JOIN Servicos s ON a.servico_id = s.id
    INNER JOIN Usuarios u ON a.cliente_id = u.id
    WHERE a.status = 'pendente'
    ORDER BY a.data_hora, a.hora_inicio
");

$stmt->execute();
$solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($solicitacoes);