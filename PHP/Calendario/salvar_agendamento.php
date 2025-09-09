<?php
session_start();
require '../conexao.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Cliente') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_SESSION['id'];
    $animal_id = $_POST['animal_id'];
    $servico_id = $_POST['servico_id'];
    $data_hora = $_POST['data_hora'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_final = $_POST['hora_final'];
    $observacoes = $_POST['observacoes'] ?? '';
    
    try {
        // Inserir como pendente (status = 'pendente')
        $stmt = $pdo->prepare("INSERT INTO Agendamentos 
                              (cliente_id, animal_id, servico_id, data_hora, hora_inicio, hora_final, observacoes, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')");
        
        $stmt->execute([$cliente_id, $animal_id, $servico_id, $data_hora, $hora_inicio, $hora_final, $observacoes]);
        
        header("Location: calendario.php?success=1");
        exit;
        
    } catch (PDOException $e) {
        header("Location: calendario.php?error=1");
        exit;
    }
} else {
    header("Location: calendario.php");
    exit;
}