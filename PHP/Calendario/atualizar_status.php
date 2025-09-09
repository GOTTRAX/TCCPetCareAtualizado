<?php
session_start();
require '../conexao.php';

if (!isset($_SESSION['id']) || ($_SESSION['tipo_usuario'] !== 'Veterinario' && $_SESSION['tipo_usuario'] !== 'Secretaria')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE Agendamentos SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        echo json_encode(['status' => 'ok']);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'erro' => 'Erro ao atualizar status']);
    }
}