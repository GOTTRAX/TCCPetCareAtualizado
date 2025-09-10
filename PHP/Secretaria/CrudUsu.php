<?php
// =================== VERIFICAÇÃO DE SESSÃO ===================


// =================== CONEXÃO PDO ===================
include("../conexao.php");

// =================== DEFINIR TÍTULO ===================
$paginaTitulo = "Gerenciamento de Usuários";

// =================== INCLUIR HEADER ===================
include "header.php";

// =================== AÇÕES (POST) ===================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"])) {
    $acao = $_POST["acao"];

    if ($acao === "adicionar") {
        $sql = "INSERT INTO Usuarios (nome, cpf, telefone, email, senha_hash, tipo_usuario, genero, descricao, ativo) 
                VALUES (:nome,:cpf,:telefone,:email,:senha,:tipo,:genero,:descricao,:ativo)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":nome" => $_POST["nome"],
            ":cpf" => $_POST["cpf"],
            ":telefone" => $_POST["telefone"],
            ":email" => $_POST["email"],
            ":senha" => password_hash("123456", PASSWORD_DEFAULT),
            ":tipo" => $_POST["tipo_usuario"],
            ":genero" => $_POST["genero"],
            ":descricao" => $_POST["descricao"],
            ":ativo" => isset($_POST["ativo"]) ? 1 : 0
        ]);
    }

    if ($acao === "editar") {
        $sql = "UPDATE Usuarios 
                   SET nome=:nome, cpf=:cpf, telefone=:telefone, email=:email, 
                       tipo_usuario=:tipo, genero=:genero, descricao=:descricao 
                 WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":id" => $_POST["id"],
            ":nome" => $_POST["nome"],
            ":cpf" => $_POST["cpf"],
            ":telefone" => $_POST["telefone"],
            ":email" => $_POST["email"],
            ":tipo" => $_POST["tipo_usuario"],
            ":genero" => $_POST["genero"],
            ":descricao" => $_POST["descricao"],
        ]);
    }

    if ($acao === "deletar") {
        $stmt = $pdo->prepare("DELETE FROM Usuarios WHERE id=:id");
        $stmt->execute([":id" => $_POST["id"]]);
    }

    if ($acao === "toggleAtivo") {
        $stmt = $pdo->prepare("UPDATE Usuarios SET ativo = NOT ativo, bloqueado_ate = NULL WHERE id=:id");
        $stmt->execute([":id" => $_POST["id"]]);
    }

    if ($acao === "bloquear") {
        $id = (int) $_POST["id"];
        $duracao = $_POST["duracao"] ?? "indefinido";

        if ($duracao === "indefinido") {
            $sql = "UPDATE Usuarios SET ativo = 0, bloqueado_ate = NULL WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":id" => $id]);
        } else {
            $map = [
                "1h" => "1 HOUR",
                "24h" => "24 HOUR",
                "7d" => "7 DAY",
            ];
            $interval = $map[$duracao] ?? "1 HOUR";
            $sql = "UPDATE Usuarios SET bloqueado_ate = DATE_ADD(NOW(), INTERVAL $interval) WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([":id" => $id]);
        }
    }

    if ($acao === "desbloquear") {
        $stmt = $pdo->prepare("UPDATE Usuarios SET bloqueado_ate = NULL WHERE id=:id");
        $stmt->execute([":id" => $_POST["id"]]);
    }

    header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

