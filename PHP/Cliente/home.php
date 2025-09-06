<?php
ob_start();
session_start();
// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Check session and user type
if (!isset($_SESSION["id"]) || $_SESSION["tipo_usuario"] !== "Cliente") {
    header("Location: ../../index.php");
    exit();
}

// Generate CSRF token for form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include('../Conexao.php');

// Function to get abbreviated name (e.g., "Gabriel Figueiredo" -> "GF")
function getAbbreviatedName($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

$abbreviatedName = isset($_SESSION['nome']) ? getAbbreviatedName($_SESSION['nome']) : '';
?>





<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PetCare - Área do Cliente para cuidados com seu pet">
    <meta name="keywords" content="petcare, cliente, veterinária, cuidado pet">
    <meta name="author" content="PetCare">
    <title>Área do Cliente - PetCare</title>
    
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios/452/cat.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous">
    
    <link rel="stylesheet" href="../../Estilos/styles.css">
</head>
<body>
    

    <main>
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-text">
                <h1>Cuidado <span class="highlight">especializado</span> para seu melhor amigo</h1>
                <p>Oferecemos atendimento veterinário de qualidade com uma equipe de profissionais dedicados ao bem-estar do seu pet.</p>
                <div class="buttons">
                    <a href="consultas.php" class="btn primary">Agendar Consulta</a>
                    <a href="#contato" class="btn secondary">Contato</a>
                </div>
            </div>
            <div class="image-container">
                <img src="https://images.pexels.com/photos/6235225/pexels-photo-6235225.jpeg" alt="Veterinário cuidando de um pet" loading="lazy">
            </div>
        </section>

        <section id="servicos" class="servicos" aria-labelledby="servicos-title">
            <div class="container">
                <span class="tag">Nossos Serviços</span>
                <h2 id="servicos-title">Serviços especializados para seu pet</h2>
                <p>Oferecemos uma ampla gama de serviços veterinários de alta qualidade para garantir a saúde e o bem-estar do seu animal de estimação.</p>
                <div class="grid">
                    <!--<a href="consultas.php" class="card" aria-label="Consultas veterinárias">
                        <div class="icon">🩺</div>
                        <h3>Consultas</h3>
                        <p>Atendimento clínico com profissionais especializados.</p>
                    </a>
                    <a href="banho-tosa.php" class="card" aria-label="Banho e tosa">
                        <div class="icon">✂️</div>
                        <h3>Banho & Tosa</h3>
                        <p>Serviços de estética completos realizados por profissionais capacitados.</p>
                    </a>
                    <a href="farmacia.php" class="card" aria-label="Farmácia veterinária">
                        <div class="icon">💊</div>
                        <h3>Farmácia</h3>
                        <p>Medicamentos e produtos de qualidade para o tratamento e bem-estar animal.</p>
                    </a>-->
                    <a href="vacinas.php" class="card" aria-label="Vacinação">
                        <div class="icon">💉</div>
                        <h3>Vacinação</h3>
                        <p>Programa de imunização completo para prevenir doenças e proteger seu pet.</p>
                    </a>
                    <a href="exames.php" class="card" aria-label="Exames laboratoriais">
                        <div class="icon">🔬</div>
                        <h3>Exames</h3>
                        <p>Laboratório completo para diagnósticos precisos e rápidos.</p>
                    </a>
                    <a href="cirurgias.php" class="card" aria-label="Cirurgias veterinárias">
                        <div class="icon">📋</div>
                        <h3>Cirurgias</h3>
                        <p>Centro cirúrgico equipado para procedimentos simples e complexos.</p>
                    </a>
                </div>
            </div>
        </section>

        
    <?php include '../menu.php';?>
    
</body>
</html>
<!--<section id="equipe" class="equipe" aria-labelledby="equipe-title">
            <div class="container">
                <span class="tag">Nossa Equipe</span>
                <h2 id="equipe-title">Profissionais apaixonados por animais</h2>
                <div class="grid">
                    <div class="card">
                        <img src="https://i.pinimg.com/1200x/8f/53/5e/8f535eb32689e19f318dabf46785951e.jpg" alt="Dr. João Silva" loading="lazy">
                        <h3>Dr. João Silva</h3>
                        <p>Especialista em clínica geral e cirurgia.</p>
                    </div>
                    <div class="card">
                        <img src="https://i.pinimg.com/736x/30/53/da/3053da7e5d6abb0b1411e77e73bcb66e.jpg" alt="Dra. Maria Souza" loading="lazy">
                        <h3>Dra. Maria Souza</h3>
                        <p>Especialista em dermatologia e cuidados com a pele.</p>
                    </div>
                    <div class="card">
                        <img src="https://i.pinimg.com/736x/8d/1c/af/8d1caf76c2f1b07b3e68cf7144a80e19.jpg" alt="Dr. Carlos Lima" loading="lazy">
                        <h3>Dr. Carlos Lima</h3>
                        <p>Especialista em ortopedia e reabilitação animal.</p>
                    </div>
                </div>
            </div>
        </section>-->
<!--
        <section id="depoimentos" class="depoimentos" aria-labelledby="depoimentos-title">
            <div class="container">
                <span class="tag">Depoimentos</span>
                <h2 id="depoimentos-title">O que nossos clientes dizem</h2>
                <div class="grid">
                    <div class="card">
                        <p>"A PetCare salvou a vida do meu cachorro! Atendimento excelente e muito carinho com os animais."</p>
                        <h4>- Ana Paula</h4>
                    </div>
                    <div class="card">
                        <p>"Equipe extremamente profissional e dedicada. Meu gato foi tratado como um rei."</p>
                        <h4>- Marcos Vinícius</h4>
                    </div>
                    <div class="card">
                        <p>"Serviços de qualidade e preços justos. Recomendo para todos os meus amigos."</p>
                        <h4>- Fernanda Rocha</h4>
                    </div>
                </div>
            </div>
        </section>

        <section id="faq" class="faq" aria-labelledby="faq-title">
            <div class="container">
                <span class="tag">Perguntas Frequentes</span>
                <h2 id="faq-title">Tire suas dúvidas</h2>
                <div class="faq-item">
                    <h3>Vocês atendem emergências?</h3>
                    <p>Sim! Temos plantão 24h para emergências.</p>
                </div>
                <div class="faq-item">
                    <h3>Quais formas de pagamento aceitam?</h3>
                    <p>Aceitamos cartões de crédito, débito, PIX e dinheiro.</p>
                </div>
                <div class="faq-item">
                    <h3>Preciso marcar consulta com antecedência?</h3>
                    <p>Recomendamos agendar para evitar espera, mas também atendemos por ordem de chegada.</p>
                </div>
            </div>
        </section>

        <section id="contato" class="contato" aria-labelledby="contato-title">
            <div class="container">
                <span class="tag">Fale Conosco</span>
                <h2 id="contato-title">Entre em contato</h2>
                <form action="process_contact.php" method="POST" id="contactForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="text" name="name" placeholder="Seu nome" required aria-label="Nome">
                    <input type="email" name="email" placeholder="Seu e-mail" required aria-label="E-mail">
                    <textarea name="message" placeholder="Sua mensagem" required aria-label="Mensagem"></textarea>
                    <button type="submit" class="btn primary">Enviar</button>
                </form>
            </div>
        </section>
    </main>-->