<?php
session_start();

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

include '../conexao.php';

// ======= PROCESSAMENTO DE FORMULÁRIOS =======
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar qual formulário foi enviado
    if (isset($_POST['atualizar_perfil'])) {
        processarAtualizacaoPerfil($pdo);
    } 
    elseif (isset($_POST['redefinir_senha'])) {
        processarRedefinicaoSenha($pdo);
    } 
    else {
        processarCadastroAnimal($pdo);
    }
}

// ======= FUNÇÕES DE PROCESSAMENTO =======
function processarAtualizacaoPerfil($pdo) {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $datanasc = $_POST['datanasc'];
    $genero = $_POST['genero'];
    $usuario_id = $_SESSION["id"];

    try {
        $sql = "UPDATE Usuarios SET nome = :nome, telefone = :telefone, datanasc = :datanasc, genero = :genero WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':telefone' => $telefone,
            ':datanasc' => $datanasc,
            ':genero' => $genero,
            ':id' => $usuario_id
        ]);

        // Atualizar dados na sessão
        $_SESSION["nome"] = $nome;
        $_SESSION["telefone"] = $telefone;
        $_SESSION["datanasc"] = $datanasc;
        $_SESSION["genero"] = $genero;

        echo json_encode(['status' => 'success', 'message' => 'Perfil atualizado com sucesso!']);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar perfil: ' . $e->getMessage()]);
        exit();
    }
}

function processarRedefinicaoSenha($pdo) {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $usuario_id = $_SESSION["id"];

    // Verificar se as novas senhas coincidem
    if ($nova_senha !== $confirmar_senha) {
        echo json_encode(['status' => 'error', 'message' => 'As novas senhas não coincidem!']);
        exit();
    }

    try {
        // Buscar senha atual no banco
        $sql = "SELECT senha FROM Usuarios WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar se a senha atual está correta
        if (password_verify($senha_atual, $usuario['senha'])) {
            // Atualizar a senha
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            $sql_update = "UPDATE Usuarios SET senha = :senha WHERE id = :id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                ':senha' => $nova_senha_hash,
                ':id' => $usuario_id
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Senha redefinida com sucesso!']);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Senha atual incorreta!']);
            exit();
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao redefinir senha: ' . $e->getMessage()]);
        exit();
    }
}

function processarCadastroAnimal($pdo) {
    $nome = $_POST['nome'];
    $datanasc = !empty($_POST['datanasc']) ? $_POST['datanasc'] : null;
    $especie_nome = $_POST['especie'];
    $raca = !empty($_POST['raca']) ? $_POST['raca'] : null;
    $sexo = !empty($_POST['sexo']) ? $_POST['sexo'] : null;
    $porte = !empty($_POST['porte']) ? $_POST['porte'] : null;
    $usuario_id = $_SESSION["id"];
    
    // Processar upload da foto
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = uniqid() . '.' . $extensao;
        $pasta_destino = '../uploads/pets/';
        
        // Criar diretório se não existir
        if (!file_exists($pasta_destino)) {
            mkdir($pasta_destino, 0777, true);
        }
        
        $caminho_completo = $pasta_destino . $nome_arquivo;
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminho_completo)) {
            $foto = $nome_arquivo;
        }
    }

    try {
        // Primeiro busca o ID da espécie
        $stmt_especie = $pdo->prepare("SELECT id FROM Especies WHERE nome = :nome");
        $stmt_especie->execute([':nome' => $especie_nome]);
        $especie = $stmt_especie->fetch(PDO::FETCH_ASSOC);

        if (!$especie) {
            echo json_encode(['status' => 'error', 'message' => 'Espécie não encontrada!']);
            exit();
        }

        $especie_id = $especie['id'];

        // Agora insere o animal com o especie_id correto
        $sql = "INSERT INTO Animais (nome, datanasc, especie_id, raca, porte, sexo, usuario_id, foto)
                VALUES (:nome, :datanasc, :especie_id, :raca, :porte, :sexo, :usuario_id, :foto)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':datanasc' => $datanasc,
            ':especie_id' => $especie_id,
            ':raca' => $raca,
            ':porte' => $porte,
            ':sexo' => $sexo,
            ':usuario_id' => $usuario_id,
            ':foto' => $foto
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Animal cadastrado com sucesso!']);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao cadastrar animal: ' . $e->getMessage()]);
        exit();
    }
}

