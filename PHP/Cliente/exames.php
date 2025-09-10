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

require_once '../../PHP/conexao.php';
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exames - PetCare Clínica Veterinária</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --accent-color: #4A90E2;
            --text-dark: #2C3E50;
            --text-light: #7F8C8D;
            --text-muted: #BDC3C7;
            --background-light: #F8F9FA;
            --background-white: #FFFFFF;
            --border-color: #E5E5E5;
            --gradient-primary: linear-gradient(135deg, #7d8a83 0%, #48B973 100%);
            --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-medium: 0 4px 12px rgba(0, 0, 0, 0.12);
            --border-radius: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--background-white);
        }

        /* Header */
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

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
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

        /* Main Content */
        .main-content {
            margin-top: 80px;
            min-height: calc(100vh - 80px);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 4rem 5% 3rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* About Section */
        .about-section {
            padding: 4rem 5%;
            background: var(--background-light);
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .about-content h2 {
            font-size: 2rem;
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .about-content p {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            color: var(--text-dark);
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .stat-item i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .about-image img {
            width: 100%;
            border-radius: 12px;
            box-shadow: var(--shadow-medium);
        }

        /* Exams Section */
        .exames-section {
            padding: 4rem 5%;
            background: var(--background-white);
        }

        .exames-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 2.2rem;
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .exames-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .exam-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-light);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }

        .exam-image {
            height: 200px;
            overflow: hidden;
        }

        .exam-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .exam-card:hover .exam-image img {
            transform: scale(1.05);
        }

        .exam-content {
            padding: 1.5rem;
        }

        .exam-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }

        .exam-description {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .exam-features {
            list-style: none;
            margin-top: 1rem;
        }

        .exam-features li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .exam-features li i {
            color: var(--accent-color);
            font-size: 0.8rem;
        }

        /* Services Section */
        .services-section {
            padding: 4rem 5%;
            background: var(--background-light);
        }

        .services-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .service-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
        }

        .service-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .service-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .service-description {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Contact Info Section */
        .contact-info {
            background: var(--primary-color);
            color: white;
            padding: 3rem 5%;
            text-align: center;
        }

        .contact-info h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .contact-info p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: var(--background-white);
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
            color: var(--background-white);
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
            color: #FF6B6B;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #FF6B6B;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            display: inline-block;
            margin-top: 1rem;
            transition: var(--transition);
        }

        .emergencia a:hover {
            background: #FF6B6B;
            color: var(--background-white);
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
            color: #FF6B6B;
            font-size: 1.2rem;
            animation: heartbeat 1.5s ease-in-out infinite;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Responsive Design */
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
            }
            
            .page-header {
                padding: 3rem 3% 2rem;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .about-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .about-stats {
                grid-template-columns: 1fr;
            }
            
            .exames-grid, .services-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .footer {
                padding: 3rem 3% 1.5rem;
            }
            
            .footer-container {
                gap: 2rem;
            }
        }

        @media (max-width: 480px) {
            .page-header {
                padding: 2rem 1rem 1.5rem;
            }
            
            .about-section, .exames-section, .services-section {
                padding: 3rem 1rem;
            }
            
            .footer {
                padding: 2rem 1rem 1rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Utility classes */
        .text-center {
            text-align: center;
        }

        .mb-2 {
            margin-bottom: 1rem;
        }

        .mt-2 {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../menu.php'; ?>

    <main class="main-content">
        <!-- Page Header -->
        <section class="page-header">
            <h1>Exames Veterinários</h1>
            <p>Diagnósticos precisos com tecnologia avançada para cuidar da saúde do seu pet com excelência e dedicação profissional.</p>
        </section>

        <!-- About Section -->
        <section class="about-section">
            <div class="about-container">
                <div class="about-content">
                    <h2>Tecnologia e Precisão em Diagnósticos</h2>
                    <p>Na PetCare, utilizamos equipamentos de última geração e uma equipe altamente qualificada para oferecer diagnósticos precisos e confiáveis. Nossa estrutura moderna permite realizar uma ampla gama de exames com rapidez e segurança.</p>
                    <p>Contamos com laboratório próprio e parcerias estratégicas para garantir resultados rápidos e precisos, essenciais para o tratamento adequado do seu companheiro.</p>
                    
                    <div class="about-stats">
                        <div class="stat-item">
                            <i class="fas fa-paw"></i>
                            <span>+5.000 Pets Atendidos</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-clock"></i>
                            <span>Atendimento 24 Horas</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-star"></i>
                            <span>10+ Anos de Experiência</span>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="https://images.pexels.com/photos/7470635/pexels-photo-7470635.jpeg" alt="Laboratório PetCare" loading="lazy">
                </div>
            </div>
        </section>

        <!-- Exames Section -->
        <section class="exames-section">
            <div class="exames-container">
                <div class="section-header">
                    <h2 class="section-title">Nossos Exames Especializados</h2>
                    <p class="section-subtitle">Oferecemos uma ampla gama de exames diagnósticos com equipamentos modernos e profissionais especializados para garantir a saúde do seu pet.</p>
                </div>

                <div class="exames-grid">
                    <div class="exam-card">
                        <div class="exam-image">
                            <img src="https://images.pexels.com/photos/1350591/pexels-photo-1350591.jpeg" alt="Exame de hemogasometria" loading="lazy">
                        </div>
                        <div class="exam-content">
                            <h3 class="exam-title">Hemogasometria</h3>
                            <p class="exam-description">Análise precisa dos gases sanguíneos para monitoramento de funções respiratórias e metabólicas, especialmente importante em casos críticos e cirúrgicos.</p>
                            <ul class="exam-features">
                                <li><i class="fas fa-check-circle"></i> Resultados em tempo real</li>
                                <li><i class="fas fa-check-circle"></i> Equipamento de alta precisão</li>
                                <li><i class="fas fa-check-circle"></i> Essencial para UTI veterinária</li>
                            </ul>
                        </div>
                    </div>

                    <div class="exam-card">
                        <div class="exam-image">
                            <img src="https://images.pexels.com/photos/8450142/pexels-photo-8450142.jpeg" alt="Exames laboratoriais" loading="lazy">
                        </div>
                        <div class="exam-content">
                            <h3 class="exam-title">Exames Laboratoriais</h3>
                            <p class="exam-description">Hemogramas completos, bioquímica sanguínea e análises clínicas para diagnóstico abrangente da saúde do seu animal de estimação.</p>
                            <ul class="exam-features">
                                <li><i class="fas fa-check-circle"></i> Laboratório próprio</li>
                                <li><i class="fas fa-check-circle"></i> Resultados rápidos</li>
                                <li><i class="fas fa-check-circle"></i> Análises especializadas</li>
                            </ul>
                        </div>
                    </div>

                    <div class="exam-card">
                        <div class="exam-image">
                            <img src="https://images.pexels.com/photos/6235048/pexels-photo-6235048.jpeg" alt="Exame de radiologia" loading="lazy">
                        </div>
                        <div class="exam-content">
                            <h3 class="exam-title">Radiologia Digital</h3>
                            <p class="exam-description">Imagens radiológicas de alta definição para diagnóstico de fraturas, problemas respiratórios, digestivos e estruturais com máxima precisão.</p>
                            <ul class="exam-features">
                                <li><i class="fas fa-check-circle"></i> Tecnologia digital</li>
                                <li><i class="fas fa-check-circle"></i> Menor exposição à radiação</li>
                                <li><i class="fas fa-check-circle"></i> Imagens de alta qualidade</li>
                            </ul>
                        </div>
                    </div>

                    <div class="exam-card">
                        <div class="exam-image">
                            <img src="https://momentoequestre.com.br/wp-content/uploads/2018/01/tomografia.gif" alt="Tomografia computadorizada" loading="lazy">
                        </div>
                        <div class="exam-content">
                            <h3 class="exam-title">Tomografia Computadorizada</h3>
                            <p class="exam-description">Imagens tridimensionais detalhadas para diagnósticos complexos, planejamento cirúrgico e avaliação de estruturas internas com precisão excepcional.</p>
                            <ul class="exam-features">
                                <li><i class="fas fa-check-circle"></i> Imagens 3D detalhadas</li>
                                <li><i class="fas fa-check-circle"></i> Diagnóstico preciso</li>
                                <li><i class="fas fa-check-circle"></i> Planejamento cirúrgico</li>
                            </ul>
                        </div>
                    </div>

                    <div class="exam-card">
                        <div class="exam-image">
                            <img src="https://www.wellpetclinica.com.br/imagens/informacoes/ultrassonografia-veterinaria-no-ceara-09.jpg" alt="Ultrassonografia veterinária" loading="lazy">
                        </div>
                        <div class="exam-content">
                            <h3 class="exam-title">Ultrassonografia</h3>
                            <p class="exam-description">Exame não invasivo para avaliação de órgãos internos, acompanhamento de gestação e diagnóstico de condições abdominais com total segurança.</p>
                            <ul class="exam-features">
                                <li><i class="fas fa-check-circle"></i> Exame não invasivo</li>
                                <li><i class="fas fa-check-circle"></i> Acompanhamento gestacional</li>
                                <li><i class="fas fa-check-circle"></i> Avaliação em tempo real</li>
                            </ul>
                        </div>
                    </div>

                    <div class="exam-card">
                        <div class="exam-image">
                            <img src="https://images.pexels.com/photos/7469219/pexels-photo-7469219.jpeg" alt="Ecocardiograma veterinário" loading="lazy">
                        </div>
                        <div class="exam-content">
                            <h3 class="exam-title">Ecocardiograma</h3>
                            <p class="exam-description">Avaliação especializada da função cardíaca através de ultrassom, essencial para diagnóstico de cardiopatias e monitoramento da saúde do coração.</p>
                            <ul class="exam-features">
                                <li><i class="fas fa-check-circle"></i> Especialista em cardiologia</li>
                                <li><i class="fas fa-check-circle"></i> Avaliação funcional</li>
                                <li><i class="fas fa-check-circle"></i> Diagnóstico preciso</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services-section">
            <div class="services-container">
                <div class="section-header">
                    <h2 class="section-title">Nossos Serviços Complementares</h2>
                    <p class="section-subtitle">Além dos exames especializados, oferecemos uma gama completa de serviços para o cuidado integral da saúde do seu pet.</p>
                </div>

                <div class="services-grid">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <h3 class="service-title">Consultório Clínico</h3>
                        <p class="service-description">Atendimento clínico geral com veterinários especializados, consultas de rotina e emergências 24 horas para seu pet.</p>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <h3 class="service-title">Internação Especializada</h3>
                        <p class="service-description">Ambiente controlado e seguro para internação, com cuidados intensivos e monitoramento contínuo da recuperação.</p>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h3 class="service-title">UTI Veterinária</h3>
                        <p class="service-description">Unidade de terapia intensiva com equipamentos avançados e equipe especializada para casos críticos e pós-operatório.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Info -->
        <section class="contact-info">
            <h3>Precisa Agendar um Exame?</h3>
            <p>Entre em contato conosco através do telefone (11) 3456-7890 ou visite nossa clínica para mais informações sobre nossos exames e serviços.</p>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>PetCare</h3>
                <p>Cuidado veterinário de qualidade para todos os tipos de animais de estimação.</p>
                <div class="social-icons">
                    <a href="#"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Links Rápidos</h3>
                <ul>
                    <li><a href="#">Início</a></li>
                    <li><a href="#">Serviços</a></li>
                    <li><a href="#">Equipe</a></li>
                    <li><a href="#">Depoimentos</a></li>
                    <li><a href="#">Contato</a></li>
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
                if (entry.target.classList.contains('card')) {
                    setTimeout(() => {
                        entry.target.style.transform = 'translateY(0) scale(1)';
                    }, 100);
                }
            }
        });
    }, observerOptions);

    // Observar todos os cards e seções
    const animatedElements = document.querySelectorAll('.card, .hero-text, .image-container, .tag, h2');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        observer.observe(el);
    });

    // ==========================================
    // FAQ INTERATIVO
    // ==========================================
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('h3');
        const answer = item.querySelector('p');
        
        // Inicialmente esconder as respostas
        answer.style.maxHeight = '0';
        answer.style.overflow = 'hidden';
        answer.style.transition = 'max-height 0.3s ease, padding 0.3s ease';
        answer.style.paddingTop = '0';
        answer.style.paddingBottom = '0';
        
        question.addEventListener('click', function() {
            const isOpen = item.classList.contains('active');
            
            // Fechar todos os outros items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    const otherAnswer = otherItem.querySelector('p');
                    const otherQuestion = otherItem.querySelector('h3');
                    otherAnswer.style.maxHeight = '0';
                    otherAnswer.style.paddingTop = '0';
                    otherAnswer.style.paddingBottom = '0';
                    otherQuestion.style.background = 'linear-gradient(135deg, var(--white) 0%, rgba(46, 139, 87, 0.02) 100%)';
                }
            });
            
            if (!isOpen) {
                // Abrir este item
                item.classList.add('active');
                answer.style.maxHeight = answer.scrollHeight + 'px';
                answer.style.paddingTop = '0';
                answer.style.paddingBottom = '2rem';
                question.style.background = 'linear-gradient(135deg, rgba(46, 139, 87, 0.08) 0%, rgba(46, 139, 87, 0.04) 100%)';
            } else {
                // Fechar este item
                item.classList.remove('active');
                answer.style.maxHeight = '0';
                answer.style.paddingTop = '0';
                answer.style.paddingBottom = '0';
                question.style.background = 'linear-gradient(135deg, var(--white) 0%, rgba(46, 139, 87, 0.02) 100%)';
            }
        });
        
        // Hover effects
        question.addEventListener('mouseenter', function() {
            if (!item.classList.contains('active')) {
                question.style.background = 'linear-gradient(135deg, rgba(46, 139, 87, 0.05) 0%, rgba(46, 139, 87, 0.02) 100%)';
            }
        });
        
        question.addEventListener('mouseleave', function() {
            if (!item.classList.contains('active')) {
                question.style.background = 'linear-gradient(135deg, var(--white) 0%, rgba(46, 139, 87, 0.02) 100%)';
            }
        });
    });

    // ==========================================
    // FORMULÁRIO DE CONTATO
    // ==========================================
    const contactForm = document.querySelector('.contato form');
    
    if (contactForm) {
        const inputs = contactForm.querySelectorAll('input, textarea');
        
        // Efeitos nos inputs
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.boxShadow = '0 8px 25px rgba(46, 139, 87, 0.2)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
            });
            
            // Validação em tempo real
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = 'rgba(46, 139, 87, 0.5)';
                    this.style.background = 'rgba(46, 139, 87, 0.05)';
                } else {
                    this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    this.style.background = 'rgba(255, 255, 255, 0.1)';
                }
            });
        });
        
        // Envio do formulário
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Animação de envio
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            submitBtn.style.background = 'linear-gradient(135deg, #48B973 0%, #2E8B57 100%)';
            
            // Simular envio (substitua pela sua lógica real)
            setTimeout(() => {
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Enviado!';
                submitBtn.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                
                // Mostrar notificação
                PetCare.showNotification('Mensagem enviada com sucesso! Entraremos em contato em breve.', 'success');
                
                // Resetar formulário
                setTimeout(() => {
                    this.reset();
                    submitBtn.innerHTML = originalText;
                    submitBtn.style.background = 'var(--gradient-primary)';
                    inputs.forEach(input => {
                        input.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                        input.style.background = 'rgba(255, 255, 255, 0.1)';
                    });
                }, 2000);
            }, 1500);
        });
    }

    // ==========================================
    // CARDS INTERATIVOS
    // ==========================================
    const serviceCards = document.querySelectorAll('.servicos .card');
    
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            // Efeito parallax sutil
            const icon = this.querySelector('.icon');
            if (icon) {
                icon.style.transform = 'scale(1.1) rotate(5deg) translateY(-5px)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.icon');
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg) translateY(0px)';
            }
        });
        
        // Efeito de clique
        card.addEventListener('click', function(e) {
            // Criar ondas ripple
            const ripple = document.createElement('div');
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(46, 139, 87, 0.3)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s linear';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.width = ripple.style.height = '20px';
            ripple.style.marginLeft = ripple.style.marginTop = '-10px';
            ripple.style.pointerEvents = 'none';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
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
                            <a href="redefinirsenha.html" class="forgot-password">Esqueci a senha</a>
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
                            Não tem conta? <a href="cadastro.html" id="signupLink">Cadastre-se aqui</a>
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
            
            // Simular login (substitua pela sua lógica real)
            setTimeout(() => {
                PetCare.showNotification('Login realizado com sucesso! Bem-vindo(a)!', 'success');
                closeModal();
                
                // Aqui você pode redirecionar ou atualizar a interface
                // window.location.href = 'dashboard.html';
            }, 2000);
        });
        
        // Login com Google
        googleLoginBtn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando...';
            this.style.pointerEvents = 'none';
            
            // Simular login Google (aqui você implementará a API do Google)
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
        // Aqui você implementará a API do Google
        console.log('Google Auth será implementado aqui');
    }
};