// =================== AJAX (GET - Relatório de Usuário) ===================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'relatorio' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("SELECT id, nome, email, ativo, bloqueado_ate, ultimo_login, criado, atualizado_em 
                           FROM Usuarios WHERE id=?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT email_tentado, sucesso, ip_origem, navegador, data_hora 
                           FROM Logs_Acesso WHERE usuario_id=? 
                           ORDER BY data_hora DESC LIMIT 50");
    $stmt->execute([$id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>
    <h4>Informações do Usuário</h4>
    <p><b>ID:</b> <?= (int) $usuario["id"] ?></p>
    <p><b>Nome:</b> <?= htmlspecialchars($usuario["nome"]) ?></p>
    <p><b>Email:</b> <?= htmlspecialchars($usuario["email"]) ?></p>
    <p><b>Status:</b> <?= $usuario["ativo"] ? "Ativo" : "Inativo" ?></p>
    <p><b>Bloqueado até:</b> <?= $usuario["bloqueado_ate"] ?: "N/A" ?></p>
    <p><b>Último login:</b> <?= $usuario["ultimo_login"] ?: "Nunca" ?></p>
    <p><b>Criado em:</b> <?= $usuario["criado"] ?></p>
    <p><b>Atualizado em:</b> <?= $usuario["atualizado_em"] ?></p>

    <?php if (!empty($usuario["bloqueado_ate"])): ?>
        <form method="POST" style="margin:10px 0;">
            <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
            <button type="submit" name="acao" value="desbloquear">Desbloquear agora</button>
        </form>
    <?php endif; ?>

    <h4>Logs de Acesso (últimos 50)</h4>
    <table border="1" width="100%" cellpadding="5" cellspacing="0">
        <tr>
            <th>Email Tentado</th>
            <th>Sucesso</th>
            <th>IP</th>
            <th>Navegador</th>
            <th>Data/Hora</th>
        </tr>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l["email_tentado"]) ?></td>
                <td><?= $l["sucesso"] ? "✅" : "❌" ?></td>
                <td><?= htmlspecialchars($l["ip_origem"]) ?></td>
                <td><?= htmlspecialchars($l["navegador"]) ?></td>
                <td><?= $l["data_hora"] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
            <tr>
                <td colspan="5" style="text-align:center">Sem logs para este usuário.</td>
            </tr>
        <?php endif; ?>
    </table>
    <?php
    exit;
}

// =================== FILTROS (GET) ===================
$where = [];
$params = [];

if (!empty($_GET['nome'])) {
    $where[] = "u.nome LIKE ?";
    $params[] = "%" . $_GET['nome'] . "%";
}
if (!empty($_GET['cpf'])) {
    $where[] = "u.cpf LIKE ?";
    $params[] = "%" . $_GET['cpf'] . "%";
}
if (!empty($_GET['telefone'])) {
    $where[] = "u.telefone LIKE ?";
    $params[] = "%" . $_GET['telefone'] . "%";
}
if (!empty($_GET['email'])) {
    $where[] = "u.email LIKE ?";
    $params[] = "%" . $_GET['email'] . "%";
}
if (!empty($_GET['tipo_usuario'])) {
    $where[] = "u.tipo_usuario = ?";
    $params[] = $_GET['tipo_usuario'];
}
if (!empty($_GET['genero'])) {
    $where[] = "u.genero = ?";
    $params[] = $_GET['genero'];
}

$sql = "SELECT u.id, u.nome, u.cpf, u.telefone, u.email, u.tipo_usuario, u.genero, u.descricao,
               u.ativo, u.bloqueado_ate, u.ultimo_login, u.criado, u.atualizado_em,
               COUNT(a.id) AS total_animais
        FROM Usuarios u
        LEFT JOIN Animais a ON u.id = a.usuario_id";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY u.id ORDER BY u.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =================== DADOS PARA GRÁFICOS ===================
