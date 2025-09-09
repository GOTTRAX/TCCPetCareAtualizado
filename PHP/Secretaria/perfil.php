<?php
include '../conexao.php';

// Verificar conexão
if (!$pdo) {
    die("Erro: Não foi possível conectar ao banco de dados");
}

// Configurar tratamento de erros
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Processar o formulário quando for enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_horarios'])) {
    try {
        // Limpar a tabela antes de inserir os novos dados
        $pdo->query("DELETE FROM Dias_Trabalhados");
        
        // Processar cada dia da semana
        $dias_semana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
        
        foreach ($dias_semana as $dia) {
            $ativo = isset($_POST['ativo'][$dia]) ? 1 : 0;
            $abertura = $_POST['abertura'][$dia];
            $fechamento = $_POST['fechamento'][$dia];
            $tem_almoco = isset($_POST['tem_almoco'][$dia]) ? 1 : 0;
            $almoco_inicio = $tem_almoco ? $_POST['almoco_inicio'][$dia] : NULL;
            $almoco_fim = $tem_almoco ? $_POST['almoco_fim'][$dia] : NULL;
            
            // Inserir no banco de dados
            $sql = "INSERT INTO Dias_Trabalhados (dia_semana, horario_abertura, horario_fechamento, horario_almoco_inicio, horario_almoco_fim, ativo) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$dia, $abertura, $fechamento, $almoco_inicio, $almoco_fim, $ativo]);
        }
        
        $success_message = "Horários salvos com sucesso!";
    } catch (PDOException $e) {
        $error_message = "Erro ao salvar horários: " . $e->getMessage();
    }
}

// Carregar configurações existentes
$horarios_config = [];
try {
    $stmt = $pdo->query("SELECT * FROM Dias_Trabalhados ORDER BY 
        CASE dia_semana 
            WHEN 'Segunda' THEN 1
            WHEN 'Terça' THEN 2
            WHEN 'Quarta' THEN 3
            WHEN 'Quinta' THEN 4
            WHEN 'Sexta' THEN 5
            WHEN 'Sábado' THEN 6
            WHEN 'Domingo' THEN 7
        END");
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($resultados as $row) {
        $horarios_config[$row['dia_semana']] = $row;
    }
} catch (PDOException $e) {
    // Se a tabela não existir, usar valores padrão
    $horarios_config = [];
}

// Dias da semana
$dias_semana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];

// Preparar dados para exibição
$dias_config = [];
foreach ($dias_semana as $dia) {
    if (isset($horarios_config[$dia])) {
        $dias_config[$dia] = $horarios_config[$dia];
    } else {
        // Valores padrão
        $dias_config[$dia] = [
            'ativo' => ($dia != 'Domingo' && $dia != 'Sábado'),
            'horario_abertura' => '08:00',
            'horario_fechamento' => '18:00',
            'horario_almoco_inicio' => '12:00',
            'horario_almoco_fim' => '13:00',
            'tem_almoco' => true
        ];
    }
}

// ======= FUNÇÃO GET HORARIOS DISPONIVEIS =======
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
    
    // Verificar agendamentos existentes para esta data
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

// ======= TESTAR HORÁRIOS DISPONÍVEIS (PARA DEMONSTRAÇÃO) =======
$horarios_por_dia = [];
try {
    $stmt = $pdo->query("SELECT * FROM Dias_Trabalhados WHERE ativo = 1");
    $horarios_clinica = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($horarios_clinica as $horario) {
        $horarios_por_dia[$horario['dia_semana']] = $horario;
    }
} catch (PDOException $e) {
    $horarios_por_dia = [];
}

// Testar para amanhã
$data_teste = date('Y-m-d', strtotime('+1 day'));
$horarios_disponiveis_teste = getHorariosDisponiveis($data_teste, $pdo, $horarios_por_dia);

// Primeiro, carregue os horários da clínica
$horarios_por_dia = [];
try {
    $stmt = $pdo->query("SELECT * FROM Dias_Trabalhados WHERE ativo = 1");
    $horarios_clinica = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($horarios_clinica as $horario) {
        $horarios_por_dia[$horario['dia_semana']] = $horario;
    }
} catch (PDOException $e) {
    $horarios_por_dia = [];
}

// Depois, chame a função para uma data específica
$data = '2024-01-15'; // Data que você quer verificar
$horarios_disponiveis = getHorariosDisponiveis($data, $pdo, $horarios_por_dia);

