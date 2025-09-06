<?php
session_start();

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION["id"])) {
    header("Location: ../index.php");
    exit();
}

include '../conexao.php';

// ======= CADASTRO DE ANIMAL =======
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (código existente para cadastro de animal)
}

// ======= LISTAGEM DE ANIMAIS POR DONO =======
$animais = [];
// ... (código existente para listar animais)

// ======= BUSCAR AGENDAMENTOS DO USUÁRIO =======
$agendamentos = [];
try {
    $sql_agendamentos = "SELECT a.*, s.nome as servico_nome, p.nome as pet_nome 
                        FROM Agendamentos a 
                        JOIN Servicos s ON a.servico_id = s.id 
                        JOIN Animais p ON a.animal_id = p.id 
                        WHERE a.usuario_id = :usuario_id 
                        ORDER BY a.data_agendamento DESC 
                        LIMIT 5";
    $stmt_agendamentos = $pdo->prepare($sql_agendamentos);
    $stmt_agendamentos->execute(['usuario_id' => $_SESSION["id"]]);
    $agendamentos = $stmt_agendamentos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Não mostrar erro se a tabela não existir ainda
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
        :root {
            --primary: #4e9f3d;
            --primary-dark: #1e5128;
            --primary-light: #d8e9a8;
            --secondary: #191a19;
            --accent: #1e88e5;
            --accent-dark: #0d47a1;
            --accent-light: #90caf9;
            --light: #f5f5f5;
            --gray: #e0e0e0;
            --dark-gray: #757575;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.15);
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
            background: linear-gradient(135deg, #f9f9f9 0%, #eef5ed 100%);
            color: var(--secondary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar de Agendamentos */
        .agendamentos-sidebar {
            width: 320px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--accent-light) 100%);
            padding: 20px;
            border-right: 2px solid var(--primary);
            overflow-y: auto;
            box-shadow: var(--shadow);
        }

        .agendamentos-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
        }

        .agendamentos-header h2 {
            color: var(--primary-dark);
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .agendamentos-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .agendamento-card {
            background-color: var(--white);
            border-radius: var(--radius);
            padding: 15px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--accent);
        }

        .agendamento-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .agendamento-servico {
            font-weight: 700;
            color: var(--accent-dark);
            margin-bottom: 8px;
            font-size: 16px;
        }

        .agendamento-pet {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--primary-dark);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .agendamento-data {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--dark-gray);
            font-size: 14px;
        }

        .sem-agendamentos {
            text-align: center;
            padding: 20px;
            color: var(--dark-gray);
            font-style: italic;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            border: 2px dashed var(--gray);
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
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
            position: relative;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 4px solid var(--white);
        }

        .avatar:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-hover);
        }

        .profile-header h1 {
            color: var(--primary-dark);
            margin-bottom: 10px;
            font-size: 28px;
        }

        .profile-header p {
            color: var(--dark-gray);
            font-size: 16px;
        }

        /* Navigation */
        .profile-nav {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .profile-nav button {
            padding: 14px 28px;
            background-color: var(--white);
            border: 2px solid var(--primary);
            border-radius: 30px;
            color: var(--primary-dark);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .profile-nav button:hover {
            background-color: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .profile-nav button.active {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            box-shadow: var(--shadow);
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
            border: 1px solid var(--gray);
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
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
        }

        /* Profile Info */
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .info-item {
            background: linear-gradient(135deg, #f9fdf8 0%, #f0f7ee 100%);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .info-item strong {
            color: var(--primary-dark);
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-item strong i {
            font-size: 18px;
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
            border: 1px solid var(--gray);
        }

        .form-animal h2 {
            margin-top: 0;
        }

        .form-animal label {
            display: block;
            margin-top: 18px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 8px;
        }

        .form-animal input,
        .form-animal select,
        .form-animal textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--gray);
            border-radius: 8px;
            background-color: var(--light);
            transition: var(--transition);
            font-size: 16px;
        }

        .form-animal input:focus,
        .form-animal select:focus,
        .form-animal textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(78, 159, 61, 0.2);
        }

        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-upload-btn {
            background: linear-gradient(135deg, var(--primary-light), #cde8a0);
            color: var(--primary-dark);
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: var(--transition);
            justify-content: center;
            border: 1px dashed var(--primary);
        }

        .file-upload-btn:hover {
            background: linear-gradient(135deg, #cde8a0, var(--primary-light));
        }

        .file-upload input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .form-animal button {
            margin-top: 25px;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .form-animal button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        /* Pets List */
        .pets-list {
            margin-top: 20px;
        }

        .pet-card {
            background: linear-gradient(135deg, #f9fdf8 0%, #f0f7ee 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            position: relative;
        }

        .pet-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .pet-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light), #cde8a0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 28px;
            flex-shrink: 0;
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
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 8px;
            font-size: 18px;
        }

        .pet-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 14px;
            color: var(--dark-gray);
        }

        .pet-detail {
            display: flex;
            align-items: center;
            gap: 6px;
            background-color: rgba(255, 255, 255, 0.7);
            padding: 6px 12px;
            border-radius: 20px;
        }

        .pet-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-edit:hover {
            background: var(--accent-dark);
            transform: scale(1.1);
        }

        .no-pets {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark-gray);
            font-style: italic;
            background-color: var(--light);
            border-radius: 10px;
            border: 2px dashed var(--gray);
        }

        .no-pets i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Config Section */
        .config-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 45px;
            cursor: pointer;
            color: var(--dark-gray);
        }

        .btn-logout {
            background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
            margin-top: 30px;
        }

        .btn-logout:hover {
            background: linear-gradient(135deg, #c0392b, #a23526) !important;
        }

        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow);
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
        @media (max-width: 1024px) {
            body {
                flex-direction: column;
            }
            
            .agendamentos-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 2px solid var(--primary);
                max-height: 300px;
            }
        }

        @media (max-width: 768px) {
            .flex-container {
                flex-direction: column;
            }

            .profile-nav {
                flex-direction: column;
                align-items: stretch;
            }

            .profile-nav button {
                width: 100%;
                justify-content: center;
            }

            .pet-card {
                flex-direction: column;
                text-align: center;
            }

            .pet-details {
                justify-content: center;
            }
            
            .pet-actions {
                position: static;
                margin-top: 15px;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar de Agendamentos -->
    <div class="agendamentos-sidebar">
        <div class="agendamentos-header">
            <h2><i class="fas fa-calendar-alt"></i> Meus Agendamentos</h2>
        </div>
        
        <div class="agendamentos-list">
            <?php if (!empty($agendamentos)): ?>
                <?php foreach ($agendamentos as $agendamento): ?>
                    <div class="agendamento-card">
                        <div class="agendamento-servico"><?= htmlspecialchars($agendamento['servico_nome']) ?></div>
                        <div class="agendamento-pet">
                            <i class="fas fa-paw"></i>
                            <?= htmlspecialchars($agendamento['pet_nome']) ?>
                        </div>
                        <div class="agendamento-data">
                            <i class="fas fa-clock"></i>
                            <?= date('d/m/Y H:i', strtotime($agendamento['data_agendamento'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="sem-agendamentos">
                    <i class="fas fa-calendar-times"></i>
                    <p>Nenhum agendamento encontrado</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
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

                            <form action="" method="post" enctype="multipart/form-data">
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

                                <label for="datanasc">Data de Nascimento:</label>
                                <input type="date" name="datanasc" id="datanasc">

                                <label for="foto">Foto do Pet (opcional):</label>
                                <div class="file-upload">
                                    <div class="file-upload-btn">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        Escolher Foto
                                    </div>
                                    <input type="file" name="foto" id="foto" accept="image/*">
                                </div>

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

                            <div class="pets-list">
                                <?php if (!empty($animais)): ?>
                                    <?php foreach ($animais as $animal): ?>
                                        <div class="pet-card">
                                            <div class="pet-icon">
                                                <?php if (!empty($animal['foto'])): ?>
                                                    <img src="<?= htmlspecialchars($animal['foto']) ?>"
                                                        alt="<?= htmlspecialchars($animal['animal_nome']) ?>">
                                                <?php else: ?>
                                                    <?= $animal['especie'] == 'Cachorro' ? '🐶' :
                                                        ($animal['especie'] == 'Gato' ? '🐱' :
                                                            ($animal['especie'] == 'Hamster' ? '🐹' :
                                                                ($animal['especie'] == 'Peixe' ? '🐠' : '🐾')))
                                                    ?>
                                                <?php endif; ?>
                                            </div>

                                            <div class="pet-info">
                                                <div class="pet-name"><?= htmlspecialchars($animal['animal_nome']) ?></div>
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
                                                </div>
                                            </div>
                                            
                                            <div class="pet-actions">
                                                <button class="btn-edit" onclick="editarPet(<?= $animal['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-pets">
                                        <i class="fas fa-paw"></i>
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
                    <!-- Update Profile Info -->
                    <div class="form-animal" style="margin-bottom: 20px;">
                        <h3><i class="fas fa-user-edit"></i> Editar Informações Pessoais</h3>
                        <form action="alterar_perfil.php" method="post">
                            <div class="form-group">
                                <label for="nome_completo">Nome Completo:</label>
                                <input type="text" name="nome_completo" id="nome_completo" value="<?= $_SESSION["nome"] ?>"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="telefone">Telefone:</label>
                                <input type="text" name="telefone" id="telefone" value="<?= $_SESSION["telefone"] ?>"
                                    required>
                            </div>
                            <div class="form-group">
                                <label for="datanasc">Data de Nascimento:</label>
                                <input type="date" name="datanasc" id="datanasc" value="<?= $_SESSION["datanasc"] ?>">
                            </div>
                            <div class="form-group">
                                <label for="genero">Gênero:</label>
                                <select name="genero" id="genero">
                                    <option value="Masculino" <?= $_SESSION["genero"] == "Masculino" ? "selected" : "" ?>>
                                        Masculino</option>
                                    <option value="Feminino" <?= $_SESSION["genero"] == "Feminino" ? "selected" : "" ?>>
                                        Feminino</option>
                                    <option value="Outro" <?= $_SESSION["genero"] == "Outro" ? "selected" : "" ?>>Outro
                                    </option>
                                </select>
                            </div>
                            <button type="submit"><i class="fas fa-sync-alt"></i> Atualizar Perfil</button>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="form-animal" style="margin-bottom: 20px;">
                        <h3><i class="fas fa-lock"></i> Alterar Senha</h3>
                        <form action="alterar_senha.php" method="post">
                            <div class="form-group">
                                <label for="senha_atual">Senha Atual:</label>
                                <input type="password" name="senha_atual" id="senha_atual" required>
                                <span class="password-toggle" onclick="togglePassword('senha_atual')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="form-group">
                                <label for="nova_senha">Nova Senha:</label>
                                <input type="password" name="nova_senha" id="nova_senha" required>
                                <span class="password-toggle" onclick="togglePassword('nova_senha')">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="form-group">
                                <label for="confirmar_senha">Confirmar Nova Senha:</label>
                                <input type="password" name="confirmar_senha" id="confirmar_senha" required>
                                <span class="password-toggle" onclick="togglePassword('confirmar_senha')">
                                    <i class="fas fa-eye"></i>
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

        // Mostrar nome do arquivo selecionado
        document.getElementById('foto').addEventListener('change', function (e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.querySelector('.file-upload-btn').innerHTML =
                    `<i class="fas fa-file-image"></i> ${fileName}`;
            }
        });

        // Alternar visibilidade da senha
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Função para editar pet (a ser implementada)
        function editarPet(petId) {
            alert('Funcionalidade de edição para o pet com ID ' + petId + ' será implementada em breve!');
            // Aqui você pode redirecionar para uma página de edição ou abrir um modal
            // window.location.href = 'editar_pet.php?id=' + petId;
        }
    </script>

    <?php include '../menu.php'; ?>
</body>

</html>