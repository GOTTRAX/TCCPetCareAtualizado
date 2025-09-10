<?php
// =================== CONEXÃO PDO ===================
$host = "localhost";
$db = "PetCare";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// =================== AÇÕES (POST) ===================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao"])) {
    $acao = $_POST["acao"];

    if ($acao === "adicionar") {
        $sql = "INSERT INTO Animais (nome, datanasc, especie_id, raca, porte, sexo, usuario_id, foto) 
                VALUES (:nome, :datanasc, :especie_id, :raca, :porte, :sexo, :usuario_id, :foto)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":nome" => $_POST["nome"],
            ":datanasc" => $_POST["datanasc"],
            ":especie_id" => $_POST["especie_id"],
            ":raca" => $_POST["raca"],
            ":porte" => $_POST["porte"],
            ":sexo" => $_POST["sexo"],
            ":usuario_id" => $_POST["usuario_id"],
            ":foto" => $_POST["foto"] ?? null
        ]);
    }

    if ($acao === "editar") {
        $sql = "UPDATE Animais 
                   SET nome=:nome, datanasc=:datanasc, especie_id=:especie_id, 
                       raca=:raca, porte=:porte, sexo=:sexo, usuario_id=:usuario_id, foto=:foto
                 WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":id" => $_POST["id"],
            ":nome" => $_POST["nome"],
            ":datanasc" => $_POST["datanasc"],
            ":especie_id" => $_POST["especie_id"],
            ":raca" => $_POST["raca"],
            ":porte" => $_POST["porte"],
            ":sexo" => $_POST["sexo"],
            ":usuario_id" => $_POST["usuario_id"],
            ":foto" => $_POST["foto"] ?? null
        ]);
    }

    if ($acao === "deletar") {
        $stmt = $pdo->prepare("DELETE FROM Animais WHERE id=:id");
        $stmt->execute([":id" => $_POST["id"]]);
    }

    header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
    exit;
}