$sexoData = $pdo->query("SELECT genero, COUNT(*) AS total FROM Usuarios GROUP BY genero")->fetchAll(PDO::FETCH_ASSOC);
$tipoData = $pdo->query("SELECT tipo_usuario, COUNT(*) AS total FROM Usuarios GROUP BY tipo_usuario")->fetchAll(PDO::FETCH_ASSOC);
$animaisData = $pdo->query("SELECT u.nome, COUNT(a.id) AS total 
                            FROM Usuarios u
                            LEFT JOIN Animais a ON u.id = a.usuario_id
                            GROUP BY u.id")->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
    /* ========== ESTILOS DA BARRA LATERAL ========== */
    :root {
        --primary: #4e73df;
        --secondary: #1cc88a;
        --info: #36b9cc;
        --warning: #f6c23e;
        --danger: #e74a3b;
        --light: #f8f9fc;
        --dark: #5a5c69;
        --sidebar-width: 250px;
        --sidebar-collapsed: 70px;
        --header-height: 60px;
        --transition-speed: 0.3s;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: #f8f9fc;
        color: #333;
        overflow-x: hidden;
        margin: 0;
        padding: 0;
    }

    /* Layout principal */
    .container {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar colapsável */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        background: linear-gradient(180deg, var(--primary) 0%, #224abe 100%);
        color: white;
        z-index: 1000;
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.1);
        transition: width var(--transition-speed) ease;
        overflow-x: hidden;
        width:
            <?php echo $is_home_page ? 'var(--sidebar-width)' : 'var(--sidebar-collapsed)'; ?>
        ;
    }

    .sidebar:hover {
        width: var(--sidebar-width);
    }

    .sidebar-header {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar-header h2 {
        font-size: 1.5rem;
        font-weight: 600;
    }

    .menu-items {
        list-style: none;
        padding: 10px 0;
    }

    .menu-items li {
        margin: 5px 0;
    }

    .menu-items a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
    }

    .menu-items a:hover,
    .menu-items a.active {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        border-left: 4px solid white;
    }

    .menu-items a i {
        margin-right: 15px;
        width: 20px;
        text-align: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .menu-text {
        opacity:
            <?php echo $is_home_page ? '1' : '0'; ?>
        ;
        transition: opacity var(--transition-speed) ease;
    }

    .sidebar:hover .menu-text {
        opacity: 1;
    }

    /* Conteúdo principal */
    .main-content {
        flex: 1;
        margin-left:
            <?php echo $is_home_page ? 'var(--sidebar-width)' : 'var(--sidebar-collapsed)'; ?>
        ;
        padding: 20px;
        transition: margin-left var(--transition-speed) ease;
    }

    .sidebar:hover~.main-content {
        margin-left: var(--sidebar-width);
    }

    /* Botão de toggle para mobile */
    .menu-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1100;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 5px;
        width: 40px;
        height: 40px;
        font-size: 1.5rem;
        cursor: pointer;
    }

    /* ========== ESTILOS DO CRUD ========== */
    .crud-container {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 10px;
        font-size: 14px;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: center;
    }

    th {
        background: #f4f4f4;
    }

    button {
        padding: 8px 12px;
        margin: 4px;
        cursor: pointer;
        border: none;
        border-radius: 4px;
        background: #4e73df;
        color: white;
        font-weight: bold;
    }

    button:hover {
        background: #2e59d9;
    }

    #formAdd {
        display: none;
        margin-top: 15px;
        background: #f8f9fc;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .filtros input,
    .filtros select {
        margin: 5px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    input,
    select,
    textarea {
        padding: 8px;
        margin: 5px 0;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 100%;
    }

    /* Modal genérico */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
    }

    .modal-content {
        background: #fff;
        margin: 5% auto;
        padding: 20px;
        width: 90%;
        max-width: 1000px;
        max-height: 80vh;
        overflow: auto;
        border-radius: 8px;
    }

    .modal header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .modal .close {
        background: #eee;
        border: 0;
        padding: 6px 10px;
        cursor: pointer;
        border-radius: 4px;
    }

    /* Seção de gráficos */
    .charts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }

    .chart-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    h2,
    h3 {
        margin-top: 20px;
        color: #5a5c69;
    }

    .badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        color: #fff;
        display: inline-block;
        margin: 2px;
    }

    .badge.ativo {
        background: #28a745;
    }

    .badge.inativo {
        background: #dc3545;
    }

    /* Responsividade */
    @media (max-width: 992px) {
        .sidebar {
            width: var(--sidebar-collapsed);
        }

        .main-content {
            margin-left: var(--sidebar-collapsed);
        }

        .menu-text {
            opacity: 0;
        }

        .menu-toggle {
            display: block;
        }

        .sidebar.mobile-open {
            width: var(--sidebar-width);
        }

        .sidebar.mobile-open .menu-text {
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .crud-container {
            padding: 15px;
            overflow-x: auto;
        }

        table {
            font-size: 12px;
            display: block;
            overflow-x: auto;
        }

        .filtros {
            display: flex;
            flex-direction: column;
        }

        .filtros input,
        .filtros select {
            margin: 5px 0;
        }

        td,
        th {
            padding: 4px;
        }
    }
</style>
<!-- CONTEÚDO DO CRUD -->
<div class="crud-container">
    <!-- Formulário de Filtro -->
    <form method="GET" class="filtros">
        <input type="text" name="nome" placeholder="Nome" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
        <input type="text" name="cpf" placeholder="CPF" value="<?= htmlspecialchars($_GET['cpf'] ?? '') ?>">
        <input type="text" name="telefone" placeholder="Telefone"
            value="<?= htmlspecialchars($_GET['telefone'] ?? '') ?>">
        <input type="email" name="email" placeholder="E-mail" value="<?= htmlspecialchars($_GET['email'] ?? '') ?>">
        <select name="tipo_usuario">
            <option value="">-- Tipo --</option>
            <?php foreach (["Cliente", "Veterinario", "Secretaria", "Cuidador"] as $t): ?>
                <option value="<?= $t ?>" <?= (($_GET['tipo_usuario'] ?? '') == $t) ? "selected" : "" ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
        <select name="genero">
            <option value="">-- Gênero --</option>
            <?php foreach (["Masculino", "Feminino", "Outro"] as $g): ?>
                <option value="<?= $g ?>" <?= (($_GET['genero'] ?? '') == $g) ? "selected" : "" ?>><?= $g ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <h2>Gerenciamento de Usuários</h2>
    <button onclick="document.getElementById('formAdd').style.display='block'">Adicionar Usuário</button>

    <!-- Form Adicionar -->
    <div id="formAdd">
        <form method="POST">
            <input type="hidden" name="acao" value="adicionar">
            <input type="text" name="nome" placeholder="Nome completo" required>
            <input type="text" name="cpf" placeholder="CPF" required>
            <input type="text" name="telefone" placeholder="Telefone">
            <input type="email" name="email" placeholder="E-mail" required>
            <select name="tipo_usuario">
                <option>Cliente</option>
                <option>Veterinario</option>
                <option>Secretaria</option>
                <option>Cuidador</option>
            </select>
            <select name="genero">
                <option>Masculino</option>
                <option>Feminino</option>
                <option>Outro</option>
            </select>
            <textarea name="descricao" placeholder="Descrição"></textarea>
            <label><input type="checkbox" name="ativo" checked> Ativo</label>
            <button type="submit">Salvar</button>
        </form>
    </div>

    <!-- Tabela Usuários -->
    <?php if (!empty($res)): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Telefone</th>
                <th>Email</th>
                <th>Tipo</th>
                <th>Gênero</th>
                <th>Descrição</th>
                <th>Animais</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($res as $row): ?>
                <tr>
                    <form method="POST">
                        <td><?= (int) $row['id'] ?></td>
                        <td><input type="text" name="nome" value="<?= htmlspecialchars($row['nome']) ?>"></td>
                        <td><input type="text" name="cpf" value="<?= htmlspecialchars($row['cpf']) ?>"></td>
                        <td><input type="text" name="telefone" value="<?= htmlspecialchars($row['telefone']) ?>"></td>
                        <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>"></td>
                        <td>
                            <select name="tipo_usuario">
                                <?php foreach (["Cliente", "Veterinario", "Secretaria", "Cuidador"] as $t): ?>
                                    <option <?= $row['tipo_usuario'] == $t ? "selected" : "" ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="genero">
                                <?php foreach (["Masculino", "Feminino", "Outro"] as $g): ?>
                                    <option <?= $row['genero'] == $g ? "selected" : "" ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><textarea name="descricao"><?= htmlspecialchars($row['descricao']) ?></textarea></td>
                        <td>
                            <a href="../animais.php?usuario_id=<?= (int) $row['id'] ?>">
                                <?= (int) $row['total_animais'] ?> (Ver)
                            </a>
                        </td>
                        <td style="min-width:250px">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <button type="submit" name="acao" value="editar">Salvar</button>
                            <button type="submit" name="acao" value="deletar"
                                onclick="return confirm('Excluir usuário?')">Excluir</button>
                            <button type="button" onclick="abrirRelatorio(<?= (int) $row['id'] ?>)">Relatório</button>
                            <button type="button" onclick="abrirBloqueio(<?= (int) $row['id'] ?>)">Bloquear</button>
                    </form>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" name="acao" value="toggleAtivo">
                            <?= $row['ativo'] ? "Desativar" : "Ativar" ?>
                        </button>
                    </form>
                    <div style="margin-top:4px;">
                        <span class="badge <?= $row['ativo'] ? 'ativo' : 'inativo' ?>">
                            <?= $row['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                        <?php if (!empty($row['bloqueado_ate'])): ?>
                            <span class="badge inativo"
                                title="Bloqueado até <?= htmlspecialchars($row['bloqueado_ate']) ?>">Bloqueado</span>
                        <?php endif; ?>
                    </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p style="text-align: center; padding: 20px; color: #666;">Nenhum usuário encontrado com os filtros aplicados.</p>
    <?php endif; ?>

    <!-- Modais -->
    <div id="modalRelatorio" class="modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <header>
                <h3>Relatório do Usuário</h3>
                <button class="close" onclick="fecharModal('modalRelatorio')">Fechar</button>
            </header>
            <div id="relatorioDados">Carregando...</div>
        </div>
    </div>

    <div id="modalBloqueio" class="modal" role="dialog" aria-modal="true">
        <div class="modal-content">
            <header>
                <h3>Bloquear Usuário</h3>
                <button class="close" onclick="fecharModal('modalBloqueio')">Fechar</button>
            </header>
            <form method="POST" id="formBloqueio">
                <input type="hidden" name="acao" value="bloquear">
                <input type="hidden" name="id" id="bloqueioUserId" value="">
                <p>Escolha a duração do bloqueio:</p>
                <label><input type="radio" name="duracao" value="1h"> 1 hora</label><br>
                <label><input type="radio" name="duracao" value="24h"> 24 horas</label><br>
                <label><input type="radio" name="duracao" value="7d"> 7 dias</label><br>
                <label><input type="radio" name="duracao" value="indefinido" checked> Indeterminado (desativar
                    conta)</label><br><br>
                <button type="submit">Confirmar</button>
            </form>
        </div>
    </div>

    <!-- RELATÓRIOS (GRÁFICOS) -->
    <h3>Relatórios</h3>
    <div class="charts">
        <div class="chart-card">
            <h4>Distribuição por Gênero</h4>
            <canvas id="graficoSexo" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h4>Distribuição por Tipo</h4>
            <canvas id="graficoTipos" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h4>Animais por Usuário</h4>
            <canvas id="graficoAnimais" height="200"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // ====== Dados para gráficos vindos do PHP ======
    const dadosSexo = <?= json_encode($sexoData) ?>;
    const dadosTipos = <?= json_encode($tipoData) ?>;
    const dadosAnimais = <?= json_encode($animaisData) ?>;

    // ====== Gráficos ======
    if (dadosSexo.length > 0) {
        new Chart(document.getElementById('graficoSexo'), {
            type: 'pie',
            data: {
                labels: dadosSexo.map(d => d.genero || 'Não informado'),
                datasets: [{
                    data: dadosSexo.map(d => Number(d.total)),
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    if (dadosTipos.length > 0) {
        new Chart(document.getElementById('graficoTipos'), {
            type: 'pie',
            data: {
                labels: dadosTipos.map(d => d.tipo_usuario || 'Não informado'),
                datasets: [{
                    data: dadosTipos.map(d => Number(d.total)),
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    if (dadosAnimais.length > 0) {
        const sortedData = [...dadosAnimais].sort((a, b) => b.total - a.total).slice(0, 10);

        new Chart(document.getElementById('graficoAnimais'), {
            type: 'bar',
            data: {
                labels: sortedData.map(d => d.nome || 'Não informado'),
                datasets: [{
                    label: 'Animais por Cliente',
                    data: sortedData.map(d => Number(d.total)),
                    backgroundColor: '#4e73df'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // ====== Relatório (AJAX na mesma página) ======
    function abrirRelatorio(userId) {
        const modal = document.getElementById('modalRelatorio');
        const alvo = document.getElementById('relatorioDados');
        alvo.innerHTML = "Carregando...";
        modal.style.display = "block";
        fetch(`<?= basename($_SERVER['PHP_SELF']) ?>?ajax=relatorio&id=` + userId, { credentials: 'same-origin' })
            .then(r => r.text())
            .then(html => alvo.innerHTML = html)
            .catch(() => alvo.innerHTML = "Erro ao carregar relatório.");
    }

    function abrirBloqueio(userId) {
        document.getElementById('bloqueioUserId').value = userId;
        document.getElementById('modalBloqueio').style.display = 'block';
    }

    function fecharModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // fecha modal ao clicar fora do conteúdo
    window.addEventListener('click', function (e) {
        document.querySelectorAll('.modal').forEach(m => {
            if (e.target === m) m.style.display = 'none';
        });
    });
</script>

<?php
// =================== INCLUIR FOOTER ===================
include 'footer.php';
?>