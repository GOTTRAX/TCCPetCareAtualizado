<?php
// PHP/Secretaria/config.php
$paginaTitulo = "Configurações";
include("header.php");
include("../conexao.php"); // $pdo

// ====== FLASH (mensagens de feedback) ======
function set_flash($msg, $tipo='success'){ $_SESSION['flash']=['msg'=>$msg,'tipo'=>$tipo]; }
function get_flash(){ if(!empty($_SESSION['flash'])){ $f=$_SESSION['flash']; unset($_SESSION['flash']); return $f; } return null; }

// ====== TRATAMENTO DE AÇÕES (POST) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        // ------- CRUD SERVIÇOS -------
        if ($acao === 'servico_criar') {
            $sql = "INSERT INTO Servicos (nome, descricao, preco_normal, preco_feriado) 
                    VALUES (:n,:d,:pn,:pf)";
            $st = $pdo->prepare($sql);
            $st->execute([
                ':n'=>trim($_POST['nome']??''),
                ':d'=>trim($_POST['descricao']??''),
                ':pn'=>$_POST['preco_normal']??0,
                ':pf'=>$_POST['preco_feriado']??0
            ]);
            set_flash("Serviço cadastrado com sucesso!");
        }
        if ($acao === 'servico_atualizar') {
            $sql = "UPDATE Servicos SET nome=:n, descricao=:d, preco_normal=:pn, preco_feriado=:pf WHERE id=:id";
            $st = $pdo->prepare($sql);
            $st->execute([
                ':n'=>trim($_POST['nome']??''),
                ':d'=>trim($_POST['descricao']??''),
                ':pn'=>$_POST['preco_normal']??0,
                ':pf'=>$_POST['preco_feriado']??0,
                ':id'=>$_POST['id']??0
            ]);
            set_flash("Serviço atualizado!");
        }
        if ($acao === 'servico_excluir') {
            $st = $pdo->prepare("DELETE FROM Servicos WHERE id=:id");
            $st->execute([':id'=>$_POST['id']??0]);
            set_flash("Serviço excluído!", "info");
        }

        // ------- DIAS TRABALHADOS (salvar em lote) -------
        if ($acao === 'dias_salvar') {
            // Para cada dia, upsert (se não existir, cria)
            $dias = $_POST['dia'] ?? [];
            foreach ($dias as $k => $dado) {
                $dia_semana = $dado['dia_semana'];
                $abertura = $dado['abertura'] ?: '08:00';
                $fechamento = $dado['fechamento'] ?: '18:00';
                $ativo = isset($dado['ativo']) ? 1 : 0;

                // Existe?
                $chk = $pdo->prepare("SELECT id FROM Dias_Trabalhados WHERE dia_semana=:ds");
                $chk->execute([':ds'=>$dia_semana]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $up = $pdo->prepare("UPDATE Dias_Trabalhados 
                                         SET horario_abertura=:a, horario_fechamento=:f, ativo=:atv 
                                         WHERE id=:id");
                    $up->execute([
                        ':a'=>$abertura, ':f'=>$fechamento, ':atv'=>$ativo, ':id'=>$row['id']
                    ]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO Dias_Trabalhados (dia_semana, horario_abertura, horario_fechamento, ativo) 
                                          VALUES (:ds,:a,:f,:atv)");
                    $ins->execute([
                        ':ds'=>$dia_semana, ':a'=>$abertura, ':f'=>$fechamento, ':atv'=>$ativo
                    ]);
                }
            }
            set_flash("Dias trabalhados salvos!");
        }

        // ------- PERÍODOS INATIVOS -------
        if ($acao === 'periodo_criar') {
            $st = $pdo->prepare("INSERT INTO Periodos_Inativos (data_inicio, data_fim, motivo) 
                                 VALUES (:i,:f,:m)");
            $st->execute([
                ':i'=>$_POST['data_inicio']??'',
                ':f'=>$_POST['data_fim']??'',
                ':m'=>trim($_POST['motivo']??'')
            ]);
            set_flash("Período inativo cadastrado!");
        }
        if ($acao === 'periodo_excluir') {
            $st = $pdo->prepare("DELETE FROM Periodos_Inativos WHERE id=:id");
            $st->execute([':id'=>$_POST['id']??0]);
            set_flash("Período removido.", "info");
        }

        // ------- FERIADOS -------
        if ($acao === 'feriado_criar') {
            $st = $pdo->prepare("INSERT INTO Feriados (nome, data) VALUES (:n,:d)");
            $st->execute([':n'=>trim($_POST['nome']??''), ':d'=>$_POST['data']??'']);
            set_flash("Feriado adicionado!");
        }
        if ($acao === 'feriado_excluir') {
            $st = $pdo->prepare("DELETE FROM Feriados WHERE id=:id");
            $st->execute([':id'=>$_POST['id']??0]);
            set_flash("Feriado removido.", "info");
        }

    } catch (PDOException $e) {
        set_flash("Erro ao salvar: ".$e->getMessage(), "error");
    }

    // PRG: evita reenvio de formulário
    header("Location: config.php");
    exit;
}

// ====== CARREGAR DADOS PARA A TELA ======
$servicos = $pdo->query("SELECT * FROM Servicos ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// garantir 7 dias (se não existirem)
$diasPadrao = ['Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'];
$rows = $pdo->query("SELECT * FROM Dias_Trabalhados")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);
$dias = [];
foreach ($diasPadrao as $d) {
    if (isset($rows[$d])) $dias[$d] = $rows[$d];
    else $dias[$d] = ['dia_semana'=>$d,'horario_abertura'=>'08:00','horario_fechamento'=>'18:00','ativo'=>($d!='Domingo')?1:0];
}

$periodos = $pdo->query("SELECT * FROM Periodos_Inativos ORDER BY data_inicio DESC")->fetchAll(PDO::FETCH_ASSOC);
$feriados = $pdo->query("SELECT * FROM Feriados ORDER BY data")->fetchAll(PDO::FETCH_ASSOC);
$flash = get_flash();
?>

<style>
/* ===== ESTILO DA PÁGINA DE CONFIG ===== */
:root{
    --pc-primary:#2E8B57;
    --pc-dark:#1F5F3F;
    --pc-bg:#f5f7fb;
    --pc-card:#ffffff;
    --pc-border:#e6e8ee;
    --pc-text:#2c3e50;
    --pc-light:#7F8C8D;
    --pc-danger:#ff6b6b;
    --pc-info:#3B82F6;
    --radius:14px;
}
.config-wrap{
    max-width: 1200px;
    margin: 10px auto 40px;
}
.tabs{
    display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px;
}
.tab-btn{
    border:1px solid var(--pc-border);
    background:#fff; color:var(--pc-text);
    padding:10px 14px; border-radius:999px; cursor:pointer;
    font-weight:600;
}
.tab-btn.active{ background:var(--pc-primary); color:#fff; border-color:var(--pc-primary); }
.tab-content{ display:none; }
.tab-content.active{ display:block; }

.card{
    background:var(--pc-card); border:1px solid var(--pc-border);
    box-shadow:0 10px 30px rgba(0,0,0,.06);
    border-radius: var(--radius); padding:18px; margin-bottom:18px;
}
.card h2{ margin-bottom:10px; color:var(--pc-text); font-size:1.2rem }
.grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.grid-3{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
@media (max-width:900px){ .grid-2,.grid-3{ grid-template-columns:1fr; } }

label small{ color:var(--pc-light); display:block; margin-bottom:6px; font-weight:600 }
input[type="text"], input[type="number"], input[type="time"], input[type="date"], textarea, select{
    width:100%; padding:10px 12px; border:2px solid var(--pc-border);
    border-radius:10px; outline:none; font-size:.95rem; background:#fff;
}
input:focus, textarea:focus, select:focus{ border-color: var(--pc-primary); box-shadow: 0 0 0 3px rgba(46,139,87,.12); }

.btn{
    border:none; padding:10px 14px; border-radius:10px; font-weight:700; cursor:pointer;
}
.btn-primary{ background:linear-gradient(135deg,#7d8a83,#48B973); color:#fff;}
.btn-light{ background:#f0f2f6; }
.btn-danger{ background:var(--pc-danger); color:#fff; }
.btn-outline{ background:#fff; border:2px solid var(--pc-border); }

.table{
    width:100%; border-collapse:collapse; border:1px solid var(--pc-border); overflow:hidden; border-radius:10px;
}
.table th,.table td{ padding:10px 12px; border-bottom:1px solid var(--pc-border); text-align:left; }
.table th{ background:#f8fafc; color:var(--pc-light); font-weight:800; font-size:.85rem; }

.badge{ padding:4px 8px; border-radius:999px; font-size:.75rem; font-weight:700; }
.badge.on{ background:#e7f7ee; color:var(--pc-dark); }
.badge.off{ background:#ffe9e9; color:#b33636; }

/* Toast */
.flash{ margin-bottom:14px; padding:12px 14px; border-radius:10px; font-weight:700; }
.flash.success{ background:#e8f7ef; color:#0f5132; }
.flash.info{ background:#e7f0ff; color:#1e3a8a; }
.flash.error{ background:#ffecec; color:#8a1c1c; }
</style>

<div class="config-wrap">
    <?php if($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['tipo']) ?>">
            <i class="fa fa-info-circle"></i> <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab-btn active" data-tab="tab-servicos"><i class="fa fa-stethoscope"></i> Serviços</button>
        <button class="tab-btn" data-tab="tab-dias"><i class="fa fa-calendar-day"></i> Dias trabalhados</button>
        <button class="tab-btn" data-tab="tab-periodos"><i class="fa fa-plane-slash"></i> Períodos inativos</button>
        <button class="tab-btn" data-tab="tab-feriados"><i class="fa fa-flag"></i> Feriados</button>
    </div>

    <!-- SERVIÇOS -->
     
    <div id="tab-servicos" class="tab-content active">
        <div class="card">
            <h2><i class="fa fa-plus"></i> Novo serviço</h2>
            <form method="post" class="grid-3">
                <input type="hidden" name="acao" value="servico_criar">
                <div>
                    <label><small>Nome</small>
                        <input type="text" name="nome" required placeholder="Ex: Consulta geral">
                    </label>
                </div>
                <div>
                    <label><small>Preço normal (R$)</small>
                        <input type="number" name="preco_normal" step="0.01" min="0" required>
                    </label>
                </div>
                <div>
                    <label><small>Preço em feriado (R$)</small>
                        <input type="number" name="preco_feriado" step="0.01" min="0" required>
                    </label>
                </div>
                <div class="grid-3" style="grid-column:1/-1">
                    <label style="grid-column:1/-1"><small>Descrição</small>
                        <textarea name="descricao" rows="3" placeholder="Detalhes do serviço..."></textarea>
                    </label>
                    <div style="grid-column:1/-1; display:flex; gap:10px;">
                        <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Salvar serviço</button>
                        <button class="btn btn-light" type="reset">Limpar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <h2><i class="fa fa-list"></i> Serviços cadastrados</h2>
            <div style="overflow:auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Preço normal</th>
                            <th>Preço feriado</th>
                            <th>Descrição</th>
                            <th style="width:180px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$servicos): ?>
                            <tr><td colspan="5">Nenhum serviço cadastrado.</td></tr>
                        <?php else: foreach($servicos as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['nome']) ?></td>
                                <td>R$ <?= number_format($s['preco_normal'],2,',','.') ?></td>
                                <td>R$ <?= number_format($s['preco_feriado'],2,',','.') ?></td>
                                <td><?= nl2br(htmlspecialchars($s['descricao'])) ?></td>
                                <td>
                                    <!-- editar inline -->
                                    <details>
                                        <summary class="btn btn-outline" style="display:inline-block; cursor:pointer;">Editar</summary>
                                        <form method="post" class="grid-3" style="margin-top:10px">
                                            <input type="hidden" name="acao" value="servico_atualizar">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <label><small>Nome</small><input type="text" name="nome" value="<?= htmlspecialchars($s['nome']) ?>" required></label>
                                            <label><small>Preço normal</small><input type="number" step="0.01" name="preco_normal" value="<?= htmlspecialchars($s['preco_normal']) ?>" required></label>
                                            <label><small>Preço feriado</small><input type="number" step="0.01" name="preco_feriado" value="<?= htmlspecialchars($s['preco_feriado']) ?>" required></label>
                                            <label style="grid-column:1/-1"><small>Descrição</small><textarea name="descricao" rows="2"><?= htmlspecialchars($s['descricao']) ?></textarea></label>
                                            <div style="grid-column:1/-1; display:flex; gap:8px;">
                                                <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Atualizar</button>
                                            </div>
                                        </form>
                                    </details>

                                    <form method="post" style="display:inline" onsubmit="return confirm('Excluir este serviço?');">
                                        <input type="hidden" name="acao" value="servico_excluir">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button class="btn btn-danger" type="submit"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- DIAS TRABALHADOS -->
    <div id="tab-dias" class="tab-content">
        <div class="card">
            <h2><i class="fa fa-business-time"></i> Definição de agenda semanal</h2>
            <form method="post">
                <input type="hidden" name="acao" value="dias_salvar">
                <div class="grid-3">
                    <?php foreach($dias as $d=>$info): ?>
                        <div class="card" style="border:1px dashed var(--pc-border)">
                            <h3 style="margin-bottom:10px"><?= htmlspecialchars($d) ?></h3>
                            <input type="hidden" name="dia[<?= $d ?>][dia_semana]" value="<?= htmlspecialchars($d) ?>">
                            <label><small>Abertura</small>
                                <input type="time" name="dia[<?= $d ?>][abertura]" value="<?= htmlspecialchars($info['horario_abertura']) ?>">
                            </label>
                            <label><small>Fechamento</small>
                                <input type="time" name="dia[<?= $d ?>][fechamento]" value="<?= htmlspecialchars($info['horario_fechamento']) ?>">
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;margin-top:6px">
                                <input type="checkbox" name="dia[<?= $d ?>][ativo]" <?= !empty($info['ativo'])?'checked':''; ?>> Ativo
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:10px">
                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Salvar semana</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PERÍODOS INATIVOS -->
    <div id="tab-periodos" class="tab-content">
        <div class="card">
            <h2><i class="fa fa-plane-slash"></i> Cadastrar período inativo</h2>
            <form method="post" class="grid-3">
                <input type="hidden" name="acao" value="periodo_criar">
                <label><small>Data início</small><input type="date" name="data_inicio" required></label>
                <label><small>Data fim</small><input type="date" name="data_fim" required></label>
                <label style="grid-column:1/-1"><small>Motivo</small><input type="text" name="motivo" required placeholder="Ex: Reforma, viagem, inventário..."></label>
                <div style="grid-column:1/-1"><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Cadastrar</button></div>
            </form>
        </div>

        <div class="card">
            <h2><i class="fa fa-list-check"></i> Períodos cadastrados</h2>
            <div style="overflow:auto">
                <table class="table">
                    <thead><tr><th>Início</th><th>Fim</th><th>Motivo</th><th style="width:120px">Ações</th></tr></thead>
                    <tbody>
                        <?php if(!$periodos): ?>
                            <tr><td colspan="4">Nenhum período inativo.</td></tr>
                        <?php else: foreach($periodos as $p): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($p['data_inicio'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($p['data_fim'])) ?></td>
                                <td><?= htmlspecialchars($p['motivo']) ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Remover este período?');">
                                        <input type="hidden" name="acao" value="periodo_excluir">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button class="btn btn-danger" type="submit"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FERIADOS -->
    <div id="tab-feriados" class="tab-content">
        <div class="card">
            <h2><i class="fa fa-flag"></i> Adicionar feriado</h2>
            <form method="post" class="grid-2">
                <input type="hidden" name="acao" value="feriado_criar">
                <label><small>Nome</small><input type="text" name="nome" required placeholder="Ex: Natal"></label>
                <label><small>Data</small><input type="date" name="data" required></label>
                <div style="grid-column:1/-1"><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Adicionar</button></div>
            </form>
        </div>

        <div class="card">
            <h2><i class="fa fa-list"></i> Feriados</h2>
            <div style="overflow:auto">
                <table class="table">
                    <thead><tr><th>Data</th><th>Nome</th><th style="width:120px">Ações</th></tr></thead>
                    <tbody>
                        <?php if(!$feriados): ?>
                            <tr><td colspan="3">Nenhum feriado cadastrado.</td></tr>
                        <?php else: foreach($feriados as $f): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($f['data'])) ?></td>
                                <td><?= htmlspecialchars($f['nome']) ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Remover feriado?');">
                                        <input type="hidden" name="acao" value="feriado_excluir">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                        <button class="btn btn-danger" type="submit"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Tabs
document.querySelectorAll('.tab-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
        // rolar pro topo da área de conteúdo
        window.scrollTo({top:0, behavior:'smooth'});
    });
});
</script>

<?php include("footer.php"); ?>
