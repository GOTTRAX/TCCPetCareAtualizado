<?php 
session_start(); 
include("../conexao.php");

// Buscar dados do banco
$especies = [];
$usuarios = [];
$animais = [];

try {
    // Buscar espécies
    $stmt = $pdo->query("SELECT id, nome FROM Especies");
    $especies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar usuários (donos)
    $stmt = $pdo->query("SELECT id, nome, email, telefone FROM Usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar animais com informações completas
    $stmt = $pdo->query("
        SELECT a.*, e.nome as especie_nome, u.nome as dono_nome, u.email, u.telefone 
        FROM Animais a 
        INNER JOIN Especies e ON a.especie_id = e.id 
        INNER JOIN Usuarios u ON a.usuario_id = u.id
    ");
    $animais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada animal, buscar seu histórico de consultas
    foreach ($animais as &$animal) {
        $stmt = $pdo->prepare("
            SELECT c.data_consulta as data, 'Consulta' as tipo, 
                   COALESCE(c.diagnostico, 'Consulta realizada') as descricao
            FROM Consultas c 
            WHERE c.animal_id = :animal_id
            UNION
            SELECT a.data_hora as data, 'Agendamento' as tipo, 
                   CONCAT('Agendamento - ', a.status) as descricao
            FROM Agendamentos a 
            WHERE a.animal_id = :animal_id
            ORDER BY data DESC
        ");
        $stmt->execute(['animal_id' => $animal['id']]);
        $animal['historico'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    die("Erro ao buscar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Veterinário - Fichas dos Animais</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --verde-principal: #2E8B57;
            --verde-claro: #8FBC8F;
            --verde-escuro: #1a5c38;
            --azul-principal: #4682B4;
            --azul-claro: #87CEEB;
            --azul-escuro: #2c5aa0;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, var(--verde-principal), var(--azul-principal));
        }
        .btn-primary {
            background-color: var(--azul-principal);
            border-color: var(--azul-escuro);
        }
        .btn-primary:hover {
            background-color: var(--azul-escuro);
            border-color: var(--azul-principal);
        }
        .btn-success {
            background-color: var(--verde-principal);
            border-color: var(--verde-escuro);
        }
        .btn-success:hover {
            background-color: var(--verde-escuro);
            border-color: var(--verde-principal);
        }
        .animal-card {
            transition: transform 0.3s;
            height: 100%;
            border: 1px solid var(--verde-claro);
            border-radius: 10px;
            overflow: hidden;
        }
        .animal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(46, 139, 87, 0.15);
            border-color: var(--verde-principal);
        }
        .animal-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
            border-bottom: 2px solid var(--verde-claro);
        }
        .filter-section {
            background: linear-gradient(to right, #e8f5e9, #e3f2fd);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--azul-claro);
        }
        .chart-container {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid var(--azul-claro);
        }
        .card-title {
            color: var(--verde-escuro);
            font-weight: bold;
        }
        .card-text strong {
            color: var(--azul-escuro);
        }
        h1, h5 {
            color: var(--verde-escuro);
        }
        .modal-header {
            background: linear-gradient(135deg, var(--verde-principal), var(--azul-principal));
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-heart-pulse-fill me-2" viewBox="0 0 16 16">
                    <path d="M1.475 9C2.702 10.6 4.256 11.823 6 12.55V9H1.475Z"/>
                    <path d="M7.5 9v3.55c1.744-.727 3.298-1.95 4.525-3.55H7.5Z"/>
                    <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8Zm1.475 1C2.702 10.6 4.256 11.823 6 12.55V9H1.475Zm7.5 0v3.55c1.744-.727 3.298-1.95 4.525-3.55H9Z"/>
                </svg>
                Sistema Veterinário
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-list me-1" viewBox="0 0 16 16">
                                <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
                                <path d="M5 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 5 8zm0-2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0 5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm-1-5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zM4 8a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm0 2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
                            </svg>
                            Fichas dos Animais
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard2-data me-1" viewBox="0 0 16 16">
                                <path d="M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5h3Z"/>
                                <path d="M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5v-12Z"/>
                                <path d="M10 7a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0V7Zm-6 4a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0v-1Zm4-3a1 1 0 0 0-1 1v3a1 1 0 1 0 2 0V9a1 1 0 0 0-1-1Z"/>
                            </svg>
                            Prontuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-event me-1" viewBox="0 0 16 16">
                                <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                            </svg>
                            Agendamentos
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-paw-fill me-2" viewBox="0 0 16 16">
                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/>
                <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m5.5-4a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 0 1H6v.5a.5.5 0 0 1-1 0V6h-.5a.5.5 0 0 1 0-1H5V4.5a.5.5 0 0 1 .5-.5m4 0a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 0 1H10v.5a.5.5 0 0 1-1 0V6h-.5a.5.5 0 0 1 0-1H9V4.5a.5.5 0 0 1 .5-.5M2.5 11a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 0 1H3v.5a.5.5 0 0 1-1 0V13h-.5a.5.5 0 0 1 0-1H2v-.5a.5.5 0 0 1 .5-.5m9 0a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 0 1H12v.5a.5.5 0 0 1-1 0V13h-.5a.5.5 0 0 1 0-1H12v-.5a.5.5 0 0 1 .5-.5"/>
            </svg>
            Fichas dos Animais
        </h1>

        <!-- Filtros -->
        <div class="row filter-section">
            <div class="col-md-3">
                <label for="sexoFilter" class="form-label">Filtrar por Sexo</label>
                <select class="form-select" id="sexoFilter">
                    <option value="todos">Todos</option>
                    <option value="Macho">Machos</option>
                    <option value="Fêmea">Fêmeas</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="especieFilter" class="form-label">Filtrar por Espécie</label>
                <select class="form-select" id="especieFilter">
                    <option value="todos">Todas</option>
                    <!-- As opções serão preenchidas via JavaScript -->
                </select>
            </div>
            <div class="col-md-3">
                <label for="donoFilter" class="form-label">Filtrar por Dono</label>
                <select class="form-select" id="donoFilter">
                    <option value="todos">Todos</option>
                    <!-- As opções serão preenchidas via JavaScript -->
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-success w-100" id="limparFiltros">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-counterclockwise me-1" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/>
                        <path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/>
                    </svg>
                    Limpar Filtros
                </button>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-gender-ambiguous me-1" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11.5 1a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1.707l-2.91 2.91a.5.5 0 0 1-.686-.687L10.293 1H8.5a.5.5 0 0 1 0-1h3a.5.5 0 0 1 .5.5M11 7.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V8.707l-3.45 3.45a.5.5 0 0 1-.707-.707L13.293 8H11.5a.5.5 0 0 1-.5-.5"/>
                            <path fill-rule="evenodd" d="M5.5 13.5A3.5 3.5 0 1 1 2 10a3.5 3.5 0 0 1 3.5 3.5m-1-3.5a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/>
                        </svg>
                        Distribuição por Sexo
                    </h5>
                    <canvas id="sexoChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-diagram-3 me-1" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H14a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 2 7h5.5V6A1.5 1.5 0 0 1 6 4.5v-1zM8.5 5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1zM0 11.5A1.5 1.5 0 0 1 1.5 10h1A1.5 1.5 0 0 1 4 11.5v1A1.5 1.5 0 0 1 2.5 14h-1A1.5 1.5 0 0 1 0 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5A1.5 1.5 0 0 1 7.5 10h1a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                        </svg>
                        Distribuição por Espécie
                    </h5>
                    <canvas id="especieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Cards dos Animais -->
        <div class="row mt-4" id="animaisContainer">
            <!-- Os cards serão preenchidos via JavaScript -->
        </div>
    </div>

    <!-- Modal para visualizar prontuário -->
    <div class="modal fade" id="prontuarioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-file-medical me-2" viewBox="0 0 16 16">
                            <path d="M8.5 4.5a.5.5 0 0 0-1 0v.634l-.549-.317a.5.5 0 1 0-.5.866L7 6l-.549.317a.5.5 0 1 0 .5.866l.549-.317V7.5a.5.5 0 0 0 1 0v-.634l.549.317a.5.5 0 1 0 .5-.866L9 6l.549-.317a.5.5 0 1 0-.5-.866l-.549.317V4.5zM5.5 9a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zm0 2a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z"/>
                            <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                        </svg>
                        Prontuário do Animal
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="prontuarioContent">
                    <!-- Conteúdo do prontuário será preenchido via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Converter dados PHP para JavaScript
        const especies = <?php echo json_encode($especies); ?>;
        const usuarios = <?php echo json_encode($usuarios); ?>;
        const animais = <?php echo json_encode($animais); ?>;

        // Preencher filtros
        function preencherFiltros() {
            const especieFilter = document.getElementById('especieFilter');
            especies.forEach(especie => {
                const option = document.createElement('option');
                option.value = especie.id;
                option.textContent = especie.nome;
                especieFilter.appendChild(option);
            });

            const donoFilter = document.getElementById('donoFilter');
            usuarios.forEach(usuario => {
                const option = document.createElement('option');
                option.value = usuario.id;
                option.textContent = usuario.nome;
                donoFilter.appendChild(option);
            });
        }

        // Calcular idade a partir da data de nascimento
        function calcularIdade(dataNasc) {
            const hoje = new Date();
            const nascimento = new Date(dataNasc);
            let idade = hoje.getFullYear() - nascimento.getFullYear();
            const mes = hoje.getMonth() - nascimento.getMonth();
            
            if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
                idade--;
            }
            
            return idade;
        }

        // Formatar data para exibição
        function formatarData(data) {
            if (!data) return 'N/A';
            return new Date(data).toLocaleDateString('pt-BR');
        }

        // Renderizar cards dos animais
        function renderizarAnimais(animaisFiltrados = animais) {
            const container = document.getElementById('animaisContainer');
            container.innerHTML = '';
            
            if (animaisFiltrados.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-search text-muted mb-3" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                        <h4 class="text-muted">Nenhum animal encontrado</h4>
                        <p class="text-muted">Tente ajustar os filtros para ver mais resultados.</p>
                    </div>
                `;
                return;
            }
            
            animaisFiltrados.forEach(animal => {
                const idade = calcularIdade(animal.datanasc);
                const fotoUrl = animal.foto || 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60';
                
                const card = document.createElement('div');
                card.className = 'col-md-4 mb-4';
                card.innerHTML = `
                    <div class="card animal-card">
                        <img src="${fotoUrl}" class="animal-img card-img-top" alt="${animal.nome}">
                        <div class="card-body">
                            <h5 class="card-title">${animal.nome}</h5>
                            <p class="card-text">
                                <strong>Espécie:</strong> ${animal.especie_nome}<br>
                                <strong>Raça:</strong> ${animal.raca || 'Não informada'}<br>
                                <strong>Idade:</strong> ${idade} anos<br>
                                <strong>Porte:</strong> ${animal.porte || 'Não informado'}<br>
                                <strong>Sexo:</strong> ${animal.sexo || 'Não informado'}<br>
                                <strong>Dono:</strong> ${animal.dono_nome}
                            </p>
                            <button class="btn btn-primary btn-sm ver-prontuario" data-animal-id="${animal.id}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-medical me-1" viewBox="0 0 16 16">
                                    <path d="M8.5 4.5a.5.5 0 0 0-1 0v.634l-.549-.317a.5.5 0 1 0-.5.866L7 6l-.549.317a.5.5 0 1 0 .5.866l.549-.317V7.5a.5.5 0 0 0 1 0v-.634l.549.317a.5.5 0 1 0 .5-.866L9 6l.549-.317a.5.5 0 1 0-.5-.866l-.549.317V4.5zM5.5 9a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zm0 2a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z"/>
                                    <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                                </svg>
                                Ver Prontuário
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
            
            // Adicionar event listeners aos botões de prontuário
            document.querySelectorAll('.ver-prontuario').forEach(btn => {
                btn.addEventListener('click', function() {
                    const animalId = this.getAttribute('data-animal-id');
                    mostrarProntuario(animalId);
                });
            });
        }

        // Mostrar prontuário no modal
        function mostrarProntuario(animalId) {
            const animal = animais.find(a => a.id == animalId);
            const idade = calcularIdade(animal.datanasc);
            const fotoUrl = animal.foto || 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60';
            
            const prontuarioContent = document.getElementById('prontuarioContent');
            prontuarioContent.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <img src="${fotoUrl}" class="img-fluid rounded" alt="${animal.nome}">
                    </div>
                    <div class="col-md-8">
                        <h4>${animal.nome}</h4>
                        <p><strong>Espécie:</strong> ${animal.especie_nome}</p>
                        <p><strong>Raça:</strong> ${animal.raca || 'Não informada'}</p>
                        <p><strong>Data de Nascimento:</strong> ${formatarData(animal.datanasc)} (${idade} anos)</p>
                        <p><strong>Porte:</strong> ${animal.porte || 'Não informado'}</p>
                        <p><strong>Sexo:</strong> ${animal.sexo || 'Não informado'}</p>
                        <p><strong>Dono:</strong> ${animal.dono_nome}</p>
                        <p><strong>Contato:</strong> ${animal.email} | ${animal.telefone}</p>
                    </div>
                </div>
                <hr>
                <h5>Histórico de Consultas</h5>
                ${animal.historico && animal.historico.length > 0 ? 
                    animal.historico.map(consulta => `
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6 class="card-title">${formatarData(consulta.data)} - ${consulta.tipo}</h6>
                                <p class="card-text">${consulta.descricao}</p>
                            </div>
                        </div>
                    `).join('') : 
                    '<p>Nenhum histórico de consulta registrado.</p>'
                }
                <div class="mt-3">
                    <h6>Adicionar nova observação</h6>
                    <textarea class="form-control" rows="3" placeholder="Registrar nova observação no prontuário..."></textarea>
                    <button class="btn btn-success mt-2">Salvar Observação</button>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('prontuarioModal'));
            modal.show();
        }

        // Criar gráficos
        function criarGraficos() {
            // Gráfico de distribuição por sexo
            const sexoCount = {
                'Macho': animais.filter(a => a.sexo === 'Macho').length,
                'Fêmea': animais.filter(a => a.sexo === 'Fêmea').length
            };
            
            const sexoCtx = document.getElementById('sexoChart').getContext('2d');
            new Chart(sexoCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Machos', 'Fêmeas'],
                    datasets: [{
                        data: [sexoCount['Macho'], sexoCount['Fêmea']],
                        backgroundColor: ['#4682B4', '#2E8B57'],
                        borderWidth: 1
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
            
            // Gráfico de distribuição por espécie
            const especieCount = {};
            especies.forEach(especie => {
                especieCount[especie.nome] = animais.filter(a => a.especie_id == especie.id).length;
            });
            
            const especieCtx = document.getElementById('especieChart').getContext('2d');
            new Chart(especieCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(especieCount),
                    datasets: [{
                        label: 'Quantidade por Espécie',
                        data: Object.values(especieCount),
                        backgroundColor: ['#2E8B57', '#4682B4', '#3CB371', '#20B2AA', '#48D1CC'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
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
        }

        // Aplicar filtros
        function aplicarFiltros() {
            const sexoVal = document.getElementById('sexoFilter').value;
            const especieVal = document.getElementById('especieFilter').value;
            const donoVal = document.getElementById('donoFilter').value;
            
            let animaisFiltrados = animais;
            
            if (sexoVal !== 'todos') {
                animaisFiltrados = animaisFiltrados.filter(a => a.sexo === sexoVal);
            }
            
            if (especieVal !== 'todos') {
                animaisFiltrados = animaisFiltrados.filter(a => a.especie_id == especieVal);
            }
            
            if (donoVal !== 'todos') {
                animaisFiltrados = animaisFiltrados.filter(a => a.usuario_id == donoVal);
            }
            
            renderizarAnimais(animaisFiltrados);
        }

        // Inicializar a página
        document.addEventListener('DOMContentLoaded', function() {
            preencherFiltros();
            renderizarAnimais();
            criarGraficos();
            
            // Event listeners para os filtros
            document.getElementById('sexoFilter').addEventListener('change', aplicarFiltros);
            document.getElementById('especieFilter').addEventListener('change', aplicarFiltros);
            document.getElementById('donoFilter').addEventListener('change', aplicarFiltros);
            
            document.getElementById('limparFiltros').addEventListener('click', function() {
                document.getElementById('sexoFilter').value = 'todos';
                document.getElementById('especieFilter').value = 'todos';
                document.getElementById('donoFilter').value = 'todos';
                renderizarAnimais();
            });
        });
    </script>
</body>
</html>