// ======= CARREGAMENTO DE DADOS =======
// Buscar informações atualizadas do usuário
try {
    $sql = "SELECT nome, telefone, email, datanasc, genero FROM Usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $_SESSION["id"]]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Não encerrar o script, apenas definir variável vazia
    $usuario = [];
}

// Listagem de animais por dono
$animais = [];
try {
    $sql = "SELECT 
                a.nome AS animal_nome, 
                e.nome AS especie, 
                a.raca, 
                a.sexo, 
                a.porte,
                a.foto,
                a.datanasc
            FROM Animais a
            JOIN Especies e ON a.especie_id = e.id
            WHERE a.usuario_id = :usuario_id
            ORDER BY a.nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario_id' => $_SESSION["id"]]);
    $animais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Não encerrar o script, apenas definir array vazio
    $animais = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - PetShop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Seus estilos CSS anteriores aqui */
        :root {
            --primary: #4e9f3d;
            --primary-dark: #1e5128;
            --primary-light: #d8e9a8;
            --secondary: #191a19;
            --light: #f5f5f5;
            --gray: #e0e0e0;
            --dark-gray: #757575;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: var(--secondary);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            text-align: center;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 15px;
            box-shadow: var(--shadow);
        }

        .profile-header h1 {
            color: var(--primary-dark);
            margin-bottom: 10px;
        }

        /* Navigation */
        .profile-nav {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .profile-nav button {
            padding: 12px 24px;
            background-color: var(--white);
            border: 2px solid var(--primary);
            border-radius: 30px;
            color: var(--primary-dark);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
        }

        .profile-nav button:hover {
            background-color: var(--primary-light);
        }

        .profile-nav button.active {
            background-color: var(--primary);
            color: white;
        }

        /* Content Sections */
        .content-section {
            display: none;
            background-color: var(--white);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-section.active {
            display: block;
        }

        .content-section h2 {
            color: var(--primary-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray);
        }

        /* Profile Info */
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            background-color: var(--light);
            padding: 15px;
            border-radius: 8px;
        }

        .info-item strong {
            color: var(--primary-dark);
            display: block;
            margin-bottom: 5px;
        }

        /* Animal Form */
        .flex-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .form-container,
        .pets-container {
            flex: 1;
            min-width: 300px;
        }

        .form-animal {
            background-color: var(--white);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .form-animal label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: var(--secondary);
        }

        .form-animal input,
        .form-animal select {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            border: 1px solid var(--gray);
            border-radius: 6px;
            background-color: var(--light);
            transition: var(--transition);
        }

        .form-animal input:focus,
        .form-animal select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        .form-animal button {
            margin-top: 20px;
            padding: 12px;
            background-color: var(--primary);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
        }

        .form-animal button:hover {
            background-color: var(--primary-dark);
        }

        /* Pets List */
        .pets-list {
            margin-top: 20px;
        }

        .pet-card {
            background-color: var(--light);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
        }

        .pet-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .pet-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 20px;
            overflow: hidden;
        }
        
        .pet-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pet-info {
            flex: 1;
        }

        .pet-name {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }

        .pet-details {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 14px;
            color: var(--dark-gray);
        }

        .pet-detail {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-pets {
            text-align: center;
            padding: 30px;
            color: var(--dark-gray);
            font-style: italic;
        }

        /* Config Section */
        .config-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 42px;
            cursor: pointer;
            color: var(--dark-gray);
        }

        .btn-logout {
            background-color: #e74c3c !important;
            margin-top: 30px;
        }

        .btn-logout:hover {
            background-color: #c0392b !important;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            display: none;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .flex-container {
                flex-direction: column;
            }

            .profile-nav {
                flex-direction: column;
                align-items: center;
            }

            .profile-nav button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Alertas dinâmicos -->
        <div id="alertBox" class="alert" style="display: none;"></div>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="avatar">
                <?php
                $nome_completo = $_SESSION["nome"];
                $partes = explode(' ', trim($nome_completo));

                $inicial_nome = strtoupper(mb_substr($partes[0], 0, 1));
                $inicial_sobrenome = isset($partes[1]) ? strtoupper(mb_substr($partes[1], 0, 1)) : '';

                echo $inicial_nome . $inicial_sobrenome;
                ?>
            </div>
            <h1>Olá, <?= explode(' ', $_SESSION["nome"])[0] ?>!</h1>
            <p>Bem-vindo ao seu perfil PetShop</p>
        </div>

        <!-- Navigation -->
        <div class="profile-nav">
            <button class="active" onclick="showSection('perfil')">
                <i class="fas fa-user"></i> Perfil
            </button>
            <button onclick="showSection('pets')">
                <i class="fas fa-paw"></i> Meus Pets
            </button>
            <button onclick="showSection('config')">
                <i class="fas fa-cog"></i> Configurações
            </button>
        </div>

        <!-- Profile Section -->
        <div id="perfil" class="content-section active">
            <h2><i class="fas fa-id-card"></i> Informações do Perfil</h2>
            <div class="profile-info">
                <div class="info-item">
                    <strong><i class="fas fa-signature"></i> Nome Completo</strong>
                    <?= $_SESSION["nome"] ?>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-phone"></i> Telefone</strong>
                    <?= $_SESSION["telefone"] ?>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-envelope"></i> E-mail</strong>
                    <?= $_SESSION["email"] ?>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-birthday-cake"></i> Data de Nascimento</strong>
                    <?= $_SESSION["datanasc"] ?>
                </div>
                <div class="info-item">
                    <strong><i class="fas fa-venus-mars"></i> Gênero</strong>
                    <?= $_SESSION["genero"] ?>
                </div>
            </div>
        </div>

        <!-- Pets Section -->
        <div id="pets" class="content-section">
            <div class="flex-container">
                <!-- Add Pet Form -->
                <div class="form-container">
                    <div class="form-animal">
                        <h2><i class="fas fa-plus-circle"></i> Cadastrar Novo Pet</h2>
                        <p>Preencha os dados do seu animal de estimação.</p>

                        <form id="formPet" enctype="multipart/form-data">
                            <label for="nome">Nome do Pet:</label>
                            <input type="text" name="nome" id="nome" required placeholder="Ex: Thor">

                            <label for="especie">Espécie:</label>
                            <select name="especie" id="especie" required>
                                <option value="">Selecione</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT DISTINCT nome FROM Especies ORDER BY nome ASC");
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='" . htmlspecialchars($row['nome']) . "'>" . htmlspecialchars($row['nome']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option disabled>Erro ao carregar espécies</option>";
                                }
                                ?>
                            </select>

                            <label for="raca">Raça:</label>
                            <input type="text" name="raca" id="raca" placeholder="Ex: Golden Retriever">

                            <label for="datanasc">Data Estimada de Nascimento:</label>
                            <input type="date" name="datanasc" id="datanasc">
                            
                            <label for="foto">Foto do Pet (opcional):</label>
                            <input type="file" name="foto" id="foto" accept="image/*">

                            <div class="flex-container" style="gap: 10px; margin-top: 15px;">
                                <div style="flex: 1;">
                                    <label for="sexo">Sexo:</label>
                                    <select name="sexo" id="sexo">
                                        <option value="">Não definido</option>
                                        <option value="Macho">Macho</option>
                                        <option value="Fêmea">Fêmea</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <label for="porte">Porte:</label>
                                    <select name="porte" id="porte">
                                        <option value="">Não definido</option>
                                        <option value="Pequeno">Pequeno</option>
                                        <option value="Medio">Médio</option>
                                        <option value="Grande">Grande</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit"><i class="fas fa-save"></i> Cadastrar Pet</button>
                        </form>
                    </div>
                </div>

                <!-- Pets List -->
                <div class="pets-container">
                    <div class="form-animal">
                        <h2><i class="fas fa-paw"></i> Meus Pets</h2>

                        <div class="pets-list" id="petsList">
                            <?php if (!empty($animais)): ?>
                                <?php foreach ($animais as $animal): ?>
                                    <div class="pet-card">
                                        <div class="pet-icon">
                                            <?php if (!empty($animal['foto'])): ?>
                                                <img src="../uploads/pets/<?= htmlspecialchars($animal['foto']) ?>" alt="<?= htmlspecialchars($animal['animal_nome']) ?>">
                                            <?php else: ?>
                                                <?= $animal['especie'] == 'Cachorro' ? '🐶' :
                                                    ($animal['especie'] == 'Gato' ? '🐱' :
                                                        ($animal['especie'] == 'Hamster' ? '🐹' :
                                                            ($animal['especie'] == 'Peixe' ? '🐠' : '🐾'))) 
                                                ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="pet-info">
                                            <div class="pet-name"> <?= htmlspecialchars($animal['animal_nome']) ?> </div>
                                            <div class="pet-details">
                                                <span class="pet-detail">
                                                    <i class="fas fa-dog"></i> <?= htmlspecialchars($animal['especie']) ?>
                                                </span>
                                                <?php if ($animal['raca']): ?>
                                                    <span class="pet-detail">
                                                        <i class="fas fa-dna"></i> <?= htmlspecialchars($animal['raca']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($animal['sexo']): ?>
                                                    <span class="pet-detail">
                                                        <i class="fas fa-venus-mars"></i> <?= htmlspecialchars($animal['sexo']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($animal['porte']): ?>
                                                    <span class="pet-detail">
                                                        <i class="fas fa-weight-hanging"></i>
                                                        <?= htmlspecialchars($animal['porte']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($animal['datanasc']): ?>
                                                    <span class="pet-detail">
                                                        <i class="fas fa-birthday-cake"></i>
                                                        <?= date('d/m/Y', strtotime($animal['datanasc'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-pets">
                                    <i class="fas fa-paw" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;"></i>
                                    <p>Você ainda não cadastrou nenhum pet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Config Section -->
        <div id="config" class="content-section">
            <h2><i class="fas fa-cog"></i> Configurações da Conta</h2>

            <div class="config-form">
                <!-- Editar Perfil -->
                <div class="form-animal" style="margin-bottom: 20px;">
                    <h3><i class="fas fa-user-edit"></i> Editar Perfil</h3>
                    <form id="formPerfil">
                        <input type="hidden" name="atualizar_perfil" value="1">
                        
                        <div class="form-group">
                            <label for="edit_nome">Nome Completo:</label>
                            <input type="text" name="nome" id="edit_nome" required value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_telefone">Telefone:</label>
                            <input type="text" name="telefone" id="edit_telefone" required value="<?= htmlspecialchars($usuario['telefone'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_email">E-mail:</label>
                            <input type="email" name="email" id="edit_email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" disabled>
                            <small style="color: var(--dark-gray);">O e-mail não pode ser alterado</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_datanasc">Data de Nascimento:</label>
                            <input type="date" name="datanasc" id="edit_datanasc" value="<?= htmlspecialchars($usuario['datanasc'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_genero">Gênero:</label>
                            <select name="genero" id="edit_genero">
                                <option value="">Selecione</option>
                                <option value="Masculino" <?= ($usuario['genero'] ?? '') == 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                                <option value="Feminino" <?= ($usuario['genero'] ?? '') == 'Feminino' ? 'selected' : '' ?>>Feminino</option>
                                <option value="Outro" <?= ($usuario['genero'] ?? '') == 'Outro' ? 'selected' : '' ?>>Outro</option>
                                <option value="Prefiro não informar" <?= ($usuario['genero'] ?? '') == 'Prefiro não informar' ? 'selected' : '' ?>>Prefiro não informar</option>
                            </select>
                        </div>
                        
                        <button type="submit"><i class="fas fa-save"></i> Salvar Alterações</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="form-animal" style="margin-bottom: 20px;">
                    <h3><i class="fas fa-lock"></i> Alterar Senha</h3>
                    <form id="formSenha">
                        <input type="hidden" name="redefinir_senha" value="1">
                        
                        <div class="form-group">
                            <label for="senha_atual">Senha Atual:</label>
                            <input type="password" name="senha_atual" id="senha_atual" required>
                            <span class="password-toggle" onclick="togglePassword('senha_atual', this)">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        
                        <div class="form-group">
                            <label for="nova_senha">Nova Senha:</label>
                            <input type="password" name="nova_senha" id="nova_senha" required>
                            <span class="password-toggle" onclick="togglePassword('nova_senha', this)">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmar_senha">Confirmar Nova Senha:</label>
                            <input type="password" name="confirmar_senha" id="confirmar_senha" required>
                            <span class="password-toggle" onclick="togglePassword('confirmar_senha', this)">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        
                        <button type="submit"><i class="fas fa-key"></i> Alterar Senha</button>
                    </form>
                </div>

                <!-- Logout -->
                <form action="../logout.php" method="post">
                    <button type="submit" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair da Conta</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionId).classList.add('active');

            // Update active button
            document.querySelectorAll('.profile-nav button').forEach(button => {
                button.classList.remove('active');
            });

            event.currentTarget.classList.add('active');
        }
        
        function togglePassword(inputId, element) {
            const input = document.getElementById(inputId);
            const icon = element.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
        
        function showAlert(message, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.textContent = message;
            alertBox.className = 'alert ' + type;
            alertBox.style.display = 'block';
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 5000);
        }
        
        // AJAX para formulário de perfil
        document.getElementById('formPerfil').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert(data.message, 'success');
                    // Atualizar dados na página sem reload
                    document.querySelectorAll('.info-item')[0].innerHTML = '<strong><i class="fas fa-signature"></i> Nome Completo</strong> ' + formData.get('nome');
                    document.querySelectorAll('.info-item')[1].innerHTML = '<strong><i class="fas fa-phone"></i> Telefone</strong> ' + formData.get('telefone');
                    document.querySelectorAll('.info-item')[3].innerHTML = '<strong><i class="fas fa-birthday-cake"></i> Data de Nascimento</strong> ' + formData.get('datanasc');
                    document.querySelectorAll('.info-item')[4].innerHTML = '<strong><i class="fas fa-venus-mars"></i> Gênero</strong> ' + formData.get('genero');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Erro ao processar a requisição', 'error');
            });
        });
        
        // AJAX para formulário de senha
        document.getElementById('formSenha').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert(data.message, 'success');
                    this.reset();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Erro ao processar a requisição', 'error');
            });
        });
        
        // AJAX para formulário de pet
        document.getElementById('formPet').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert(data.message, 'success');
                    this.reset();
                    // Recarregar a lista de pets via AJAX
                    loadPets();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Erro ao processar a requisição', 'error');
            });
        });
        
        function loadPets() {
            // Esta função precisaria ser implementada para buscar os pets via AJAX
            // Por enquanto, vamos recarregar a página para ver os novos pets
            // Em uma implementação mais avançada, você poderia adicionar o novo pet dinamicamente
            location.reload();
        }
    </script>

    <?php include '../menu.php'; ?>
</body>
</html>