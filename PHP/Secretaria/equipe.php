<?php
include '../conexao.php'; 
include 'header.php';

// ====== CREATE ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $nome = $_POST['nome'];
    $profissao = $_POST['profissao'];
    $descricao = $_POST['descricao'];

    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "../../assets/uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $foto = time() . "_" . basename($_FILES["foto"]["name"]);
        $targetFile = $targetDir . $foto;
        move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFile);
    }

    $sql = "INSERT INTO equipe (nome, usuario_id, profissao, descricao, foto) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nome, $_SESSION['id'], $profissao, $descricao, $foto]);
    header("Location: equipe.php");
    exit;
}

// ====== DELETE ======
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM equipe WHERE id=?")->execute([$id]);
    header("Location: equipe.php");
    exit;
}

// ====== READ ======
$sql = "SELECT id, nome, profissao, descricao, foto FROM equipe";
$stmt = $pdo->query($sql);
$equipe = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .team-container { padding: 20px; }
        .team-row { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .team-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 300px;
            transition: transform 0.2s ease;
        }
        .team-card:hover { transform: translateY(-8px); }
        .team-img { width: 100%; height: 250px; object-fit: cover; }
        .team-body { padding: 20px; text-align: center; }
        .team-title { font-size: 20px; margin-bottom: 5px; }
        .team-subtitle { font-size: 14px; color: #777; margin-bottom: 10px; }
        .team-text { font-size: 14px; color: #555; margin-bottom: 15px; }
        .team-social-icons a {
            color: #3498db;
            margin: 0 8px;
            font-size: 18px;
            transition: color 0.2s;
        }
        .team-social-icons a:hover { color: #2c3e50; }
        .crud-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .crud-form input, .crud-form textarea, .crud-form button {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .crud-form button {
            background: #3498db;
            color: #fff;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        .crud-form button:hover { background: #2980b9; }
        .delete-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            margin-top: 10px;
            border-radius: 8px;
            cursor: pointer;
        }
        .delete-btn:hover { background: #c0392b; }
    </style>
</head>
<body>
    <div class="team-container">
        <h1 class="text-center">Nossa Equipe</h1>

        <!-- CRUD FORM CREATE -->
        <div class="crud-form">
            <h2>Adicionar Membro</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <input type="text" name="nome" placeholder="Nome" required>
                <input type="text" name="profissao" placeholder="Profissão" required>
                <textarea name="descricao" placeholder="Descrição"></textarea>
                <input type="file" name="foto" accept="image/*">
                <button type="submit">Salvar</button>
            </form>
        </div>

        <!-- LIST TEAM -->
        <div class="team-row">
            <?php if (count($equipe) > 0): ?>
                <?php foreach ($equipe as $row): ?>
                    <div class="team-card">
                        <img src="../../assets/uploads/<?= htmlspecialchars($row['foto']) ?>" class="team-img" alt="Foto de <?= htmlspecialchars($row['nome']) ?>">
                        <div class="team-body">
                            <h5 class="team-title"><?= htmlspecialchars($row['nome']) ?></h5>
                            <p class="team-subtitle"><?= htmlspecialchars($row['profissao']) ?></p>
                            <p class="team-text"><?= htmlspecialchars($row['descricao']) ?></p>
                            <a href="?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Tem certeza que deseja excluir este membro?')">Excluir</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">Nenhum membro cadastrado ainda.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