// =================== AJAX (GET - Detalhes do Animal) ===================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'detalhes' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Dados do animal
    $stmt = $pdo->prepare("
        SELECT a.*, e.nome as especie_nome, u.nome as dono_nome, u.email, u.telefone 
        FROM Animais a 
        INNER JOIN Especies e ON a.especie_id = e.id 
        INNER JOIN Usuarios u ON a.usuario_id = u.id 
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);
    $animal = $stmt->fetch(PDO::FETCH_ASSOC);

    // Histórico de consultas
    $stmt = $pdo->prepare("
        SELECT c.data_consulta as data, 'Consulta' as tipo, 
               COALESCE(c.diagnostico, 'Consulta realizada') as descricao
        FROM Consultas c 
        WHERE c.animal_id = ?
        ORDER BY c.data_consulta DESC 
        LIMIT 10
    ");
    $stmt->execute([$id]);
    $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular idade
    $idade = "";
    if ($animal['datanasc']) {
        $nascimento = new DateTime($animal['datanasc']);
        $hoje = new DateTime();
        $idade = $nascimento->diff($hoje)->y . " ano(s)";
    }
    ?>
    
    <h4>Informações do Animal</h4>
    <div class="row">
        <div class="col-md-4">
            <img src="<?= $animal['foto'] ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60' ?>"
                class="img-fluid rounded" alt="<?= htmlspecialchars($animal['nome']) ?>">
        </div>
        <div class="col-md-8">
            <p><b>ID:</b> <?= (int) $animal["id"] ?></p>
            <p><b>Nome:</b> <?= htmlspecialchars($animal["nome"]) ?></p>
            <p><b>Espécie:</b> <?= htmlspecialchars($animal["especie_nome"]) ?></p>
            <p><b>Raça:</b> <?= htmlspecialchars($animal["raca"] ?: 'Não informada') ?></p>
            <p><b>Data de Nascimento:</b>
                <?= $animal["datanasc"] ? date('d/m/Y', strtotime($animal["datanasc"])) : 'Não informada' ?></p>
            <p><b>Idade:</b> <?= $idade ?: 'Não informada' ?></p>
            <p><b>Porte:</b> <?= htmlspecialchars($animal["porte"] ?: 'Não informado') ?></p>
            <p><b>Sexo:</b> <?= htmlspecialchars($animal["sexo"] ?: 'Não informado') ?></p>
            <p><b>Dono:</b> <?= htmlspecialchars($animal["dono_nome"]) ?></p>
            <p><b>Contato:</b> <?= htmlspecialchars($animal["email"]) ?> | <?= htmlspecialchars($animal["telefone"]) ?></p>
        </div>
    </div>

    <h4>Últimas Consultas</h4>
    <?php if (!empty($consultas)): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consultas as $consulta): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($consulta["data"])) ?></td>
                        <td><?= htmlspecialchars($consulta["tipo"]) ?></td>
                        <td><?= htmlspecialchars($consulta["descricao"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhuma consulta registrada para este animal.</p>
    <?php endif; ?>
    <?php
    exit;
}

// =================== FILTROS (GET) ===================
$where = [];
$params = [];

if (!empty($_GET['nome'])) {
    $where[] = "a.nome LIKE ?";
    $params[] = "%" . $_GET['nome'] . "%";
}
if (!empty($_GET['raca'])) {
    $where[] = "a.raca LIKE ?";
    $params[] = "%" . $_GET['raca'] . "%";
}
if (!empty($_GET['especie_id'])) {
    $where[] = "a.especie_id = ?";
    $params[] = $_GET['especie_id'];
}
if (!empty($_GET['porte'])) {
    $where[] = "a.porte = ?";
    $params[] = $_GET['porte'];
}
if (!empty($_GET['sexo'])) {
    $where[] = "a.sexo = ?";
    $params[] = $_GET['sexo'];
}
if (!empty($_GET['usuario_id'])) {
    $where[] = "a.usuario_id = ?";
    $params[] = $_GET['usuario_id'];
}

$sql = "SELECT a.*, e.nome as especie_nome, u.nome as dono_nome 
        FROM Animais a
        INNER JOIN Especies e ON a.especie_id = e.id
        INNER JOIN Usuarios u ON a.usuario_id = u.id";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY a.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$animais = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =================== DADOS PARA GRÁFICOS ===================
$sexoData = $pdo->query("SELECT sexo, COUNT(*) AS total FROM Animais WHERE sexo IS NOT NULL GROUP BY sexo")->fetchAll(PDO::FETCH_ASSOC);
$especieData = $pdo->query("SELECT e.nome, COUNT(a.id) AS total 
                            FROM Animais a
                            INNER JOIN Especies e ON a.especie_id = e.id
                            GROUP BY e.id, e.nome")->fetchAll(PDO::FETCH_ASSOC);
$porteData = $pdo->query("SELECT porte, COUNT(*) AS total FROM Animais WHERE porte IS NOT NULL GROUP BY porte")->fetchAll(PDO::FETCH_ASSOC);

// =================== DADOS PARA FILTROS ===================
$especies = $pdo->query("SELECT id, nome FROM Especies")->fetchAll(PDO::FETCH_ASSOC);
$donos = $pdo->query("SELECT id, nome FROM Usuarios WHERE tipo_usuario = 'Cliente'")->fetchAll(PDO::FETCH_ASSOC);

// =================== DEFINIR TÍTULO DA PÁGINA ===================
$paginaTitulo = "Gerenciamento de Animais";

// =================== INCLUIR HEADER COM SIDEBAR ===================
include 'header.php';
?>
<style>
    :root {
        --verde-principal: #2E8B57;
        --verde-claro: #8FBC8F;
        --verde-escuro: #1a5c38;
        --azul-principal: #4682B4;
        --azul-claro: #87CEEB;
        --azul-escuro: #2c5aa0;
        --cinza-claro: #f8f9fa;
        --cinza-medio: #e9ecef;
        --cinza-escuro: #6c757d;
        --sombra: 0 2px 10px rgba(0, 0, 0, 0.1);
        --borda: 1px solid #ddd;
        --borda-radius: 8px;
    }

    /* ESTILOS GERAIS */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background-color: var(--cinza-claro);
        color: #333;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* BARRA LATERAL */
    #secretaria-sidebar {
        height: 100vh;
        width: 70px;
        position: fixed;
        top: 0;
        left: 0;
        background: linear-gradient(to bottom, #2c3e50, #1a2530);
        overflow-x: hidden;
        transition: width 0.3s ease;
        z-index: 1000;
        box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
        padding-top: 20px;
    }

    #secretaria-sidebar:hover {
        width: 250px;
    }

    .sidebar-logo-container {
        display: flex;
        align-items: center;
        padding: 0 16px;
        margin-bottom: 30px;
    }

    /*ate aq */
    .sidebar-logo {
        color: white;
        font-size: 28px;
        min-width: 40px;
        text-align: center;
    }

    .sidebar-logo-text {
        color: white;
        margin-left: 15px;
        font-weight: bold;
        font-size: 18px;
        white-space: nowrap;
        opacity: 0;
        transition: opacity 0.2s ease 0.1s;
    }

    #secretaria-sidebar:hover .sidebar-logo-text {
        opacity: 1;
    }

    .sidebar-menu {
        list-style: none;
    }

    .sidebar-menu li {
        padding: 12px 16px;
        display: flex;
        align-items: center;
        transition: background-color 0.2s;
        border-left: 4px solid transparent;
    }

    .sidebar-menu li:hover {
        background-color: #34495e;
        border-left: 4px solid #3498db;
    }

    .sidebar-menu a {
        text-decoration: none;
        color: #ecf0f1;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .sidebar-menu i {
        font-size: 22px;
        min-width: 40px;
        text-align: center;
    }

    .sidebar-menu-text {
        white-space: nowrap;
        opacity: 0;
        transition: opacity 0.2s ease 0.1s;
        margin-left: 10px;
        font-size: 16px;
    }

    #secretaria-sidebar:hover .sidebar-menu-text {
        opacity: 1;
    }

    /* CONTEÚDO PRINCIPAL */
    .content {
        margin-left: 70px;
        padding: 30px;
        transition: all 0.3s ease;
        width: calc(100% - 70px);
        min-height: 100vh;
    }

    /* Ajuste para quando o menu expandir */
    #secretaria-sidebar:hover~.content {
        margin-left: 250px;
        width: calc(100% - 250px);
    }

    /* BOTÃO TOGGLE MOBILE */
    #sidebar-toggle {
        position: fixed;
        top: 20px;
        left: 20px;
        background: #2c3e50;
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        font-size: 22px;
        z-index: 1001;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        display: none;
        align-items: center;
        justify-content: center;
    }

    /* NAVBAR */
    .navbar {
        background: linear-gradient(135deg, var(--verde-principal), var(--azul-principal));
        padding: 15px 25px;
        border-radius: var(--borda-radius);
        margin-bottom: 25px;
        color: white;
        width: 100%;
    }

    /* FILTROS */
    .filtros {
        background: white;
        padding: 20px;
        border-radius: var(--borda-radius);
        margin-bottom: 20px;
        box-shadow: var(--sombra);
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }

    .filtros input,
    .filtros select {
        padding: 10px;
        border: var(--borda);
        border-radius: 4px;
        flex: 1;
        min-width: 150px;
    }

    /* TABELAS */
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: var(--borda-radius);
        overflow: hidden;
        box-shadow: var(--sombra);
        margin-top: 20px;
        font-size: 14px;
    }

    th,
    td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: var(--borda);
    }

    th {
        background-color: var(--cinza-medio);
        font-weight: 600;
        text-align: center;
    }

    tr:hover {
        background-color: #f5f5f5;
    }

    /* BOTÕES */
    button {
        padding: 8px 15px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.2s;
        border: none;
        border-radius: 4px;
    }

    .btn-primary {
        background-color: var(--azul-principal);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--azul-escuro);
    }

    .btn-success {
        background-color: var(--verde-principal);
        color: white;
    }

    .btn-success:hover {
        background-color: var(--verde-escuro);
    }

    /* FORMULÁRIOS */
    #formAdd {
        display: none;
        background: white;
        padding: 25px;
        border-radius: var(--borda-radius);
        margin-top: 20px;
        box-shadow: var(--sombra);
    }

    #formAdd input,
    #formAdd select {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: var(--borda);
        border-radius: 4px;
    }

    /* MODAIS */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1100;
        overflow-y: auto;
    }

    .modal-content {
        background: white;
        margin: 50px auto;
        padding: 25px;
        width: 90%;
        max-width: 800px;
        border-radius: var(--borda-radius);
        position: relative;
    }

    .modal header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal .close {
        background: #eee;
        border: 0;
        padding: 6px 10px;
        cursor: pointer;
        border-radius: 4px;
    }

    /* GRÁFICOS */
    .charts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .chart-card {
        background: white;
        padding: 20px;
        border-radius: var(--borda-radius);
        box-shadow: var(--sombra);
    }

    /* ELEMENTOS ESPECÍFICOS */
    .animal-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 5px;
    }

    h2,
    h3 {
        margin-top: 20px;
        margin-bottom: 15px;
    }

    /* RESPONSIVIDADE */
    @media (max-width: 1024px) {
        .content {
            padding: 20px;
        }

        .filtros {
            flex-direction: column;
            align-items: stretch;
        }

        .filtros input,
        .filtros select {
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        #secretaria-sidebar {
            width: 0;
        }

        .content {
            margin-left: 0;
            width: 100%;
            padding: 15px;
        }

        #sidebar-toggle {
            display: flex;
        }

        #secretaria-sidebar.mobile-open {
            width: 250px;
        }

        #secretaria-sidebar.mobile-open~.content {
            margin-left: 0;
            width: 100%;
            filter: brightness(0.7);
            pointer-events: none;
        }

        table {
            display: block;
            overflow-x: auto;
        }

        .charts {
            grid-template-columns: 1fr;
        }

        .navbar {
            padding: 12px 20px;
        }

        .filtros {
            padding: 15px;
        }
    }
