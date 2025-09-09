<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

require '../conexao.php';

$usuario_id = $_SESSION['id'];
$tipo = $_SESSION['tipo_usuario'];

// Buscar animais do cliente
$animais = [];
if ($tipo === 'Cliente') {
    $stmt = $pdo->prepare("SELECT id, nome, foto FROM Animais WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $animais = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar serviços em vez de veterinários - REMOVIDA A CLAUSULA WHERE ativo = 1
$servicos = [];
$stmt = $pdo->query("SELECT id, nome, preco_normal, preco_feriado FROM Servicos ORDER BY nome");
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar horários de funcionamento da clínica
$horarios_clinica = [];
try {
    $stmt = $pdo->query("SELECT * FROM Dias_Trabalhados WHERE ativo = 1");
    $horarios_clinica = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Se não existir a tabela, usar valores padrão
    $horarios_clinica = [];
}

// Converter para formato mais fácil de usar (CORRIGIDO)
$horarios_por_dia = [];
foreach ($horarios_clinica as $horario) {
    // Garantir que o nome do dia está com a primeira letra maiúscula
    $dia = ucfirst(strtolower($horario['dia_semana']));
    $horarios_por_dia[$dia] = $horario;
}

// Função para obter horários disponíveis - CORRIGIDA
function getHorariosDisponiveis($data, $pdo, $horarios_por_dia) {
    // Verificar se é um dia válido
    $dia_semana = date('N', strtotime($data)); // 1=Segunda, 7=Domingo
    $nomes_dias = ['', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
    $dia_nome = $nomes_dias[$dia_semana];
    
    // Verificar se a clínica funciona neste dia
    if (!isset($horarios_por_dia[$dia_nome]) || $data < date('Y-m-d')) {
        return []; // Não funciona ou data passada
    }
    
    $horario_dia = $horarios_por_dia[$dia_nome];
    $horarios_disponiveis = [];
    
    // Gerar todos os horários possíveis
    $inicio = new DateTime($horario_dia['horario_abertura']);
    $fim = new DateTime($horario_dia['horario_fechamento']);
    
    // Verificar agendamentos existentes para esta data - CONSIDERAR TODOS OS STATUS
    $stmt = $pdo->prepare("SELECT hora_inicio FROM Agendamentos WHERE data_hora = ? AND status != 'cancelado'");
    $stmt->execute([$data]);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $horarios_ocupados = [];
    foreach ($agendamentos as $agendamento) {
        $horarios_ocupados[$agendamento['hora_inicio']] = true;
    }
    
    // Gerar horários disponíveis
    while ($inicio < $fim) {
        $hora_atual = $inicio->format('H:i');
        
        // Verificar se está no horário de almoço
        $em_almoco = false;
        if (!empty($horario_dia['horario_almoco_inicio']) && !empty($horario_dia['horario_almoco_fim'])) {
            $almoco_inicio = new DateTime($horario_dia['horario_almoco_inicio']);
            $almoco_fim = new DateTime($horario_dia['horario_almoco_fim']);
            $hora_atual_obj = new DateTime($hora_atual);
            
            if ($hora_atual_obj >= $almoco_inicio && $hora_atual_obj < $almoco_fim) {
                $em_almoco = true;
            }
        }
        
        // Se não está ocupado e não é horário de almoço, adicionar aos disponíveis
        if (!isset($horarios_ocupados[$hora_atual]) && !$em_almoco) {
            $horarios_disponiveis[] = $hora_atual;
        }
        
        $inicio->modify('+1 hour');
    }
    
    return $horarios_disponiveis;
}

// Incluir menu
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário de Agendamentos</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>

    <style>
        /* Seu CSS existente mantido igual */
        :root {
            --primary: #3a86ff;
            --secondary: #8338ec;
            --accent: #ff006e;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #2e8b57;
            --danger: #b22222;
            --warning: #ffbe0b;
            --info: #4cc9f0;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --border-radius: 12px;
            --box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
        }

        header {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray);
        }

        .user-info i {
            font-size: 18px;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            padding: 16px 20px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        #calendar {
            height: 700px;
        }

        .fc {
            height: 100%;
        }

        .fc-toolbar-title {
            font-weight: 700;
            color: var(--dark);
        }

        .fc-button {
            background: var(--primary) !important;
            border: none !important;
            transition: var(--transition);
        }

        .fc-button:hover {
            background: var(--secondary) !important;
            transform: translateY(-2px);
        }

        .fc-daygrid-day-number {
            color: var(--dark);
            font-weight: 600;
        }

        .fc-event {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 14px;
        }

        .fc-day-past .fc-daygrid-day-number {
            color: var(--gray) !important;
            opacity: 0.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(58, 134, 255, 0.4);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .btn-success {
            background: linear-gradient(to right, var(--success), #34a853);
        }

        .btn-danger {
            background: linear-gradient(to right, var(--danger), #ea4335);
        }

        .btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .animals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .animal-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .animal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .animal-card.selected {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.3);
        }

        .animal-image {
            height: 80px;
            background: linear-gradient(to right, #f3f4f6, #e5e7eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 32px;
        }

        .animal-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .animal-name {
            padding: 10px 5px;
            font-weight: 600;
            font-size: 14px;
        }

        .no-animals {
            text-align: center;
            padding: 30px;
            background: var(--light);
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }

        .no-animals i {
            font-size: 48px;
            color: var(--gray);
            margin-bottom: 15px;
        }

        .no-animals p {
            margin-bottom: 20px;
            color: var(--gray);
        }

        .solicitacao {
            padding: 16px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .solicitacao:last-child {
            border-bottom: none;
        }

        .solicitacao-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .solicitacao-actions {
            display: flex;
            gap: 10px;
        }

        .solicitacao-animal {
            font-weight: 600;
            color: var(--dark);
        }

        .solicitacao-data {
            color: var(--gray);
            font-size: 14px;
        }

        .solicitacao-observacoes {
            font-style: italic;
            color: var(--gray);
        }

        .hidden {
            display: none;
        }

        .horario-indisponivel {
            color: var(--danger);
            font-style: italic;
            font-size: 14px;
            margin-top: 10px;
        }

        .preco-info {
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
        }

        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-paw"></i>
                <span>PetAgenda</span>
            </div>
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span><?= htmlspecialchars($_SESSION['nome']) ?> (<?= $tipo ?>)</span>
            </div>
        </header>

        <main class="card">
            <div class="card-header">
                <i class="far fa-calendar-alt"></i>
                <span>Calendário de Agendamentos</span>
            </div>
            <div class="card-body">
                <div id='calendar'></div>
            </div>
        </main>

        <aside>
            <?php if ($tipo === 'Cliente'): ?>
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-paw"></i>
                        <span>Meus Animais</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($animais) > 0): ?>
                            <p class="form-group">Selecione um animal para agendar:</p>
                            <div class="animals-grid" id="animais-container">
                                <?php foreach ($animais as $animal): ?>
                                    <div class="animal-card" data-id="<?= $animal['id'] ?>">
                                        <div class="animal-image">
                                            <?php if (!empty($animal['foto'])): ?>
                                                <img src="../uploads/pets/<?= htmlspecialchars($animal['foto']) ?>" alt="<?= htmlspecialchars($animal['nome']) ?>">
                                            <?php else: ?>
                                                <i class="fas fa-paw"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="animal-name"><?= htmlspecialchars($animal['nome']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-animals">
                                <i class="fas fa-exclamation-circle"></i>
                                <p>Você não possui animais cadastrados.</p>
                                <a href="../Cliente/perfil.php" class="btn">
                                    <i class="fas fa-plus"></i> Cadastrar Animal
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card" id="formulario-agendamento">
                    <div class="card-header">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Agendar Serviço</span>
                    </div>
                    <div class="card-body">
                        <form action="salvar_agendamento.php" method="POST">
                            <input type="hidden" name="animal_id" id="animal_selecionado" required>
                            
                            <div class="form-group">
                                <label for="servico_id"><i class="fas fa-concierge-bell"></i> Serviço:</label>
                                <select name="servico_id" id="servico_id" class="form-control" required>
                                    <option value="">Selecione o serviço</option>
                                    <?php foreach ($servicos as $servico): ?>
                                        <option value="<?= $servico['id'] ?>" data-preco-normal="<?= $servico['preco_normal'] ?>" data-preco-feriado="<?= $servico['preco_feriado'] ?>">
                                            <?= htmlspecialchars($servico['nome']) ?> 
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="info-preco" class="preco-info"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="data_hora"><i class="far fa-calendar"></i> Data do Serviço:</label>
                                <input type="date" name="data_hora" id="data_hora" class="form-control" readonly required>
                                <div id="info-data" class="horario-indisponivel"></div>
                            </div>

                            <div class="form-group">
                                <label for="hora_inicio"><i class="far fa-clock"></i> Horário de Início:</label>
                                <select name="hora_inicio" id="hora_inicio" class="form-control" required disabled>
                                    <option value="">Selecione a data primeiro</option>
                                </select>
                                <input type="hidden" name="hora_final" id="hora_final">
                            </div>

                            <div class="form-group">
                                <label for="observacoes"><i class="far fa-sticky-note"></i> Observações (opcional):</label>
                                <textarea name="observacoes" id="observacoes" class="form-control" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-block" id="btn-agendar" disabled>
                                <i class="fas fa-calendar-check"></i> Agendar Serviço
                            </button>
                        </form>
                    </div>
                </div>

            <?php elseif ($tipo === 'Veterinario' || $tipo === 'Secretaria'): ?>
                <div class="card" id="solicitacoes">
                    <div class="card-header">
                        <i class="fas fa-tasks"></i>
                        <span>Solicitações Pendentes</span>
                    </div>
                    <div class="card-body">
                        <div id="lista-solicitacoes">Carregando...</div>
                    </div>
                </div>
            <?php endif; ?>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    let selectedDate = null;
    
    // Inicializar calendário
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        events: 'get_agendamentos.php',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia'
        },
        <?php if ($tipo === 'Cliente'): ?>
        dateClick: function(info) {
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            const dataSelecionada = new Date(info.dateStr);
            
            // Não permitir selecionar datas passadas
            if (dataSelecionada < hoje) {
                alert('Não é possível agendar para datas passadas.');
                return;
            }
            
            selectedDate = info.dateStr;
            document.getElementById('data_hora').value = info.dateStr;
            
            // Destacar a data clicada no calendário
            document.querySelectorAll('.fc-day').forEach(day => {
                day.classList.remove('fc-day-selected');
            });
            info.dayEl.classList.add('fc-day-selected');
            
            // Atualizar informações de preço
            atualizarInformacoesPreco();
            
            // Buscar horários disponíveis para esta data
            buscarHorariosDisponiveis(info.dateStr);
        },
        dayCellDidMount: function(info) {
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            const dataCelula = new Date(info.date);
            
            // Desabilitar visualmente dias passados
            if (dataCelula < hoje) {
                info.el.style.opacity = '0.5';
                info.el.style.cursor = 'not-allowed';
            }
        }
        <?php endif; ?>
    });
    
    calendar.render();

    // Seleção de animais
    const animalCards = document.querySelectorAll('.animal-card');
    const animalSelecionadoInput = document.getElementById('animal_selecionado');
    
    animalCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remover seleção anterior
            animalCards.forEach(c => c.classList.remove('selected'));
            
            // Adicionar seleção atual
            this.classList.add('selected');
            
            // Definir o animal selecionado no formulário
            animalSelecionadoInput.value = this.getAttribute('data-id');
            verificarFormularioCompleto();
        });
    });

    // Atualizar informações de preço quando o serviço ou data mudar
    function atualizarInformacoesPreco() {
        const servicoSelect = document.getElementById('servico_id');
        const dataInput = document.getElementById('data_hora');
        const infoPreco = document.getElementById('info-preco');
        
        if (servicoSelect.value && dataInput.value) {
            const selectedOption = servicoSelect.options[servicoSelect.selectedIndex];
            const precoNormal = selectedOption.getAttribute('data-preco-normal');
            const precoFeriado = selectedOption.getAttribute('data-preco-feriado');
            
            // Verificar se é feriado (exemplo simples)
            const data = new Date(dataInput.value);
            const isFeriado = false; // Aqui você pode implementar a lógica de feriados
            
            const preco = isFeriado ? precoFeriado : precoNormal;
            infoPreco.textContent = `Preço: R$ ${parseFloat(preco).toFixed(2)}`;
        } else {
            infoPreco.textContent = '';
        }
    }

    // Função para buscar horários disponíveis
    function buscarHorariosDisponiveis(data) {
        fetch('get_horarios_disponiveis.php?data=' + data)
            .then(response => response.json())
            .then(horarios => {
                const select = document.getElementById('hora_inicio');
                const infoData = document.getElementById('info-data');
                
                select.innerHTML = '';
                select.disabled = false;
                
                if (horarios.length === 0) {
                    select.innerHTML = '<option value="">Nenhum horário disponível</option>';
                    infoData.textContent = 'Não há horários disponíveis para esta data.';
                    document.getElementById('btn-agendar').disabled = true;
                    return;
                }
                
                infoData.textContent = '';
                
                horarios.forEach(horario => {
                    const option = document.createElement('option');
                    option.value = horario;
                    option.textContent = horario;
                    select.appendChild(option);
                });
                
                verificarFormularioCompleto();
            })
            .catch(error => {
                console.error('Erro ao buscar horários:', error);
                document.getElementById('hora_inicio').innerHTML = '<option value="">Erro ao carregar horários</option>';
            });
    }

    // Definir hora final automaticamente
    document.getElementById('hora_inicio').addEventListener('change', function() {
        if (this.value) {
            const [h, m] = this.value.split(':');
            const novaHora = String(parseInt(h) + 1).padStart(2, '0') + ':' + m;
            document.getElementById('hora_final').value = novaHora;
            verificarFormularioCompleto();
        }
    });

    // Verificar se o formulário está completo
    function verificarFormularioCompleto() {
        const animalSelecionado = document.getElementById('animal_selecionado').value;
        const servicoSelecionado = document.getElementById('servico_id').value;
        const dataSelecionada = document.getElementById('data_hora').value;
        const horaSelecionada = document.getElementById('hora_inicio').value;
        
        const btnAgendar = document.getElementById('btn-agendar');
        btnAgendar.disabled = !(animalSelecionado && servicoSelecionado && dataSelecionada && horaSelecionada);
    }

    // Verificar seleção de serviço e data
    document.getElementById('servico_id').addEventListener('change', function() {
        atualizarInformacoesPreco();
        verificarFormularioCompleto();
    });

    <?php if ($tipo === 'Veterinario' || $tipo === 'Secretaria'): ?>
    function carregarSolicitacoes() {
        fetch('get_solicitacoes.php')
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('lista-solicitacoes');
                container.innerHTML = '';
                
                if (data.length === 0) {
                    container.innerHTML = '<p class="text-center">Sem solicitações pendentes.</p>';
                    return;
                }
                
                data.forEach(s => {
                    const div = document.createElement('div');
                    div.classList.add('solicitacao');
                    div.innerHTML = `
                        <div class="solicitacao-header">
                            <div class="solicitacao-info">
                                <strong>${s.animal_nome}</strong> - ${s.servico_nome}
                                <br><small>Cliente: ${s.cliente_nome}</small>
                            </div>
                            <div class="solicitacao-actions">
                                <button class="btn btn-success aceitar" data-id="${s.id}">
                                    <i class="fas fa-check"></i> Aceitar
                                </button>
                                <button class="btn btn-danger recusar" data-id="${s.id}">
                                    <i class="fas fa-times"></i> Recusar
                                </button>
                            </div>
                        </div>
                        <div class="solicitacao-data">
                            Data: ${s.data_hora} às ${s.hora_inicio}
                        </div>
                        ${s.observacoes ? `<div class="solicitacao-observacoes">Observações: ${s.observacoes}</div>` : ''}
                    `;
                    container.appendChild(div);
                });
            });
    }

    // Captura clique dos botões e envia status correto
    document.addEventListener("click", async (e) => {
        if (e.target.classList.contains("aceitar") || e.target.closest('.aceitar')) {
            const button = e.target.classList.contains("aceitar") ? e.target : e.target.closest('.aceitar');
            const id = button.dataset.id;
            const status = "confirmado";

            try {
                const resposta = await fetch("atualizar_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`,
                    credentials: "same-origin"
                });

                const dados = await resposta.json();

                if (dados.status === "ok") {
                    alert(`Agendamento confirmado com sucesso!`);
                    carregarSolicitacoes();
                    calendar.refetchEvents();
                } else {
                    alert(dados.erro || "Erro ao atualizar agendamento.");
                }
            } catch (erro) {
                console.error("Erro na requisição:", erro);
                alert("Ocorreu um erro na conexão.");
            }
        }
        
        if (e.target.classList.contains("recusar") || e.target.closest('.recusar')) {
            const button = e.target.classList.contains("recusar") ? e.target : e.target.closest('.recusar');
            const id = button.dataset.id;
            const status = "cancelado";

            try {
                const resposta = await fetch("atualizar_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${encodeURIComponent(id)}&status=${encodeURIComponent(status)}`,
                    credentials: "same-origin"
                });

                const dados = await resposta.json();

                if (dados.status === "ok") {
                    alert(`Agendamento cancelado com sucesso!`);
                    carregarSolicitacoes();
                    calendar.refetchEvents();
                } else {
                    alert(dados.erro || "Erro ao atualizar agendamento.");
                }
            } catch (erro) {
                console.error("Erro na requisição:", erro);
                alert("Ocorreu um erro na conexão.");
            }
        }
    });

    carregarSolicitacoes();
    <?php endif; ?>
});
    </script>
</body>
</html>