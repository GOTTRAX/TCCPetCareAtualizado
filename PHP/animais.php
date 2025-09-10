<?php
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "PetCare";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro: " . $e->getMessage());
}

// pega o id do usuário vindo do link
$usuario_id = $_GET['usuario_id'] ?? null;
if (!$usuario_id) {
    die("Usuário não informado");
}

// pega o dono
$stmt = $pdo->prepare("SELECT nome FROM Usuarios WHERE id=?");
$stmt->execute([$usuario_id]);
$dono = $stmt->fetchColumn();

// pega animais desse usuário
$sql = "SELECT a.id, a.nome, a.datanasc, e.nome AS especie, a.raca
        FROM Animais a
        JOIN Especies e ON a.especie_id = e.id
        WHERE a.usuario_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$animais = $stmt->fetchAll(PDO::FETCH_ASSOC);

function calcularIdade($dataNasc) {
    if (!$dataNasc) return "Não informado";
    $nasc = new DateTime($dataNasc);
    return $nasc->diff(new DateTime())->y . " ano(s)";
}

// preparar consultas extras
$sqlAg = "SELECT id, data_hora, hora_inicio, hora_final, status, observacoes
          FROM Agendamentos
          WHERE animal_id = ?
          ORDER BY data_hora DESC LIMIT 5";
$stmtAg = $pdo->prepare($sqlAg);

$sqlCo = "SELECT id, diagnostico, tratamento, receita, data_consulta
          FROM Consultas
          WHERE animal_id = ?
          ORDER BY data_consulta DESC LIMIT 5";
$stmtCo = $pdo->prepare($sqlCo);

$sqlPr = "SELECT id, observacoes, data_registro
          FROM Prontuarios
          WHERE consulta_id IN (
              SELECT id FROM Consultas WHERE animal_id = ?
          )
          ORDER BY data_registro DESC LIMIT 5";
$stmtPr = $pdo->prepare($sqlPr);

$animaisDetalhes = [];
foreach ($animais as $a) {
    $stmtAg->execute([$a['id']]);
    $a['agendamentos'] = $stmtAg->fetchAll(PDO::FETCH_ASSOC);

    $stmtCo->execute([$a['id']]);
    $a['consultas'] = $stmtCo->fetchAll(PDO::FETCH_ASSOC);

    $stmtPr->execute([$a['id']]);
    $a['prontuarios'] = $stmtPr->fetchAll(PDO::FETCH_ASSOC);

    $animaisDetalhes[] = $a;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Animais de <?= htmlspecialchars($dono) ?></title>
<style>
body { font-family: Arial, sans-serif; display:flex; margin:0; }
.container { display:flex; width:100%; }
.cards { flex:3; display:flex; flex-wrap:wrap; gap:15px; padding:20px; }
.card {
    width:200px; padding:15px; border:1px solid #ccc;
    border-radius:10px; background:#f9f9f9; cursor:pointer;
    text-align:center; transition:0.2s;
}
.card:hover { background:#eaeaea; }
.sidebar {
    flex:2; padding:20px; border-left:2px solid #ccc;
    background:#fff; display:none;
}
.sidebar h2 { margin-top:0; }
.sidebar ul { padding-left:20px; }
.sidebar li { margin-bottom:5px; }
</style>
</head>
<body>
<div class="container">
    <!-- cards -->
    <div class="cards">
        <?php foreach($animaisDetalhes as $a): ?>
        <div class="card" onclick='mostrarDetalhes(<?= json_encode($a, JSON_UNESCAPED_UNICODE) ?>)'>
            <h3><?= htmlspecialchars($a['nome']) ?></h3>
            <p><strong>Espécie:</strong> <?= htmlspecialchars($a['especie']) ?></p>
            <p><strong>Raça:</strong> <?= htmlspecialchars($a['raca']) ?></p>
            <p><strong>Idade:</strong> <?= calcularIdade($a['datanasc']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- sidebar -->
    <div class="sidebar" id="sidebar">
        <h2 id="detalheNome">Selecione um animal</h2>
        <p><strong>Espécie:</strong> <span id="detalheEspecie"></span></p>
        <p><strong>Raça:</strong> <span id="detalheRaca"></span></p>
        <p><strong>Idade:</strong> <span id="detalheIdade"></span></p>

        <h3>Agendamentos</h3>
        <ul id="detalheAgendamentos"></ul>

        <h3>Consultas</h3>
        <ul id="detalheConsultas"></ul>

        <h3>Prontuários</h3>
        <ul id="detalheProntuarios"></ul>
    </div>
</div>

<script>
function mostrarDetalhes(animal) {
    document.getElementById("sidebar").style.display = "block";
    document.getElementById("detalheNome").textContent = animal.nome;
    document.getElementById("detalheEspecie").textContent = animal.especie;
    document.getElementById("detalheRaca").textContent = animal.raca ?? "Não informado";

    // idade
    if (animal.datanasc) {
        const nasc = new Date(animal.datanasc);
        const hoje = new Date();
        let idade = hoje.getFullYear() - nasc.getFullYear();
        const m = hoje.getMonth() - nasc.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < nasc.getDate())) idade--;
        document.getElementById("detalheIdade").textContent = idade + " ano(s)";
    } else {
        document.getElementById("detalheIdade").textContent = "Não informado";
    }

    // agendamentos
    let ag = document.getElementById("detalheAgendamentos");
    ag.innerHTML = "";
    if (animal.agendamentos && animal.agendamentos.length > 0) {
        animal.agendamentos.forEach(a => {
            let li = document.createElement("li");
            li.textContent = `${a.data_hora} ${a.hora_inicio}-${a.hora_final} | ${a.status} | ${a.observacoes}`;
            ag.appendChild(li);
        });
    } else {
        ag.innerHTML = "<li>Nenhum agendamento</li>";
    }

    // consultas
    let co = document.getElementById("detalheConsultas");
    co.innerHTML = "";
    if (animal.consultas && animal.consultas.length > 0) {
        animal.consultas.forEach(c => {
            let li = document.createElement("li");
            li.textContent = `${c.data_consulta} | Diagnóstico: ${c.diagnostico ?? "N/A"}`;
            co.appendChild(li);
        });
    } else {
        co.innerHTML = "<li>Nenhuma consulta</li>";
    }

    // prontuários
    let pr = document.getElementById("detalheProntuarios");
    pr.innerHTML = "";
    if (animal.prontuarios && animal.prontuarios.length > 0) {
        animal.prontuarios.forEach(p => {
            let li = document.createElement("li");
            li.textContent = `${p.data_registro} | ${p.observacoes}`;
            pr.appendChild(li);
        });
    } else {
        pr.innerHTML = "<li>Nenhum prontuário</li>";
    }
}
</script>
</body>
</html>
