<?php
// Conexão com o banco de dados
require_once '../conexao.php';


// Consultas para obter os dados
$query_usuarios = "SELECT COUNT(*) as total FROM Usuarios WHERE ativo = TRUE";
$query_animais = "SELECT COUNT(*) as total FROM Animais";
$query_agendamentos = "SELECT COUNT(*) as total FROM Agendamentos WHERE status = 'confirmado'";
$query_agendamentos_pendentes = "SELECT COUNT(*) as total FROM Agendamentos WHERE status = 'pendente'";
$query_consultas_hoje = "SELECT COUNT(*) as total FROM Consultas WHERE DATE(data_consulta) = CURDATE()";

// Executar consultas
$usuarios = $pdo->query($query_usuarios)->fetch(PDO::FETCH_ASSOC);
$animais = $pdo->query($query_animais)->fetch(PDO::FETCH_ASSOC);
$agendamentos = $pdo->query($query_agendamentos)->fetch(PDO::FETCH_ASSOC);
$agendamentos_pendentes = $pdo->query($query_agendamentos_pendentes)->fetch(PDO::FETCH_ASSOC);
$consultas_hoje = $pdo->query($query_consultas_hoje)->fetch(PDO::FETCH_ASSOC);

// Dados para gráficos
$query_especies = "SELECT e.nome, COUNT(a.id) as total 
                   FROM Animais a 
                   INNER JOIN Especies e ON a.especie_id = e.id 
                   GROUP BY e.id, e.nome";
$especies_data = $pdo->query($query_especies)->fetchAll(PDO::FETCH_ASSOC);

// CONSULTA CORRIGIDA
$query_agendamentos_mes = "SELECT DATE(data_hora) as dia, COUNT(*) as total 
                           FROM Agendamentos 
                           WHERE data_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                           GROUP BY DATE(data_hora) 
                           ORDER BY dia";
$agendamentos_mes = $pdo->query($query_agendamentos_mes)->fetchAll(PDO::FETCH_ASSOC);

// Próximos agendamentos
$query_proximos_agendamentos = "SELECT a.data_hora, a.hora_inicio, u.nome as cliente, an.nome as animal 
                                FROM Agendamentos a 
                                INNER JOIN Usuarios u ON a.cliente_id = u.id 
                                INNER JOIN Animais an ON a.animal_id = an.id 
                                WHERE a.data_hora >= CURDATE() 
                                ORDER BY a.data_hora, a.hora_inicio 
                                LIMIT 5";
$proximos_agendamentos = $pdo->query($query_proximos_agendamentos)->fetchAll(PDO::FETCH_ASSOC);

// Obter dados do usuário logado
$usuario_id = $_SESSION['usuario_id'] ?? 1;
$query_usuario = "SELECT nome, email FROM Usuarios WHERE id = :usuario_id";
$stmt = $pdo->prepare($query_usuario);
$stmt->execute([':usuario_id' => $usuario_id]);
$usuario_logado = $stmt->fetch(PDO::FETCH_ASSOC);

// Inicializar variáveis do usuário
$nome_usuario = $usuario_logado['nome'] ?? 'Secretaria';
$email_usuario = $usuario_logado['email'] ?? 'sec@sec.com';
$iniciais = '';
$nomes = explode(' ', $nome_usuario);
if (count($nomes) > 0) {
    $iniciais = strtoupper(substr($nomes[0], 0, 1) . (count($nomes) > 1 ? substr(end($nomes), 0, 1) : ''));
} else {
    $iniciais = 'SC';
}

// Definir título da página
$paginaTitulo = "Dashboard Secretaria";

// Incluir header
include 'header.php';
?>

