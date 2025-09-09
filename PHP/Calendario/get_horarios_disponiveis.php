<?php
require '../conexao.php';

if (!isset($_GET['data'])) {
    echo json_encode([]);
    exit;
}

$data = $_GET['data'];

// Buscar horários de funcionamento da clínica
$horarios_clinica = [];
try {
    $stmt = $pdo->query("SELECT * FROM Dias_Trabalhados WHERE ativo = 1");
    $horarios_clinica = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode([]);
    exit;
}

// Converter para formato mais fácil de usar
$horarios_por_dia = [];
foreach ($horarios_clinica as $horario) {
    $horarios_por_dia[$horario['dia_semana']] = $horario;
}

// Função getHorariosDisponiveis (a mesma do código principal)
function getHorariosDisponiveis($data, $pdo, $horarios_por_dia) {
    // ... (mesma função do código acima)
}

$horarios = getHorariosDisponiveis($data, $pdo, $horarios_por_dia);
echo json_encode($horarios);
?>