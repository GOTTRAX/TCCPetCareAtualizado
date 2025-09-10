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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós - PetCare Clínica Veterinária</title>
    
    <link rel="stylesheet" href="TCCPetCareAtualizado/Estilos/vacinas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap');
    </style>
</head>
<body>
   <?php include '../menu.php'; ?>

  <!-- HERO -->
  <section class="hero-vacina">
    <h1>Vacinação Profissional PetCare</h1>
    <p>Protocolos personalizados para cães e gatos, com atendimento clínico especializado para garantir saúde, proteção e bem-estar ao seu melhor amigo.</p>
  </section>

  <!-- BENEFÍCIOS -->
  <section class="section beneficios">
    <h2>Por que Escolher a PetCare?</h2>
    <p style="text-align: center; max-width: 900px; margin: 0 auto 40px;">Na PetCare, combinamos expertise veterinária com paixão por animais, oferecendo cuidados que vão além da vacinação. Nosso compromisso é proporcionar saúde, segurança e felicidade para seu pet em cada etapa da vida.</p>
    <div class="card">
      <i class="fas fa-user-md" aria-label="Ícone de veterinário"></i>
      <h3>Avaliação Veterinária de Excelência</h3>
      <p>Antes de qualquer vacina, realizamos exames completos para garantir que seu pet está pronto para a imunização, com total segurança.</p>
      <span class="highlight">Check-up detalhado em cada visita</span>
    </div>
    <div class="card">
      <i class="fas fa-syringe" aria-label="Ícone de seringa"></i>
      <h3>Vacinas Premium</h3>
      <p>Usamos vacinas de marcas globais, protegendo contra doenças como parvovirose, cinomose e rinotraqueíte, armazenadas em condições ideais para máxima eficácia.</p>
      <span class="highlight">Qualidade comprovada, sempre</span>
    </div>
    <div class="card">
      <i class="fas fa-calendar-alt" aria-label="Ícone de calendário"></i>
      <h3>Imunização Contínua</h3>
      <p>Nossos protocolos seguem diretrizes internacionais, com reforços anuais, incluindo a antirrábica, para manter seu pet protegido o ano todo.</p>
      <span class="highlight">Lembretes automáticos para reforços</span>
    </div>
    <div class="card">
      <i class="fas fa-home" aria-label="Ícone de casa"></i>
      <h3>Ambiente Acolhedor</h3>
      <p>Uma clínica projetada para o conforto do seu pet, com equipe experiente e atendimento humanizado que faz a diferença.</p>
      <span class="highlight">Seu pet se sente em casa</span>
    </div>
  </section>

  <!-- JORNADA DE VACINAÇÃO -->
  <section class="section vacina-journey">
    <h2>Jornada de Proteção do Seu Pet</h2>
    <p>Explore a jornada de vacinação do seu pet, desde os primeiros dias até a proteção contínua na vida adulta, com um plano visual que torna o cuidado mais envolvente.</p>
    <div class="vacina-timeline">
      <div class="timeline-item">
        <div class="timeline-content">
          <h3>Avaliação Inicial (30–45 dias)</h3>
          <p>Uma consulta inicial detalhada avalia a saúde do seu pet, preparando-o para o início do protocolo de vacinação.</p>
        </div>
        <div class="timeline-dot"></div>
        <div class="timeline-image">
          <img src="https://i.pinimg.com/736x/d4/87/60/d487603c9eb2b66db2e4271255a7d403.jpg" alt="Veterinário examinando um filhote de gato durante consulta inicial">
          <p class="img-caption">Consulta inicial com um filhote de gato</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-content">
          <h3>Primeiros Dias (45–60 dias)</h3>
          <p>Iniciamos com vacinas V8/V10 (cães), contra cinomose e parvovirose, e V3–V5 (gatos), contra rinotraqueíte e calicivirose, protegendo desde cedo.</p>
        </div>
        <div class="timeline-dot"></div>
        <div class="timeline-image">
          <img src="https://i.pinimg.com/736x/85/d5/44/85d5440fa384d38db4aa23158cdeae63.jpg" alt="Filhote de cão recebendo a primeira vacina">
          <p class="img-caption">Primeira vacina para filhotes</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-content">
          <h3>Reforços Iniciais (3–4 semanas)</h3>
          <p>Reforços regulares a cada 3–4 semanas até 16 semanas garantem imunidade robusta e proteção duradoura.</p>
        </div>
        <div class="timeline-dot"></div>
        <div class="timeline-image">
          <img src="https://i.pinimg.com/736x/ec/9d/d5/ec9dd55a1f5676757da765d9b2d01679.jpg" alt="Veterinário aplicando vacina de reforço em um filhote">
          <p class="img-caption">Consulta para reforços iniciais</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-content">
          <h3>Antirrábica (12 semanas)</h3>
          <p>A vacina antirrábica é aplicada a partir de 12 semanas, essencial para a segurança do pet e da comunidade.</p>
        </div>
        <div class="timeline-dot"></div>
        <div class="timeline-image">
          <img src="https://i.pinimg.com/1200x/f6/fc/2d/f6fc2dec181e6b4c183952d15b1620ec.jpg" alt="Cão recebendo vacina antirrábica">
          <p class="img-caption">Proteção contra raiva</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-content">
          <h3>Manutenção Anual</h3>
          <p>Reforços anuais e vacinas opcionais, como gripe canina para cães ou FeLV para gatos, ajustados ao estilo de vida do seu pet.</p>
        </div>
        <div class="timeline-dot"></div>
        <div class="timeline-image">
          <img src="https://i.pinimg.com/736x/29/60/22/29602249f0f4e15fa1b692a917b19128.jpg" alt="Cão adulto sendo vacinado">
          <p class="img-caption">Manutenção para pets adultos</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-content">
          <h3>Monitoramento Contínuo</h3>
          <p>Consultas regulares garantem que o plano de vacinação permanece atualizado, adaptado às necessidades do seu pet.</p>
        </div>
        <div class="timeline-dot"></div>
        <div class="timeline-image">
          <img src="https://i.pinimg.com/1200x/6e/e2/34/6ee23404e8415eac4ba4a24a8ae0b468.jpg" alt="Veterinário examinando um cão saudável em consulta de rotina">
          <p class="img-caption">Acompanhamento de um cão saudável</p>
        </div>
      </div>
      <div class="timeline-item">
        <div class="timeline-content">
          <h3>Cuidados Personalizados</h3>
          <p>Adaptamos o plano ao estilo de vida do seu pet, como vacinas para gripe canina para quem frequenta creches ou FeLV para gatos com acesso à rua.</p>
        </div>
        <div class="timeline-dot"></div>
        <div class="timeline-image">
          <img src="https://i.pinimg.com/736x/ad/20/26/ad2026bf8333049f6167a3767bcc8293.jpg" alt="Cão e gato saudáveis após vacinação personalizada">
          <p class="img-caption">Saúde e felicidade garantidas</p>
        </div>
      </div>
    </div>
  </section>

  <!-- RODAPÉ -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-section">
        <h3>PetCare</h3>
        <p>Cuidado veterinário de qualidade para todos os tipos de animais de estimação.</p>
        <div class="social-icons">
          <a href="#" aria-label="Facebook da PetCare"><i class="fa-brands fa-facebook"></i></a>
          <a href="#" aria-label="Instagram da PetCare"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="Twitter da PetCare"><i class="fa-brands fa-twitter"></i></a>
          <a href="#" aria-label="YouTube da PetCare"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>
      <div class="footer-section">
        <h3>Links Rápidos</h3>
        <ul>
          <li><a href="index.php">Início</a></li>
          <li><a href="servicos.php">Serviços</a></li>
          <li><a href="equipe.php">Equipe</a></li>
          <li><a href="depoimentos.php">Depoimentos</a></li>
          <li><a href="contato.php">Contato</a></li>
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

    document.addEventListener('DOMContentLoaded', function () {
    // === Menu Hambúrguer para Navegação Mobile ===
    const navbar = document.querySelector('.navbar');
    const navLinks = document.querySelector('.nav-links');
    const hamburger = document.createElement('div');
    hamburger.classList.add('hamburger');
    hamburger.innerHTML = '<i class="fas fa-bars"></i>';
    hamburger.setAttribute('aria-label', 'Abrir menu de navegação');
    hamburger.setAttribute('role', 'button');
    hamburger.setAttribute('tabindex', '0');
    if (navbar) navbar.appendChild(hamburger);

    // Estilizar o menu hambúrguer
    const style = document.createElement('style');
    style.innerHTML = `
        .hamburger {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary-color);
            transition: var(--transition);
        }
        .hamburger:hover, .hamburger:focus {
            color: var(--primary-dark);
        }
        .nav-links.active {
            display: flex;
            flex-direction: column;
            position: absolute;
            top: 70px;
            left: 0;
            width: 100%;
            background: var(--white);
            padding: 1rem;
            box-shadow: var(--shadow-light);
            z-index: 999;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .hamburger {
                display: block;
            }
            .nav-links {
                display: none;
            }
            .nav-links.active {
                display: flex;
            }
        }
    `;
    document.head.appendChild(style);

    // Alternar menu hambúrguer
    function toggleMenu() {
        navLinks.classList.toggle('active');
        hamburger.innerHTML = navLinks.classList.contains('active')
            ? '<i class="fas fa-times"></i>'
            : '<i class="fas fa-bars"></i>';
        hamburger.setAttribute('aria-label', navLinks.classList.contains('active') ? 'Fechar menu de navegação' : 'Abrir menu de navegação');
    }

    hamburger.addEventListener('click', toggleMenu);
    hamburger.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleMenu();
        }
    });

    // Fechar menu ao clicar em um link
    if (navLinks) {
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                hamburger.innerHTML = '<i class="fas fa-bars"></i>';
                hamburger.setAttribute('aria-label', 'Abrir menu de navegação');
            });
        });
    }

    // === Smooth Scroll para Links de Navegação ===
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80, // Ajuste para header fixo
                    behavior: 'smooth'
                });
            }
        });
    });

    // === Animações de Entrada para Seções ===
    const sections = document.querySelectorAll('.section, .timeline-item');
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const sectionObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Para animação única
            }
        });
    }, observerOptions);

    sections.forEach(section => sectionObserver.observe(section));

    // === Interatividade nos Cards ===
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('click', () => {
            cards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            card.style.transform = 'scale(1.05)';
            card.style.borderColor = 'var(--primary-dark)';
            setTimeout(() => {
                card.style.transform = 'scale(1)';
                card.style.borderColor = 'var(--primary-color)';
                card.classList.remove('active');
            }, 300);
        });
        card.setAttribute('tabindex', '0');
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                card.click();
            }
        });
    });

    // === Controle do Modal ===
    const modal = document.querySelector('.modal');
    const ctaButton = document.querySelector('.cta .btn');
    const closeModalBtn = document.querySelector('.modal-content .close');
    const modalContent = document.querySelector('.modal-content');

    function openModal() {
        if (modal) {
            modal.style.display = 'flex';
            modalContent.focus();
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    if (ctaButton) {
        ctaButton.addEventListener('click', openModal);
        ctaButton.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openModal();
            }
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
        closeModalBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                closeModal();
            }
        });
    }

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });
    }

    // === Validação de Formulário no Modal (se aplicável) ===
    const modalForm = document.querySelector('.modal-content form');
    if (modalForm) {
        modalForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const inputs = modalForm.querySelectorAll('input[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = 'var(--error-color, #EF4444)';
                    const errorMessage = document.createElement('span');
                    errorMessage.className = 'error-message';
                    errorMessage.style.color = 'var(--error-color, #EF4444)';
                    errorMessage.style.fontSize = '0.8rem';
                    errorMessage.textContent = 'Este campo é obrigatório.';
                    if (!input.nextElementSibling?.classList.contains('error-message')) {
                        input.parentElement.appendChild(errorMessage);
                    }
                } else {
                    input.style.borderColor = 'var(--primary-color)';
                    if (input.nextElementSibling?.classList.contains('error-message')) {
                        input.nextElementSibling.remove();
                    }
                }
            });

            if (isValid) {
                // Simular envio (substitua por chamada real ao backend)
                alert('Formulário enviado com sucesso!');
                closeModal();
                modalForm.reset();
            }
        });
    }

    // === Ajuste de Acessibilidade ===
    document.querySelectorAll('a, button, .card').forEach(element => {
        element.addEventListener('focus', () => {
            element.style.outline = '2px solid var(--primary-color)';
        });
        element.addEventListener('blur', () => {
            element.style.outline = 'none';
        });
    });
});



  </script>
</body>
</html>