<style>
    :root {
        --primary: #2E8B57;
        --primary-dark: #1a5c38;
        --primary-light: #8FBC8F;
        --secondary: #4682B4;
        --secondary-dark: #2c5aa0;
        --secondary-light: #87CEEB;
        --accent: #FF6B6B;
        --text-dark: #2c3e50;
        --text-light: #7f8c8d;
        --bg-light: #f8f9fa;
        --bg-white: #ffffff;
        --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        --radius: 12px;
        --transition: all 0.3s ease;
    }

    .dashboard-content {
        padding: 30px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .search-container {
        display: flex;
        align-items: center;
        background: var(--bg-white);
        border-radius: 30px;
        padding: 5px 15px;
        box-shadow: var(--shadow);
        width: 300px;
    }

    .search-container .input {
        border: none;
        outline: none;
        background: transparent;
        padding: 10px;
        width: 100%;
    }

    .search-container .btn-search {
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 16px;
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background-color: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 15px;
    }

    .page-title {
        font-size: 24px;
        font-weight: 600;
        color: var(--text-dark);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background-color: var(--bg-white);
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow);
        display: flex;
        align-items: center;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 15px;
        color: white;
    }

    .icon-users {
        background: linear-gradient(45deg, var(--secondary), var(--secondary-dark));
    }

    .icon-pets {
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
    }

    .icon-calendar {
        background: linear-gradient(45deg, #9b59b6, #8e44ad);
    }

    .icon-today {
        background: linear-gradient(45deg, #e74c3c, #c0392b);
    }

    .stat-content {
        flex: 1;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .stat-label {
        color: var(--text-light);
        font-size: 14px;
    }

    /* Charts Section */
    .charts-section {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    .chart-card {
        background-color: var(--bg-white);
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow);
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .chart-title {
        font-size: 18px;
        font-weight: 600;
    }

    /* Tables Section */
    .tables-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .table-card {
        background-color: var(--bg-white);
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow);
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .table-title {
        font-size: 18px;
        font-weight: 600;
    }

    .see-all {
        color: var(--primary);
        text-decoration: none;
        font-size: 14px;
    }

    .agendamento-list {
        list-style: none;
    }

    .agendamento-item {
        padding: 15px 0;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .agendamento-item:last-child {
        border-bottom: none;
    }

    .agendamento-info h4 {
        font-size: 14px;
        margin-bottom: 5px;
    }

    .agendamento-info p {
        font-size: 12px;
        color: var(--text-light);
    }

    .agendamento-time {
        background-color: var(--primary-light);
        color: var(--primary-dark);
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    /* Responsividade */
    @media (max-width: 1200px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .charts-section {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .dashboard-content {
            padding: 20px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .tables-section {
            grid-template-columns: 1fr;
        }

        .search-container {
            width: 200px;
        }

        .header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
    }
</style>

<div class="dashboard-content">
    <div class="header">
        <h1 class="page-title">Dashboard Secretaria</h1>

        <div class="search-container">
            <input type="text" class="input" placeholder="Pesquisar...">
            <button class="btn-search"> 🔍 </button>
        </div>

        <div class="user-info">
            <div class="user-avatar"><?= $iniciais ?></div>
            <div>
                <div><?= htmlspecialchars($nome_usuario) ?></div>
                <small><?= htmlspecialchars($email_usuario) ?></small>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-users">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $usuarios['total'] ?></div>
                <div class="stat-label">Total de Clientes</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-pets">
                <i class="fas fa-paw"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $animais['total'] ?></div>
                <div class="stat-label">Animais Cadastrados</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-calendar">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $agendamentos['total'] ?></div>
                <div class="stat-label">Agendamentos Confirmados</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon icon-today">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $consultas_hoje['total'] ?></div>
                <div class="stat-label">Consultas Hoje</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Agendamentos dos Últimos 30 Dias</h3>
            </div>
            <canvas id="agendamentosChart"></canvas>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Distribuição por Espécie</h3>
            </div>
            <canvas id="especiesChart"></canvas>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="tables-section">
        <div class="table-card">
            <div class="table-header">
                <h3 class="table-title">Próximos Agendamentos</h3>
                <a href="#" class="see-all">Ver todos</a>
            </div>
            <ul class="agendamento-list">
                <?php foreach ($proximos_agendamentos as $agendamento): ?>
                    <li class="agendamento-item">
                        <div class="agendamento-info">
                            <h4><?= htmlspecialchars($agendamento['animal']) ?></h4>
                            <p><?= htmlspecialchars($agendamento['cliente']) ?></p>
                        </div>
                        <div class="agendamento-time">
                            <?= date('d/m', strtotime($agendamento['data_hora'])) ?> •
                            <?= substr($agendamento['hora_inicio'], 0, 5) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        

        <div class="table-card">
            <div class="table-header">
                <h3 class="table-title">Estatísticas Rápidas</h3>
            </div>
            <ul class="agendamento-list">
                <li class="agendamento-item">
                    <div class="agendamento-info">
                        <h4>Agendamentos Pendentes</h4>
                    </div>
                    <div class="agendamento-time"><?= $agendamentos_pendentes['total'] ?></div>
                </li>
                <li class="agendamento-item">
                    <div class="agendamento-info">
                        <h4>Consultas Realizadas (Mês)</h4>
                    </div>
                    <div class="agendamento-time"><?= count($agendamentos_mes) ?></div>
                </li>
                <li class="agendamento-item">
                    <div class="agendamento-info">
                        <h4>Espécies Cadastradas</h4>
                    </div>
                    <div class="agendamento-time"><?= count($especies_data) ?></div>
                </li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Gráfico de Agendamentos
    const agendamentosCtx = document.getElementById('agendamentosChart').getContext('2d');
    const agendamentosChart = new Chart(agendamentosCtx, {
        type: 'line',
        data: {
            labels: [<?php
            $labels = [];
            foreach ($agendamentos_mes as $ag) {
                $labels[] = "'" . date('d/m', strtotime($ag['dia'])) . "'";
            }
            echo implode(', ', $labels);
            ?>],
            datasets: [{
                label: 'Agendamentos por Dia',
                data: [<?php
                $values = [];
                foreach ($agendamentos_mes as $ag) {
                    $values[] = $ag['total'];
                }
                echo implode(', ', $values);
                ?>],
                borderColor: '#2E8B57',
                backgroundColor: 'rgba(46, 139, 87, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
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
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Gráfico de Espécies
    const especiesCtx = document.getElementById('especiesChart').getContext('2d');
    const especiesChart = new Chart(especiesCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php
            $labels = [];
            foreach ($especies_data as $especie) {
                $labels[] = "'" . $especie['nome'] . "'";
            }
            echo implode(', ', $labels);
            ?>],
            datasets: [{
                data: [<?php
                $values = [];
                foreach ($especies_data as $especie) {
                    $values[] = $especie['total'];
                }
                echo implode(', ', $values);
                ?>],
                backgroundColor: [
                    '#2E8B57', '#4682B4', '#FF6B6B', '#9b59b6', '#e67e22',
                    '#1abc9c', '#d35400', '#34495e', '#16a085', '#27ae60'
                ]
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

    // Sistema de Pesquisa
    const search = document.querySelector('.search-container');
    const btnSearch = document.querySelector('.btn-search');
    const input = document.querySelector('.input');

    btnSearch.addEventListener('click', () => {
        search.classList.toggle('active');
        input.focus();
    });

    // Ajustar tamanho de imagens (caso necessário)
    function adjustImageSize() {
        // Implementação se necessário
    }

    window.addEventListener("load", adjustImageSize);
    window.addEventListener("resize", adjustImageSize);
</script>

<?php
// Incluir footer
include 'footer.php';
?>