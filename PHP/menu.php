<?php
// Iniciar a sessão (se ainda não estiver iniciada)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Detectar a página atual
$current_script = basename($_SERVER['SCRIPT_NAME']);
$current_path = $_SERVER['REQUEST_URI'];

// Definir classes active para cada item do menu
$home_active          = ($current_script == 'home.php' || $current_script == 'index.php') ? 'active' : '';
$sobre_active         = (strpos($current_path, 'sobre.php')         !== false) ? 'active' : '';
$calendario_active    = (strpos($current_path, 'calendario.php')    !== false) ? 'active' : '';
$servicos_active      = (strpos($current_path, 'servicos')          !== false) ? 'active' : '';
$equipe_active        = (strpos($current_path, 'equipe.php')        !== false) ? 'active' : '';
$perfil_active        = (strpos($current_path, 'perfil.php')        !== false) ? 'active' : '';
$configuracoes_active = (strpos($current_path, 'configuracoes.php') !== false) ? 'active' : '';
$pets_active          = (strpos($current_path, 'pets.php')          !== false) ? 'active' : '';


$abbreviatedName = '';
if (isset($_SESSION['nome'])) {
    $nameParts = explode(' ', $_SESSION['nome']);
    if (count($nameParts) > 0) {
        $abbreviatedName = strtoupper(substr($nameParts[0], 0, 1));
        if (count($nameParts) > 1) {
            $abbreviatedName .= strtoupper(substr($nameParts[count($nameParts)-1], 0, 1));
        }
    }
}


$base_path = '/bruno/TCCPetCareAtualizado/'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $base_path; ?>Estilos/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>PetCare</title>
    
</head>
<body>
    <header>
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <a href="<?php echo $base_path; ?>PHP/Cliente/home.php" class="logo" aria-label="PetCare Home">
            <img src="https://st2.depositphotos.com/5056293/9389/v/450/depositphotos_93899252-stock-illustration-vector-sign-veterinary.jpg"
                alt="PetCare Logo" class="logo-img" loading="lazy">
            PetCare
        </a>
        <ul class="nav-links">
            <li><a href="<?php echo $base_path; ?>PHP/Cliente/home.php" class="<?php echo $home_active; ?>" aria-current="page">Home</a></li>
            <li><a href="<?php echo $base_path; ?>PHP/Cliente/sobre.php" class="<?php echo $sobre_active; ?>" rel="next">Sobre</a></li>
            <li><a href="<?php echo $base_path; ?>PHP/Calendario/calendario.php" class="<?php echo $calendario_active; ?>">Calendario</a></li>
            <li><a href="#servicos" class="<?php echo $servicos_active; ?>">Serviços</a></li>
            <li><a href="<?php echo $base_path; ?>PHP/equipe.php" class="<?php echo $equipe_active; ?>">Equipe</a></li>

            <?php if (isset($_SESSION['nome'])): ?>
                <li class="user-menu">
                    <div class="user-name-container">
                        <span class="user-name"><?php echo htmlspecialchars($abbreviatedName); ?></span>
                        <div class="dropdown-menu">
                            <a href="<?php echo $base_path; ?>PHP/Cliente/perfil.php" class="<?php echo $perfil_active; ?>">Perfil</a>
                            <a href="<?php echo $base_path; ?>configuracoes.php" class="<?php echo $configuracoes_active; ?>">Configurações</a>
                            <a href="<?php echo $base_path; ?>pets.php" class="<?php echo $pets_active; ?>">Pets</a>
                            <a href="<?php echo $base_path; ?>PHP/logout.php">Sair</a>
                        </div>
                    </div>
                </li>

            <?php else: ?>
                <li><a href="#" class="login-btn" aria-label="Fazer login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<?php include 'footer.php';?>
</body>
    

</html>