// $horarios_disponiveis conterá um array com os horários disponíveis
include "header.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Horários - Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c7da0;
            --primary-dark: #1a5c7a;
            --primary-light: #a9d6e5;
            --secondary: #01497c;
            --accent: #e63946;
            --light: #f8f9fa;
            --gray: #e9ecef;
            --dark-gray: #6c757d;
            --white: #ffffff;
            --success: #2a9d8f;
            --warning: #e9c46a;
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
            background: linear-gradient(135deg, #f0f8ff 0%, #e6f2ff 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .dias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dia-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }

        .dia-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .dia-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray);
        }

        .dia-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .horarios-container {
            display: grid;
            gap: 15px;
        }

        .time-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .time-input {
            padding: 12px;
            border: 1px solid var(--gray);
            border-radius: 6px;
            font-size: 16px;
            transition: var(--transition);
        }

        .time-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 125, 160, 0.2);
        }

        .almoco-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--gray);
        }

        .almoco-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        .almoco-toggle input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .almoco-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .hidden {
            display: none;
        }

        .btn-salvar {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--success), #1d7870);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-salvar:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .test-section {
            background: var(--light);
            padding: 20px;
            border-radius: var(--radius);
            margin-top: 30px;
            border-left: 4px solid var(--warning);
        }

        .test-section h3 {
            margin-bottom: 15px;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .horarios-lista {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .horario-item {
            background: var(--primary);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
        }

        .sem-horarios {
            color: var(--dark-gray);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .dias-grid {
                grid-template-columns: 1fr;
            }
            
            .time-group, .almoco-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> Configuração de Horários</h1>
            <p>Defina os dias e horários de funcionamento da clínica</p>
        </div>
        
        <div class="content">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success_message ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="dias-grid">
                    <?php foreach ($dias_config as $dia => $config): ?>
                        <div class="dia-card">
                            <div class="dia-header">
                                <span class="dia-title"><?= $dia ?></span>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="ativo[<?= $dia ?>]" <?= $config['ativo'] ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="horarios-container">
                                <div class="time-group">
                                    <div>
                                        <label>Abertura</label>
                                        <input type="time" name="abertura[<?= $dia ?>]" 
                                               value="<?= $config['horario_abertura'] ?>" 
                                               class="time-input" required>
                                    </div>
                                    <div>
                                        <label>Fechamento</label>
                                        <input type="time" name="fechamento[<?= $dia ?>]" 
                                               value="<?= $config['horario_fechamento'] ?>" 
                                               class="time-input" required>
                                    </div>
                                </div>
                                
                                <div class="almoco-section">
                                    <label class="almoco-toggle">
                                        <input type="checkbox" name="tem_almoco[<?= $dia ?>]" 
                                               <?= !empty($config['horario_almoco_inicio']) ? 'checked' : '' ?> 
                                               onchange="toggleAlmoco(this, '<?= $dia ?>')">
                                        <span>Horário de almoço</span>
                                    </label>
                                    
                                    <div id="almoco-<?= $dia ?>" class="almoco-content <?= empty($config['horario_almoco_inicio']) ? 'hidden' : '' ?>">
                                        <div>
                                            <label>Início</label>
                                            <input type="time" name="almoco_inicio[<?= $dia ?>]" 
                                                   value="<?= !empty($config['horario_almoco_inicio']) ? $config['horario_almoco_inicio'] : '12:00' ?>" 
                                                   class="time-input">
                                        </div>
                                        <div>
                                            <label>Fim</label>
                                            <input type="time" name="almoco_fim[<?= $dia ?>]" 
                                                   value="<?= !empty($config['horario_almoco_fim']) ? $config['horario_almoco_fim'] : '13:00' ?>" 
                                                   class="time-input">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" name="salvar_horarios" class="btn-salvar">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
            </form>

            <!-- Seção de teste dos horários disponíveis -->
            <div class="test-section">
                <h3><i class="fas fa-test-tube"></i> Teste dos Horários Disponíveis</h3>
                <p>Horários disponíveis para amanhã (<?= date('d/m/Y', strtotime('+1 day')) ?>):</p>
                
                <div class="horarios-lista">
                    <?php if (!empty($horarios_disponiveis_teste)): ?>
                        <?php foreach ($horarios_disponiveis_teste as $horario): ?>
                            <span class="horario-item"><?= $horario ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="sem-horarios">Nenhum horário disponível ou clínica fechada</span>
                    <?php endif; ?>
                </div>
                
                <p style="margin-top: 15px; font-size: 14px; color: var(--dark-gray);">
                    <i class="fas fa-info-circle"></i> Esta é uma demonstração dos horários que estarão disponíveis para agendamento.
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleAlmoco(checkbox, dia) {
            const almocoSection = document.getElementById('almoco-' + dia);
            if (checkbox.checked) {
                almocoSection.classList.remove('hidden');
            } else {
                almocoSection.classList.add('hidden');
            }
        }
        
        // Inicializar toggles na carga da página
        document.querySelectorAll('.almoco-toggle input[type="checkbox"]').forEach(checkbox => {
            const dia = checkbox.name.match(/\[(.*?)\]/)[1];
            toggleAlmoco(checkbox, dia);
        });
    </script>
</body>
</html>