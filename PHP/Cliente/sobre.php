<?php
session_start();
// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Check session and user type
if (!isset($_SESSION["id"]) || $_SESSION["tipo_usuario"] !== "Cliente") {
    header("Location: ../index.php");
    exit();
}

// Generate CSRF token for potential forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sobre a PetCare - Conheça nossa história, valores e compromisso com o bem-estar animal">
    <meta name="keywords" content="veterinária, petcare, sobre nós, cuidados pet">
    <meta name="author" content="PetCare">
    <title>Sobre Nós - PetCare Clínica Veterinária</title>
    
    <link rel="icon" type="image/png" href="https://img.icons8.com/ios/452/cat.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2E8B57;
            --primary-dark: #1F5F3F;
            --secondary-color: #c6c8c8;
            --accent-color: #FF6B6B;
            --text-dark: #2C3E50;
            --text-light: #7F8C8D;
            --background-light: #F8F9FA;
            --white: #FFFFFF;
            --gradient-primary: linear-gradient(135deg, #7d8a83 0%, #48B973 100%);
            --gradient-secondary: linear-gradient(135deg, #F0A500 0%, #FFB84D 100%);
            --shadow-light: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.15);
            --shadow-large: 0 20px 40px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--white);
            overflow-x: hidden;
        }

        /* Header e Navegação */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(46, 139, 87, 0.1);
            transition: var(--transition);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            gap: 0.5rem;
        }

        .logo-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            transition: var(--transition);
        }

        .logo:hover .logo-img {
            transform: scale(1.1) rotate(5deg);
            border-color: var(--secondary-color);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .login-btn {
            background: var(--gradient-primary);
            color: var(--white) !important;
            padding: 0.7rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px) scale(1.05);
            color: var(--white) !important;
        }

        .login-btn i {
            font-size: 1rem;
            transition: var(--transition);
        }

        .login-btn:hover i {
            transform: scale(1.1) rotate(5deg);
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            position: relative;
            padding: 0.5rem 0;
            transition: var(--transition);
        }

        .nav-links a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gradient-primary);
            transition: var(--transition);
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .nav-links a:hover::before {
            width: 100%;
        }

        /* Tag Componente */
        .tag {
            display: inline-block;
            background: var(--gradient-secondary);
            color: var(--white);
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-light);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        /* Botões */
        .btn {
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn.primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-medium);
        }

        .btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-large);
        }

        .btn.secondary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn.secondary:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        /* Estilos específicos da página Sobre */
        .hero-sobre {
            background: linear-gradient(135deg, rgba(46, 139, 87, 0.9), rgba(72, 185, 115, 0.9)), url('https://images.pexels.com/photos/6753889/pexels-photo-6753889.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 60vh;
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
            position: relative;
            margin-top: 80px;
        }

        .hero-sobre::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
        }

        .hero-sobre-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .hero-sobre h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .hero-sobre p {
            font-size: 1.3rem;
            opacity: 0.95;
            line-height: 1.6;
        }

        .nossa-historia {
            padding: 100px 5% 80px;
            background: var(--white);
        }

        .historia-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .historia-texto h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: var(--primary-color);
        }

        .historia-texto p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .historia-imagem {
            position: relative;
        }

        .historia-imagem img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: var(--shadow-large);
            loading: lazy;
        }

        .valores-section {
            padding: 100px 5%;
            background: var(--white);
        }

        .valores-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .valores-content h2 {
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: var(--text-dark);
        }

        .valores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }

        .valor-card {
            background: linear-gradient(145deg, #f0f9f4, #ffffff);
            padding: 2.5rem 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(46, 139, 87, 0.1);
        }

        .valor-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .valor-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-large);
        }

        .valor-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
        }

        .valor-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .valor-card p {
            color: var(--text-light);
            line-height: 1.6;
        }

        .compromisso-section {
            background: var(--gradient-primary);
            color: white;
            padding: 80px 5%;
            text-align: center;
            position: relative;
        }

        .compromisso-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.pexels.com/photos/4269985/pexels-photo-4269985.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
        }

        .compromisso-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }

        .compromisso-content h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            font-weight: 700;
        }

        .compromisso-content p {
            font-size: 1.2rem;
            line-height: 1.8;
            opacity: 0.95;
        }

        .cta-section {
            background: var(--white);
            padding: 80px 5%;
            text-align: center;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--text-dark);
        }

        .cta-content p {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 2.5rem;
            line-height: 1.7;
        }

        .buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .footer {
            background: var(--text-dark);
            color: var(--white);
            padding: 4rem 5% 2rem;
        }

        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            max-width: 1400px;
            margin: 0 auto;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
        }

        .footer-section p {
            color: #BDC3C7;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: var(--gradient-primary);
            color: var(--white);
            border-radius: 50%;
            text-decoration: none;
            transition: var(--transition);
        }

        .social-icons a:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: var(--shadow-medium);
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.8rem;
        }

        .footer-section ul li a {
            color: #BDC3C7;
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-section ul li a:hover {
            color: var(--secondary-color);
            padding-left: 5px;
        }

        .horario p {
            margin-bottom: 0.5rem;
        }

        .emergencia a {
            color: var(--accent-color);
            font-weight: 600;
            text-decoration: none;
            border: 1px solid var(--accent-color);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            display: inline-block;
            margin-top: 1rem;
            transition: var(--transition);
        }

        .emergencia a:hover {
            background: var(--accent-color);
            color: var(--white);
            transform: scale(1.05);
        }

        .footer-bottom {
            border-top: 1px solid #34495E;
            padding-top: 2rem;
            text-align: center;
            color: #95A5A6;
        }

        .footer-bottom p {
            margin-bottom: 0.5rem;
        }

        .heart {
            color: var(--accent-color);
            font-size: 1.2rem;
            animation: heartbeat 1.5s ease-in-out infinite;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem 3%;
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                gap: 1rem;
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
                order: 2;
            }
            
            .login-btn {
                order: -1;
                margin-bottom: 0.5rem;
            }
            
            .hero-sobre {
                margin-top: 100px;
            }

            .historia-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }

            .historia-texto h2 {
                font-size: 2rem;
            }

            .valores-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .nossa-historia,
            .valores-section,
            .compromisso-section,
            .cta-section {
                padding: 60px 3%;
            }

            .footer {
                padding: 3rem 3% 1.5rem;
            }

            .footer-container {
                gap: 2rem;
            }
        }

        @media (max-width: 480px) {
            .hero-sobre {
                min-height: 50vh;
                padding: 2rem 1rem;
            }

            .nossa-historia,
            .valores-section,
            .compromisso-section,
            .cta-section {
                padding: 40px 1rem;
            }

            .historia-imagem img {
                height: 300px;
            }
        }

        /* Scroll suave */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <?php include '../menu.php';?>

    <main>
        <section class="hero-sobre" aria-labelledby="hero-sobre-title">
            <div class="hero-sobre-content">
                <h1 id="hero-sobre-title">Sobre a PetCare</h1>
                <p>Mais de uma década dedicada ao cuidado e bem-estar dos animais, oferecendo tratamento veterinário de excelência com amor e responsabilidade.</p>
            </div>
        </section>

        <section class="nossa-historia" aria-labelledby="historia-title">
            <div class="historia-content">
                <div class="historia-texto">
                    <span class="tag">Nossa História</span>
                    <h2 id="historia-title">Uma jornada de amor pelos animais</h2>
                    <p>A PetCare nasceu com o sonho de criar um espaço onde os animais pudessem receber cuidados veterinários de qualidade em um ambiente acolhedor e humanizado. Fundada pela Dra. Maria Silva, nossa clínica começou como um pequeno consultório e cresceu até se tornar uma das referências em medicina veterinária da região.</p>
                    <p>Nossa jornada é impulsionada pela inovação e pelo amor aos animais. Investimos constantemente em tecnologia de ponta e em uma equipe de profissionais dedicados, porque acreditamos que cada pet é único. Nosso compromisso é oferecer um atendimento excepcional, todos os dias.</p>
                    <p>Nossa missão vai além do tratamento médico: acreditamos na importância da educação dos tutores, na prevenção de doenças e no fortalecimento do vínculo entre humanos e animais de estimação.</p>
                </div>
                <div class="historia-imagem">
                    <img src="https://images.pexels.com/photos/6816861/pexels-photo-6816861.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Nossa história" loading="lazy">
                </div>
            </div>
        </section>

        <section class="valores-section" aria-labelledby="valores-title">
            <div class="valores-content">
                <span class="tag">Nossos Valores</span>
                <h2 id="valores-title">O que nos move todos os dias</h2>
                <div class="valores-grid">
                    <div class="valor-card">
                        <div class="valor-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Amor pelos Animais</h3>
                        <p>Cada pet é tratado com carinho, respeito e dedicação, como se fosse da nossa própria família. O bem-estar animal é nossa prioridade número um.</p>
                    </div>
                    <div class="valor-card">
                        <div class="valor-icon">
                            <i class="fas fa-microscope"></i>
                        </div>
                        <h3>Excelência Técnica</h3>
                        <p>Investimos constantemente em tecnologia de ponta e capacitação profissional para oferecer diagnósticos precisos e tratamentos eficazes.</p>
                    </div>
                    <div class="valor-card">
                        <div class="valor-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3>Transparência</h3>
                        <p>Mantemos comunicação clara e honesta com os tutores, explicando diagnósticos, tratamentos e custos de forma transparente.</p>
                    </div>
                    <div class="valor-card">
                        <div class="valor-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Humanização</h3>
                        <p>Tratamos cada família com empatia e compreensão, oferecendo apoio emocional nos momentos mais difíceis.</p>
                    </div>
                    <div class="valor-card">
                        <div class="valor-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3>Sustentabilidade</h3>
                        <p>Adotamos práticas eco-friendly e promovemos a conscientização sobre bem-estar animal e preservação do meio ambiente.</p>
                    </div>
                    <div class="valor-card">
                        <div class="valor-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Educação</h3>
                        <p>Acreditamos na importância de educar os tutores sobre cuidados preventivos e bem-estar animal para uma vida mais saudável.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="compromisso-section" aria-labelledby="compromisso-title">
            <div class="compromisso-content">
                <h2 id="compromisso-title">Nosso Compromisso</h2>
                <p>Na PetCare, renovamos diariamente nosso compromisso de oferecer cuidados veterinários de excelência. Cada animal que passa por nossas portas recebe não apenas tratamento médico de qualidade, mas também todo o amor e respeito que merece. Trabalhamos incansavelmente para fortalecer o vínculo entre pets e suas famílias, contribuindo para uma convivência mais harmoniosa e saudável.</p>
            </div>
        </section>

        <section class="cta-section" aria-labelledby="cta-title">
            <div class="cta-content">
                <h2 id="cta-title">Faça parte da nossa família</h2>
                <p>Venha conhecer nossa clínica e descobrir por que somos a escolha de milhares de tutores que confiam no nosso trabalho.</p>
                <div class="buttons">
                    <a href="consultas.php" class="btn primary">Agendar Consulta</a>
                    <a href="index.php#contato" class="btn secondary">Contato</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer" aria-label="Rodapé">
        <div class="footer-container">
            <div class="footer-section">
                <h3>PetCare</h3>
                <p>Cuidado veterinário de qualidade para todos os tipos de animais de estimação.</p>
                <div class="social-icons">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Links Rápidos</h3>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="index.php#servicos">Serviços</a></li>
                    <li><a href="index.php#equipe">Equipe</a></li>
                    <li><a href="index.php#depoimentos">Depoimentos</a></li>
                    <li><a href="index.php#contato">Contato</a></li>
                </ul>
            </div>
            <div class="footer-section horario">
                <h3>Horário de Atendimento</h3>
                <p>Segunda a Sexta: 8h às 19h</p>
                <p>Sábados: 8h às 16h</p>
                <p>Domingos e Feriados: 9h às 12h</p>
                <p class="emergencia"><a href="#">Plantão 24h para Emergências</a></p>
            </div>
        </div>
        <div class
        <div class="footer-bottom">
            <p>&copy; 2025 PetCare Clínica Veterinária. Todos os direitos reservados.</p>
            <p>Feito com <span class="heart">❤️</span> para todos os animais</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ==========================================
            // NAVBAR SCROLL EFFECT
            // ==========================================
            const header = document.querySelector('header');
            const navbar = document.querySelector('.navbar');
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    header.style.background = 'rgba(255, 255, 255, 0.98)';
                    header.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
                    navbar.style.padding = '0.7rem 5%';
                } else {
                    header.style.background = 'rgba(255, 255, 255, 0.95)';
                    header.style.boxShadow = 'none';
                    navbar.style.padding = '1rem 5%';
                }
            });

            // ==========================================
            // SMOOTH SCROLL PARA LINKS INTERNOS
            // ==========================================
            const internalLinks = document.querySelectorAll('a[href^="#"]');
            
            internalLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        const headerHeight = header.offsetHeight;
                        const targetPosition = targetElement.offsetTop - headerHeight - 20;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // ==========================================
            // ANIMAÇÃO DE ENTRADA DOS ELEMENTOS
            // ==========================================
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        
                        // Animação especial para cards
                        if (entry.target.classList.contains('valor-card')) {
                            setTimeout(() => {
                                entry.target.style.transform = 'translateY(0) scale(1)';
                            }, 100);
                        }
                    }
                });
            }, observerOptions);

            // Observar todos os cards e seções
            const animatedElements = document.querySelectorAll('.valor-card, .hero-sobre-content, .historia-imagem, .tag, h2');
            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                observer.observe(el);
            });

            // ==========================================
            // BOTÃO DE LOGIN
            // ==========================================
            const loginBtn = document.querySelector('.login-btn');
            
            if (loginBtn) {
                loginBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Animação de clique
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'translateY(-2px) scale(1.05)';
                    }, 100);
                    
                    // Mostrar modal de login
                    showLoginModal();
                });
            }

            // ==========================================
            // MODAL DE LOGIN
            // ==========================================
            function showLoginModal() {
                // Remover modal existente se houver
                const existingModal = document.querySelector('.login-modal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Criar modal dinamicamente
                const modal = document.createElement('div');
                modal.className = 'login-modal';
                modal.innerHTML = `
                    <div class="modal-overlay">
                        <div class="modal-content">
                            <button class="close-btn">&times;</button>
                            <div class="modal-header">
                                <h3><i class="fas fa-paw"></i> Login PetCare</h3>
                                <p>Faça login em sua conta para acessar nossos serviços</p>
                            </div>
                            
                            <form id="loginForm" class="login-form">
                                <div class="input-group">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" id="email" placeholder="Seu e-mail" required>
                                </div>
                                
                                <div class="input-group">
                                    <i class="fas fa-lock"></i>
                                    <input type="password" id="password" placeholder="Sua senha" required>
                                </div>
                                
                                <div class="login-options">
                                    <label class="checkbox-container">
                                        <input type="checkbox" id="remember">
                                        <span class="checkmark"></span>
                                        Lembrar de mim
                                    </label>
                                    <a href="redefinirsenha.php" class="forgot-password">Esqueci a senha</a>
                                </div>
                                
                                <button type="submit" class="login-submit-btn">
                                    <i class="fas fa-sign-in-alt"></i> Entrar
                                </button>
                                
                                <div class="divider">
                                    <span>ou</span>
                                </div>
                                
                                <button type="button" id="googleLogin" class="google-login-btn">
                                    <i class="fab fa-google"></i> 
                                    Continuar com Google
                                </button>
                                
                                <div class="signup-link">
                                    Não tem conta? <a href="cadastro.php" id="signupLink">Cadastre-se aqui</a>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Estilos do modal
                const modalStyles = document.createElement('style');
                modalStyles.textContent = `
                    .login-modal {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        z-index: 10000;
                        animation: fadeIn 0.3s ease;
                    }
                    
                    .modal-overlay {
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.8);
                        backdrop-filter: blur(5px);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                    }
                    
                    .modal-content {
                        background: white;
                        padding: 2.5rem;
                        border-radius: 20px;
                        max-width: 450px;
                        width: 100%;
                        position: relative;
                        animation: slideInScale 0.3s ease;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    }
                    
                    .close-btn {
                        position: absolute;
                        top: 1rem;
                        right: 1rem;
                        background: none;
                        border: none;
                        font-size: 1.5rem;
                        cursor: pointer;
                        color: #7F8C8D;
                        transition: all 0.3s ease;
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    
                    .close-btn:hover {
                        background: #f8f9fa;
                        color: #e74c3c;
                        transform: rotate(90deg);
                    }
                    
                    .modal-header {
                        text-align: center;
                        margin-bottom: 2rem;
                    }
                    
                    .modal-header h3 {
                        color: #2E8B57;
                        margin-bottom: 0.5rem;
                        font-size: 1.5rem;
                        font-weight: 700;
                    }
                    
                    .modal-header p {
                        color: #7F8C8D;
                        font-size: 0.9rem;
                        margin: 0;
                    }
                    
                    .input-group {
                        position: relative;
                        margin-bottom: 1.5rem;
                    }
                    
                    .input-group i {
                        position: absolute;
                        left: 15px;
                        top: 50%;
                        transform: translateY(-50%);
                        color: #7F8C8D;
                        transition: color 0.3s ease;
                    }
                    
                    .input-group input {
                        width: 100%;
                        padding: 1rem 1rem 1rem 45px;
                        border: 2px solid #e9ecef;
                        border-radius: 12px;
                        font-size: 1rem;
                        transition: all 0.3s ease;
                        font-family: 'Montserrat', sans-serif;
                    }
                    
                    .input-group input:focus {
                        outline: none;
                        border-color: #2E8B57;
                        box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
                    }
                    
                    .input-group input:focus + i {
                        color: #2E8B57;
                    }
                    
                    .login-options {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 2rem;
                        font-size: 0.9rem;
                    }
                    
                    .checkbox-container {
                        display: flex;
                        align-items: center;
                        cursor: pointer;
                        color: #7F8C8D;
                    }
                    
                    .checkbox-container input {
                        margin-right: 8px;
                    }
                    
                    .forgot-password {
                        color: #2E8B57;
                        text-decoration: none;
                        transition: color 0.3s ease;
                    }
                    
                    .forgot-password:hover {
                        color: #1F5F3F;
                        text-decoration: underline;
                    }
                    
                    .login-submit-btn, .google-login-btn {
                        width: 100%;
                        padding: 1rem;
                        border: none;
                        border-radius: 12px;
                        font-size: 1rem;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 0.5rem;
                        font-family: 'Montserrat', sans-serif;
                    }
                    
                    .login-submit-btn {
                        background: linear-gradient(135deg, #7d8a83 0%, #48B973 100%);
                        color: white;
                        margin-bottom: 1.5rem;
                    }
                    
                    .login-submit-btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 25px rgba(46, 139, 87, 0.3);
                    }
                    
                    .google-login-btn {
                        background: #4285f4;
                        color: white;
                        margin-bottom: 1.5rem;
                    }
                    
                    .google-login-btn:hover {
                        background: #3367d6;
                        transform: translateY(-2px);
                        box-shadow: 0 8px 25px rgba(66, 133, 244, 0.3);
                    }
                    
                    .divider {
                        text-align: center;
                        margin: 1.5rem 0 1rem 0;
                        position: relative;
                    }

                    .divider::before {
                        content: '';
                        position: absolute;
                        top: 50%;
                        left: 0;
                        right: 0;
                        height: 1px;
                        background: #e9ecef;
                        z-index: 1;
                    }

                    .divider span {
                        background: white;
                        padding: 0 1.5rem;
                        color: #7F8C8D;
                        font-size: 0.9rem;
                        font-weight: 500;
                        position: relative;
                        z-index: 2;
                    }
                    
                    .signup-link {
                        text-align: center;
                        color: #7F8C8D;
                        font-size: 0.9rem;
                    }
                    
                    .signup-link a {
                        color: #2E8B57;
                        text-decoration: none;
                        font-weight: 600;
                        transition: color 0.3s ease;
                    }
                    
                    .signup-link a:hover {
                        color: #1F5F3F;
                        text-decoration: underline;
                    }
                    
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    
                    @keyframes slideInScale {
                        from {
                            opacity: 0;
                            transform: scale(0.8) translateY(-20px);
                        }
                        to {
                            opacity: 1;
                            transform: scale(1) translateY(0);
                        }
                    }
                `;
                document.head.appendChild(modalStyles);
                
                // Event listeners do modal
                const closeBtn = modal.querySelector('.close-btn');
                const loginForm = modal.querySelector('#loginForm');
                const googleLoginBtn = modal.querySelector('#googleLogin');
                const signupLink = modal.querySelector('#signupLink');
                const forgotPassword = modal.querySelector('.forgot-password');
                
                // Fechar modal
                function closeModal() {
                    modal.style.animation = 'fadeOut 0.3s ease forwards';
                    setTimeout(() => {
                        modal.remove();
                        modalStyles.remove();
                    }, 300);
                }
                
                closeBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', function(e) {
                    if (e.target === modal.querySelector('.modal-overlay')) {
                        closeModal();
                    }
                });
                
                // Submit do formulário
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('.login-submit-btn');
                    const originalText = submitBtn.innerHTML;
                    
                    // Animação de loading
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
                    submitBtn.style.pointerEvents = 'none';
                    
                    // Simular login
                    setTimeout(() => {
                        PetCare.showNotification('Login realizado com sucesso! Bem-vindo(a)!', 'success');
                        closeModal();
                    }, 2000);
                });
                
                // Login com Google
                googleLoginBtn.addEventListener('click', function() {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando...';
                    this.style.pointerEvents = 'none';
                    
                    // Simular login Google
                    setTimeout(() => {
                        PetCare.showNotification('Login com Google será implementado em breve!', 'info');
                        closeModal();
                    }, 1500);
                });
                
                // Link de cadastro
                signupLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'cadastro.html';
                });
                
                // Esqueci a senha
                forgotPassword.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.location.href = 'redefinirsenha.html';
                });
            }

            // ==========================================
            // ANIMAÇÕES CSS DINÂMICAS
            // ==========================================
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                
                .card {
                    position: relative;
                    overflow: hidden;
                }
                
                @keyframes slideInRight {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                
                @keyframes slideOutRight {
                    from {
                        opacity: 1;
                        transform: translateX(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                }
            `;
            document.head.appendChild(style);

            // ==========================================
            // EFEITOS DE PARALAXE SUTIL
            // ==========================================
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const parallaxElements = document.querySelectorAll('.hero::before, .servicos::after');
                
                parallaxElements.forEach(element => {
                    const speed = 0.5;
                    element.style.transform = `translateY(${scrolled * speed}px)`;
                });
            });

            // ==========================================
            // LOADING SCREEN
            // ==========================================
            window.addEventListener('load', function() {
                document.body.style.opacity = '0';
                document.body.style.transition = 'opacity 0.5s ease';
                
                setTimeout(() => {
                    document.body.style.opacity = '1';
                }, 100);
            });

            console.log('🐾 PetCare JavaScript carregado com sucesso!');
        });

        // ==========================================
        // UTILITÁRIOS GLOBAIS
        // ==========================================
        window.PetCare = {
            showNotification: function(message, type = 'success') {
                const notification = document.createElement('div');
                const bgColor = {
                    'success': '#28a745',
                    'error': '#dc3545',
                    'info': '#17a2b8',
                    'warning': '#ffc107'
                };
                
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 1rem 1.5rem;
                    background: ${bgColor[type] || bgColor.success};
                    color: white;
                    border-radius: 12px;
                    z-index: 10001;
                    animation: slideInRight 0.3s ease;
                    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
                    font-weight: 500;
                    max-width: 300px;
                    word-wrap: break-word;
                `;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slideOutRight 0.3s ease forwards';
                    setTimeout(() => notification.remove(), 300);
                }, 4000);
            },
            
            // Função para inicializar login Google (para implementação futura)
            initGoogleAuth: function() {
                console.log('Google Auth será implementado aqui');
            }
        };
    </script>
</body>
</html>