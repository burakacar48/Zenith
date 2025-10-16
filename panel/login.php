
<?php
require_once "config.php";

// Eğer kullanıcı zaten giriş yapmışsa, ana sayfaya yönlendir
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

$username = $password = "";
$username_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["username"]))){
        $username_err = "Lütfen kullanıcı adınızı girin.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Lütfen şifrenizi girin.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($username_err) && empty($password_err)){
        $sql = "SELECT id, username, password_hash FROM admins WHERE username = ?";
        
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            
            if($stmt->execute()){
                $stmt->store_result();
                
                if($stmt->num_rows == 1){                    
                    $stmt->bind_result($id, $username, $hashed_password);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            header("location: index.php");
                        } else{
                            $login_err = "Geçersiz kullanıcı adı veya şifre.";
                        }
                    }
                } else{
                    $login_err = "Geçersiz kullanıcı adı veya şifre.";
                }
            } else{
                echo "Bir şeyler ters gitti. Lütfen daha sonra tekrar deneyin.";
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zenith License Panel - Güvenli Giriş</title>
    
    <!-- Meta Tags -->
    <meta name="description" content="Zenith License Panel - Güvenli admin girişi">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- External Resources -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="modern_admin.css">
    
    <style>
        /* Login specific styles */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .login-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                linear-gradient(135deg, rgba(15, 15, 15, 0.9) 0%, rgba(26, 26, 26, 0.8) 100%),
                radial-gradient(circle at 20% 30%, rgba(229, 9, 20, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(229, 9, 20, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(229, 9, 20, 0.06) 0%, transparent 50%),
                url('images/background.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            z-index: -1;
        }
        
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--gradient-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-primary);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: var(--shadow-xl), 0 0 40px rgba(229, 9, 20, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--gradient-primary);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow:
                var(--shadow-glow),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            transition: all var(--transition-normal);
        }
        
        .login-icon:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow:
                0 15px 35px rgba(229, 9, 20, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }
        
        .login-logo-image {
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: brightness(1.2) contrast(1.1);
            transition: all var(--transition-normal);
        }
        
        .login-icon:hover .login-logo-image {
            transform: scale(1.1);
            filter: brightness(1.4) contrast(1.3);
        }
        
        .login-icon::after {
            content: '';
            position: absolute;
            inset: 2px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), transparent);
            pointer-events: none;
        }
        
        .login-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--text-secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .floating-label {
            position: relative;
        }
        
        .floating-label input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(42, 42, 42, 0.5);
            border: 1px solid var(--border-primary);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all var(--transition-normal);
        }
        
        .floating-label input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
            background: rgba(42, 42, 42, 0.8);
        }
        
        .floating-label input.error {
            border-color: var(--error);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .floating-label .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-disabled);
            transition: color var(--transition-normal);
        }
        
        .floating-label input:focus ~ .input-icon {
            color: var(--primary-red);
        }
        
        .login-button {
            width: 100%;
            padding: 1rem 2rem;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg), var(--shadow-glow);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .login-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left var(--transition-normal);
        }
        
        .login-button:hover:before {
            left: 100%;
        }
        
        .error-message {
            background: var(--error-bg);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .security-notice {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .login-icon {
                width: 64px;
                height: 64px;
            }
            
            .login-title {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-background"></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <img src="images/PanelLogo.png" alt="Panel Logo" class="login-logo-image">
                </div>
                <h1 class="login-title">Zenith Panel</h1>
                <p class="login-subtitle">Güvenli yönetim portalına hoş geldiniz</p>
            </div>
            
            <?php if(!empty($login_err)): ?>
                <div class="error-message">
                    <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?php echo $login_err; ?></span>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form" id="loginForm">
                <div class="floating-label">
                    <input 
                        name="username" 
                        type="text" 
                        class="<?php echo (!empty($username_err)) ? 'error' : ''; ?>"
                        value="<?php echo htmlspecialchars($username); ?>" 
                        placeholder="Kullanıcı adınızı girin"
                        required
                        autocomplete="username"
                    >
                    <i data-lucide="user" class="input-icon w-5 h-5"></i>
                </div>
                
                <div class="floating-label">
                    <input 
                        name="password" 
                        type="password" 
                        class="<?php echo (!empty($password_err)) ? 'error' : ''; ?>"
                        placeholder="Şifrenizi girin"
                        required
                        autocomplete="current-password"
                    >
                    <i data-lucide="lock" class="input-icon w-5 h-5"></i>
                </div>
                
                <button type="submit" class="login-button" id="loginBtn">
                    <i data-lucide="log-in" class="w-5 h-5"></i>
                    <span>Güvenli Giriş</span>
                    <div class="loading"></div>
                </button>
            </form>
            
            <div class="security-notice">
                <div class="flex items-center justify-center gap-2 mb-2">
                    <i data-lucide="shield" class="w-4 h-4"></i>
                    <span class="font-semibold">Güvenlik Bildirimi</span>
                </div>
                <p>Bu sistem yetkili personel tarafından kullanılmak üzere tasarlanmıştır. Tüm oturum aktiviteleri kayıt altına alınmaktadır.</p>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            // Form submission handling
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const loadingSpinner = loginBtn.querySelector('.loading');
            
            loginForm.addEventListener('submit', function(e) {
                // Show loading state
                loginBtn.disabled = true;
                loginBtn.style.opacity = '0.8';
                loadingSpinner.style.display = 'block';
                
                // Reset after 5 seconds if no response
                setTimeout(() => {
                    loginBtn.disabled = false;
                    loginBtn.style.opacity = '1';
                    loadingSpinner.style.display = 'none';
                }, 5000);
            });
            
            // Input focus effects
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.activeElement.blur();
                }
            });
            
            // Auto-clear error messages
            setTimeout(() => {
                const errorMsg = document.querySelector('.error-message');
                if (errorMsg) {
                    errorMsg.style.opacity = '0';
                    errorMsg.style.transform = 'translateY(-10px)';
                    setTimeout(() => errorMsg.remove(), 300);
                }
            }, 5000);
        });
    </script>
</body>
</html>