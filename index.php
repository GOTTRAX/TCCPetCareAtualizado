<?php
// Inicia a sessão e inclui a conexão NO TOPO do arquivo
session_start();
ob_start(); // Inicia o buffer de saída para redirecionamentos funcionarem corretamente
include("PHP/conexao.php"); // conexão PDO como $pdo

// Inicializa variáveis de mensagem 1
$erro = '';
$sucesso = '';

// Verifica se é uma requisição POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? '';
    $password = $_POST["password"] ?? '';

    try {
        $sql = "SELECT id, nome, telefone, email, senha_hash, datanasc, genero, tipo_usuario, ativo, bloqueado_ate 
                FROM Usuarios 
                WHERE email = :email";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // 🔹 1. Checa se a conta está ativa
            if (!$usuario["ativo"]) {
                $erro = "Sua conta está inativa. Contate o administrador.";
            }
            // 🔹 2. Checa se está bloqueado
            elseif (!is_null($usuario["bloqueado_ate"]) && strtotime($usuario["bloqueado_ate"]) > time()) {
                $erro = "Sua conta está bloqueada até " . date("d/m/Y H:i", strtotime($usuario["bloqueado_ate"]));
            }
            // 🔹 3. Senha correta?
            elseif (password_verify($password, $usuario["senha_hash"])) {
                // Define variáveis de sessão
                $_SESSION["id"] = $usuario["id"];
                $_SESSION["nome"] = $usuario["nome"];
                $_SESSION["telefone"] = $usuario["telefone"];
                $_SESSION["email"] = $usuario["email"];
                $_SESSION["datanasc"] = $usuario["datanasc"];
                $_SESSION["genero"] = $usuario["genero"];
                $_SESSION["tipo_usuario"] = $usuario["tipo_usuario"];

                // Atualiza último login
                $updateLogin = $pdo->prepare("UPDATE Usuarios SET ultimo_login = NOW() WHERE id = :id");
                $updateLogin->execute([':id' => $usuario["id"]]);

                // Redireciona conforme o tipo
                switch ($usuario["tipo_usuario"]) {
                    case "Veterinario":
                        header("Location: PHP/Vet/vet_home.php");
                        exit;
                    case "Secretaria":
                        header("Location: PHP/Secretaria/sec_home.php");
                        exit;
                    case "Cliente":
                        header("Location: PHP/Cliente/home.php");
                        exit;
                    default:
                        header("Location: PHP/Cuidadores/home.php");
                        exit;
                }
            } else {
                $erro = "Senha incorreta.";
            }
        } else {
            $erro = "Usuário não encontrado.";
        }
    } catch (PDOException $e) {
        $erro = "Erro no login. Tente novamente mais tarde.";
        // Você pode logar o erro real ($e->getMessage()) em produção para debug
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Faça login na PetCare para acessar serviços veterinários exclusivos para seu pet">
    <meta name="keywords" content="login, petcare, veterinária, cuidado pet">
    <meta name="author" content="PetCare">
    <title>Login - PetCare</title>

    <link rel="icon" type="image/png" href="https://img.icons8.com/ios/452/cat.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous">

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
            --text-dark: #2C3E50;
            --text-light: #7F8C8D;
            --background-light: #F8F9FA;
            --white: #FFFFFF;
            --accent-color: #FF6B6B;
            --gradient-primary: linear-gradient(135deg, #7d8a83 0%, #48B973 100%);
            --gradient-secondary: linear-gradient(135deg, #F0A500 0%, #FFB84D 100%);
            --shadow-light: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.15);
            --shadow-large: 0 20px 40px rgba(0, 0, 0, 0.1);
            --shadow-glow: 0 0 30px rgba(46, 139, 87, 0.2);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: linear-gradient(135deg, var(--background-light) 0%, rgba(46, 139, 87, 0.05) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -20%;
            left: -20%;
            width: 140%;
            height: 140%;
            background: linear-gradient(45deg,
                    transparent 0%,
                    rgba(46, 139, 87, 0.03) 25%,
                    rgba(46, 139, 87, 0.05) 50%,
                    rgba(46, 139, 87, 0.03) 75%,
                    transparent 100%);
            z-index: -2;
            animation: backgroundFloat 20s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: fixed;
            top: 10%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: var(--gradient-primary);
            border-radius: 50%;
            opacity: 0.05;
            z-index: -1;
            animation: floatCircle 25s linear infinite;
        }

        @keyframes backgroundFloat {

            0%,
            100% {
                transform: translateX(-5%) rotate(0deg);
            }

            50% {
                transform: translateX(5%) rotate(1deg);
            }
        }

        @keyframes floatCircle {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(-100px, -50px) rotate(120deg);
            }

            66% {
                transform: translate(50px, 100px) rotate(240deg);
            }

            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(46, 139, 87, 0.1);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            gap: 0.5rem;
        }

        .logo-img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            transition: var(--transition);
        }

        .logo:hover .logo-img {
            transform: scale(1.1) rotate(5deg);
            border-color: var(--secondary-color);
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            text-decoration: none;
            transition: var(--transition);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 500;
        }

        .back-btn:hover {
            color: var(--primary-color);
            background: rgba(46, 139, 87, 0.1);
            transform: translateX(-5px);
        }

        .login-container {
            background: var(--white);
            padding: 2.5rem 2rem;
            border-radius: 20px;
            max-width: 420px;
            width: 100%;
            position: relative;
            animation: slideInScale 0.6s ease;
            box-shadow: var(--shadow-large);
            border: 1px solid rgba(46, 139, 87, 0.1);
            margin-top: 80px;
            backdrop-filter: blur(10px);
        }

        @keyframes slideInScale {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        .login-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.8rem;
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-header p {
            color: var(--text-light);
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.5;
        }

        .login-form {
            display: grid;
            gap: 1.2rem;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            transition: color 0.3s ease;
            z-index: 2;
            font-size: 0.9rem;
        }

        .input-group input {
            width: 100%;
            padding: 0.9rem 0.9rem 0.9rem 42px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: 'Montserrat', sans-serif;
            background: var(--white);
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46, 139, 87, 0.1);
            transform: scale(1.02);
        }

        .input-group input:focus+i {
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.1);
        }

        .login-btn {
            width: 100%;
            padding: 0.9rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Montserrat', sans-serif;
            background: var(--gradient-primary);
            color: white;
            position: relative;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-glow);
        }

        .login-btn:active {
            transform: translateY(0);
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
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        .google-login-btn {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #dadce0;
            border-radius: 4px;
            background: #ffffff;
            color: #1f1f1f;
            font-size: 14px;
            font-weight: 500;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .google-login-btn:hover {
            border-color: #4285f4;
            background: #f8f9fa;
            box-shadow: 0 1px 3px 1px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .google-login-btn:active {
            background: #eeeeee;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
            transform: translateY(0);
        }

        .google-login-btn img {
            width: 18px;
            height: 18px;
        }

        .signup-link {
            text-align: center;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .signup-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .forgot-password-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .forgot-password-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-password-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .remember-me input[type="checkbox"] {
            accent-color: var(--primary-color);
            width: 16px;
            height: 16px;
            cursor: pointer;
            transition: var(--transition);
        }

        .remember-me input[type="checkbox"]:hover {
            transform: scale(1.1);
        }

        .input-group.valid input {
            border-color: var(--primary-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232E8B57'%3e%3cpath d='M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
        }

        .input-group.invalid input {
            border-color: var(--accent-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23FF6B6B'%3e%3cpath d='M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z'/%3e%3cpath d='M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
        }

        .error-message {
            color: var(--accent-color);
            font-size: 0.8rem;
            margin-top: 0.3rem;
            display: none;
        }

        .input-group.invalid .error-message {
            display: block;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            box-shadow: var(--shadow-medium);
            max-width: 300px;
        }

        .toast.success {
            background: var(--primary-color);
        }

        .toast.error {
            background: var(--accent-color);
        }

        .toast.info {
            background: #3B82F6;
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

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 1rem;
            }

            .login-container {
                padding: 2rem 1.5rem;
                margin-top: 70px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem 1rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <header class="header" role="banner">
        <a href="/PetCare/" class="logo" aria-label="PetCare Home">
            <img src="https://st2.depositphotos.com/5056293/9389/v/450/depositphotos_93899252-stock-illustration-vector-sign-veterinary.jpg"
                alt="PetCare Logo" class="logo-img" loading="lazy">
            PetCare
        </a>
        <a href="/PetCare/" class="back-btn" aria-label="Voltar à página inicial">
            <i class="fas fa-arrow-left"></i>
            Voltar à página inicial
        </a>
    </header>

    <div class="login-container" aria-labelledby="login-header-title">
        <div class="login-header">
            <h1 id="login-header-title">
                <i class="fas fa-sign-in-alt"></i>
                Fazer Login
            </h1>
            <p>Acesse sua conta PetCare para cuidar do seu pet</p>
        </div>

        <!-- O formulário agora envia os dados para ESTE MESMO arquivo (<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>) -->
        <form id="loginForm" class="login-form" method="POST"
            action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <!-- Campo de token CSRF (adicionado por segurança, sua lógica original já tinha) -->
            <input type="hidden" name="csrf_token"
                value="<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : ''; ?>">

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Seu e-mail" required aria-label="E-mail"
                    aria-describedby="email-error"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <div class="error-message" id="email-error">Por favor, insira um e-mail válido</div>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Senha" required aria-label="Senha"
                    aria-describedby="password-error">
                <div class="error-message" id="password-error">Por favor, insira sua senha</div>
            </div>
            <div class="forgot-password-container">
                <label class="remember-me">
                    <input type="checkbox" id="rememberMe" name="remember_me" aria-label="Lembrar de mim">
                    Lembrar de mim
                </label>
                <div class="forgot-password-link">
                    <a href="/PetCare/PHP/Cliente/redefinirsenha.php" id="forgotPasswordLink">Esqueceu sua senha?</a>
                </div>
            </div>
            <button type="submit" class="login-btn" aria-label="Entrar">
                <i class="fas fa-sign-in-alt"></i>
                Entrar
            </button>
        </form>

        <div class="divider">
            <span>ou</span>
        </div>

        <button type="button" id="googleLogin" class="google-login-btn" aria-label="Entrar com Google">
            <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google Logo" loading="lazy">
            Entrar com Google
        </button>

        <div class="signup-link">
            Não tem uma conta? <a href="PHP/registro.php" id="signupLink">Cadastre-se aqui</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Display server-side messages
            <?php if (!empty($erro)): ?>
                PetCare.showNotification(<?php echo json_encode($erro); ?>, 'error');
            <?php endif; ?>
            <?php if (!empty($sucesso)): ?>
                PetCare.showNotification(<?php echo json_encode($sucesso); ?>, 'success');
            <?php endif; ?>

            // Form validation and submission
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const googleLoginBtn = document.getElementById('googleLogin');

            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();
                let isValid = true;

                // Validate email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email || !emailRegex.test(email)) {
                    emailInput.parentElement.classList.add('invalid');
                    emailInput.parentElement.classList.remove('valid');
                    isValid = false;
                } else {
                    emailInput.parentElement.classList.add('valid');
                    emailInput.parentElement.classList.remove('invalid');
                }

                // Validate password
                if (!password) {
                    passwordInput.parentElement.classList.add('invalid');
                    passwordInput.parentElement.classList.remove('valid');
                    isValid = false;
                } else {
                    passwordInput.parentElement.classList.add('valid');
                    passwordInput.parentElement.classList.remove('invalid');
                }

                if (isValid) {
                    const submitBtn = this.querySelector('.login-btn');
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
                    submitBtn.disabled = true;
                    this.submit();
                } else {
                    PetCare.showNotification('Por favor, corrija os erros no formulário', 'error');
                }
            });

            // Real-time validation
            [emailInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    const parent = this.parentElement;
                    if (this.id === 'email') {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (this.value.trim() && emailRegex.test(this.value.trim())) {
                            parent.classList.add('valid');
                            parent.classList.remove('invalid');
                        } else if (this.value.trim()) {
                            parent.classList.add('invalid');
                            parent.classList.remove('valid');
                        } else {
                            parent.classList.remove('valid', 'invalid');
                        }
                    } else if (this.id === 'password') {
                        if (this.value.trim()) {
                            parent.classList.add('valid');
                            parent.classList.remove('invalid');
                        } else {
                            parent.classList.add('invalid');
                            parent.classList.remove('valid');
                        }
                    }
                });
            });

            // Google login (placeholder)
            googleLoginBtn.addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando...';
                this.disabled = true;
                
                setTimeout(() => {
                    PetCare.showNotification('Login com Google será implementado em breve!', 'info');
                    this.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="Google Logo"> Entrar com Google';
                    this.disabled = false;
                }, 1500);
            });

            // Animation on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            const animatedElements = document.querySelectorAll('.login-container, .login-header h1, .login-header p');
            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                observer.observe(el);
            });

            // Notification utility
            window.PetCare = {
                showNotification: function(message, type = 'success') {
                    const notification = document.createElement('div');
                    notification.className = `toast ${type}`;
                    notification.textContent = message;
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.style.animation = 'slideOutRight 0.3s ease forwards';
                        setTimeout(() => notification.remove(), 300);
                    }, 4000);
                }
            };
        });
    </script>
</body>

</html>
<?php ob_end_flush(); ?>