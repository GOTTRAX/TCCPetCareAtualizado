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
    <meta name="description" content="PetCare - Perfil do Cliente">
    <meta name="keywords" content="petcare, perfil, cliente, veterinária">
    <meta name="author" content="PetCare">
    <title>Meu Perfil - PetCare</title>
    
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios/452/cat.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        :root {
            --primary-color: #2E8B57;
            --primary-dark: #1F5F3F;
            --secondary-color: #c6c8c8;
            --text-dark: #2C3E50;
            --text-light: #7F8C8D;
            --background-light: #F8F9FA;
            --white: #FFFFFF;
            --accent-color: #FF6B6B;
            --border-radius: 12px;
            --shadow-light: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        body {
            background: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .profile-container {
            max-width: 1000px;
            margin: 80px auto 40px;
            padding: 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-medium);
        }

        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
        }

        .photo-section {
            text-align: center;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin-bottom: 1rem;
        }

        .photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            border: 4px solid var(--primary-color);
        }

        .upload-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 0.5rem;
        }

        .upload-btn:hover {
            background: var(--primary-dark);
        }

        .info-section h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--secondary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .readonly-input {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <?php include '../menu.php'; ?>
    
    <div class="profile-container">
        <div class="profile-header">
            <h1>Meu Perfil</h1>
            <p>Gerencie suas informações e preferências</p>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert <?php echo strpos($mensagem, 'Erro') !== false ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
        <?php endif; ?>

        <form action="perfil.php" method="POST" enctype="multipart/form-data" class="profile-content">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="photo-section">
                <?php if ($fotoPerfil): ?>
                    <img src="../../uploads/perfil/<?php echo htmlspecialchars($fotoPerfil); ?>" 
                         alt="Foto de perfil" class="profile-photo" 
                         onerror="this.style.display='none'; document.getElementById('photo-placeholder').style.display='flex';">
                    <div id="photo-placeholder" class="photo-placeholder hidden">
                        <?php echo $abbreviatedName; ?>
                    </div>
                <?php else: ?>
                    <div class="photo-placeholder">
                        <?php echo $abbreviatedName; ?>
                    </div>
                <?php endif; ?>
                
                <label for="foto-perfil-input" class="upload-btn">
                    <i class="fas fa-camera"></i> Alterar Foto
                </label>
                <input type="file" id="foto-perfil-input" name="foto_perfil" accept="image/*" class="hidden" 
                       onchange="this.form.submit()">
            </div>

            <div class="info-section">
                <h2>Informações Pessoais</h2>
                
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone); ?>">
                </div>

                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" value="<?php echo htmlspecialchars($cpf); ?>" class="readonly-input" readonly>
                </div>

                <div class="form-group">
                    <label for="datanasc">Data de Nascimento</label>
                    <input type="text" id="datanasc" value="<?php echo htmlspecialchars($dataNasc); ?>" class="readonly-input" readonly>
                </div>

                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>

    <script>
        // Mostrar placeholder se a imagem não carregar
        document.addEventListener('DOMContentLoaded', function() {
            const profilePhoto = document.querySelector('.profile-photo');
            const photoPlaceholder = document.getElementById('photo-placeholder');
            
            if (profilePhoto && !profilePhoto.complete) {
                profilePhoto.onerror = function() {
                    this.style.display = 'none';
                    if (photoPlaceholder) photoPlaceholder.style.display = 'flex';
                };
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>