</style>
<!-- Formulário de Filtro -->
<form method="GET" class="filtros">
    <input type="text" name="nome" placeholder="Nome" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
    <input type="text" name="raca" placeholder="Raça" value="<?= htmlspecialchars($_GET['raca'] ?? '') ?>">

    <select name="especie_id">
        <option value="">-- Espécie --</option>
        <?php foreach ($especies as $especie): ?>
            <option value="<?= $especie['id'] ?>" <?= (($_GET['especie_id'] ?? '') == $especie['id']) ? "selected" : "" ?>>
                <?= htmlspecialchars($especie['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="porte">
        <option value="">-- Porte --</option>
        <?php foreach (["Pequeno", "Medio", "Grande"] as $porte): ?>
            <option value="<?= $porte ?>" <?= (($_GET['porte'] ?? '') == $porte) ? "selected" : "" ?>>
                <?= $porte ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="sexo">
        <option value="">-- Sexo --</option>
        <?php foreach (["Macho", "Fêmea"] as $sexo): ?>
            <option value="<?= $sexo ?>" <?= (($_GET['sexo'] ?? '') == $sexo) ? "selected" : "" ?>>
                <?= $sexo ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="usuario_id">
        <option value="">-- Dono --</option>
        <?php foreach ($donos as $dono): ?>
            <option value="<?= $dono['id'] ?>" <?= (($_GET['usuario_id'] ?? '') == $dono['id']) ? "selected" : "" ?>>
                <?= htmlspecialchars($dono['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-primary">Filtrar</button>
    <button type="button" onclick="window.location.href='<?= $_SERVER['PHP_SELF'] ?>'" class="btn btn-success">Limpar
        Filtros</button>
</form>

<button onclick="document.getElementById('formAdd').style.display='block'" class="btn btn-success">Adicionar
    Animal</button>

<!-- Form Adicionar -->
<div id="formAdd">
    <form method="POST">
        <input type="hidden" name="acao" value="adicionar">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="date" name="datanasc" placeholder="Data de Nascimento">

        <select name="especie_id" required>
            <option value="">-- Espécie --</option>
            <?php foreach ($especies as $especie): ?>
                <option value="<?= $especie['id'] ?>"><?= htmlspecialchars($especie['nome']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="raca" placeholder="Raça">

        <select name="porte">
            <option value="">-- Porte --</option>
            <?php foreach (["Pequeno", "Medio", "Grande"] as $porte): ?>
                <option value="<?= $porte ?>"><?= $porte ?></option>
            <?php endforeach; ?>
        </select>

        <select name="sexo">
            <option value="">-- Sexo --</option>
            <?php foreach (["Macho", "Fêmea"] as $sexo): ?>
                <option value="<?= $sexo ?>"><?= $sexo ?></option>
            <?php endforeach; ?>
        </select>

        <select name="usuario_id" required>
            <option value="">-- Dono --</option>
            <?php foreach ($donos as $dono): ?>
                <option value="<?= $dono['id'] ?>"><?= htmlspecialchars($dono['nome']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="foto" placeholder="URL da Foto (opcional)">

        <button type="submit" class="btn btn-success">Salvar</button>
        <button type="button" onclick="document.getElementById('formAdd').style.display='none'"
            class="btn btn-primary">Cancelar</button>
    </form>
</div>

<!-- Tabela Animais -->
<table>
    <tr>
        <th>Foto</th>
        <th>ID</th>
        <th>Nome</th>
        <th>Espécie</th>
        <th>Raça</th>
        <th>Porte</th>
        <th>Sexo</th>
        <th>Dono</th>
        <th>Ações</th>
    </tr>
    <?php foreach ($animais as $animal): ?>
        <tr>
            <form method="POST">
                <td>
                    <img src="<?= $animal['foto'] ?: 'PHP/uploads/pets/' ?>"
                        class="animal-img" alt="<?= htmlspecialchars($animal['nome']) ?>">
                </td>
                <td><?= (int) $animal['id'] ?></td>
                <td><input type="text" name="nome" value="<?= htmlspecialchars($animal['nome']) ?>"></td>
                <td>
                    <select name="especie_id">
                        <?php foreach ($especies as $especie): ?>
                            <option value="<?= $especie['id'] ?>" <?= $animal['especie_id'] == $especie['id'] ? "selected" : "" ?>>
                                <?= htmlspecialchars($especie['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="raca" value="<?= htmlspecialchars($animal['raca']) ?>"></td>
                <td>
                    <select name="porte">
                        <option value="">-- Selecione --</option>
                        <?php foreach (["Pequeno", "Medio", "Grande"] as $porte): ?>
                            <option value="<?= $porte ?>" <?= $animal['porte'] == $porte ? "selected" : "" ?>>
                                <?= $porte ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="sexo">
                        <option value="">-- Selecione --</option>
                        <?php foreach (["Macho", "Fêmea"] as $sexo): ?>
                            <option value="<?= $sexo ?>" <?= $animal['sexo'] == $sexo ? "selected" : "" ?>>
                                <?= $sexo ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <select name="usuario_id">
                        <?php foreach ($donos as $dono): ?>
                            <option value="<?= $dono['id'] ?>" <?= $animal['usuario_id'] == $dono['id'] ? "selected" : "" ?>>
                                <?= htmlspecialchars($dono['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="min-width:250px">
                    <input type="hidden" name="id" value="<?= (int) $animal['id'] ?>">
                    <input type="hidden" name="foto" value="<?= htmlspecialchars($animal['foto'] ?? '') ?>">
                    <button type="submit" name="acao" value="editar" class="btn btn-primary">Salvar</button>
                    <button type="submit" name="acao" value="deletar" onclick="return confirm('Excluir animal?')"
                        class="btn btn-primary">Excluir</button>
                    <button type="button" onclick="abrirDetalhes(<?= (int) $animal['id'] ?>)"
                        class="btn btn-primary">Detalhes</button>
            </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<!-- Modais -->
<div id="modalDetalhes" class="modal" role="dialog" aria-modal="true">
    <div class="modal-content">
        <header>
            <h3>Detalhes do Animal</h3>
            <button class="close" onclick="fecharModal('modalDetalhes')">Fechar</button>
        </header>
        <div id="detalhesDados">Carregando...</div>
    </div>
</div>

<!-- RELATÓRIOS (GRÁFICOS) -->
<h3>Relatórios</h3>
<div class="charts">
    <div class="chart-card">
        <canvas id="graficoSexo" height="200"></canvas>
    </div>
    <div class="chart-card">
        <canvas id="graficoEspecies" height="200"></canvas>
    </div>
    <div class="chart-card">
        <canvas id="graficoPortes" height="200"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // ====== Dados para gráficos vindos do PHP ======
    const dadosSexo = <?= json_encode($sexoData) ?>;
    const dadosEspecies = <?= json_encode($especieData) ?>;
    const dadosPortes = <?= json_encode($porteData) ?>;

    // ====== Gráficos ======
    new Chart(document.getElementById('graficoSexo'), {
        type: 'pie',
        data: {
            labels: dadosSexo.map(d => d.sexo),
            datasets: [{
                data: dadosSexo.map(d => Number(d.total)),
                backgroundColor: ['#4682B4', '#2E8B57']
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição por Sexo'
                }
            }
        }
    });

    new Chart(document.getElementById('graficoEspecies'), {
        type: 'pie',
        data: {
            labels: dadosEspecies.map(d => d.nome),
            datasets: [{
                data: dadosEspecies.map(d => Number(d.total)),
                backgroundColor: ['#2E8B57', '#4682B4', '#3CB371', '#20B2AA', '#48D1CC']
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição por Espécie'
                }
            }
        }
    });

    new Chart(document.getElementById('graficoPortes'), {
        type: 'bar',
        data: {
            labels: dadosPortes.map(d => d.porte),
            datasets: [{
                label: 'Quantidade por Porte',
                data: dadosPortes.map(d => Number(d.total)),
                backgroundColor: '#2E8B57'
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição por Porte'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // ====== Detalhes (AJAX na mesma página) ======
    function abrirDetalhes(animalId) {
        const modal = document.getElementById('modalDetalhes');
        const alvo = document.getElementById('detalhesDados');
        alvo.innerHTML = "Carregando...";
        modal.style.display = "block";
        fetch(`<?= basename($_SERVER['PHP_SELF']) ?>?ajax=detalhes&id=` + animalId, { credentials: 'same-origin' })
            .then(r => r.text())
            .then(html => alvo.innerHTML = html)
            .catch(() => alvo.innerHTML = "Erro ao carregar detalhes.");
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