<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Handle login
if ($_POST && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Debug: Log login attempt
    error_log("Login attempt - Username: " . $username . ", Password: " . $password);
    
    include_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Debug: Check database connection
    error_log("Database connection: " . ($db ? "Connected" : "Not connected"));
    
    $query = "SELECT * FROM users WHERE username = ? AND password = ? AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $password]);
    
    // Debug: Check execution
    error_log("Statement executed: " . ($stmt ? "Yes" : "No"));
    error_log("Row count: " . $stmt->rowCount());
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Log user data
        error_log("User found: " . print_r($user, true));
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        
        // Debug: Log session data
        error_log("Session set - User ID: " . $_SESSION['user_id'] . ", Role: " . $_SESSION['role']);
        
        // Redirect based on role
        switch ($user['role']) {
            case 'owner':
                error_log("Redirecting to owner dashboard");
                header('Location: owner_dashboard.php');
                break;
            case 'admin':
                error_log("Redirecting to admin dashboard");
                header('Location: admin_dashboard.php');
                break;
            case 'staff':
                error_log("Redirecting to staff dashboard");
                header('Location: staff_dashboard.php');
                break;
            default:
                error_log("Unknown role: " . $user['role']);
                header('Location: index.php');
                break;
        }
        exit();
    } else {
        // Login failed
        error_log("Login failed for username: " . $username);
        $_SESSION['login_error'] = "Invalid username or password";
        header('Location: index.php');
        exit();
    }
}

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'owner':
            header('Location: owner_dashboard.php');
            break;
        case 'admin':
            header('Location: admin_dashboard.php');
            break;
        case 'staff':
            header('Location: staff_dashboard.php');
            break;
        default:
            header('Location: index.php');
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System - IBS</title>
    <link rel="stylesheet" href="components/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Original Simple Login Style */
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        
        body.login-body {
            position: relative;
        }
        
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .login-logo {
            width: 60px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        
        .login-logo:hover {
            transform: scale(1.05);
        }
        
        .login-title {
            font-size: 24px;
            color: #333;
            margin: 0;
        }
        
        .login-subtitle {
            font-size: 14px;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            background: #007bff;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .error-message {
            background: #dc3545;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        /* Language Toggle Button - Dashboard Style */
        .language-toggle {
            position: fixed;
            top: 30px;
            right: 30px;
            bottom: auto;
            left: auto;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #333;
            padding: 12px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            z-index: 1000;
            min-height: 44px;
            box-shadow: 0 8px 32px rgba(0, 86, 179, 0.15);
        }
        
        .language-toggle:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.6);
            color: #333;
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(255, 255, 255, 0.2);
        }
        
        .language-toggle:active {
            transform: translateY(-1px) scale(1.02);
            box-shadow: 0 6px 20px rgba(0, 86, 179, 0.2);
        }
        
        .language-toggle i {
            font-size: 18px;
            opacity: 1;
            transition: all 0.3s ease;
        }
        
        .language-toggle:hover i {
            transform: rotate(20deg) scale(1.1);
        }
        
        .language-toggle .lang-text {
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        /* RTL positioning */
        body.rtl .language-toggle {
            left: 30px;
            right: auto;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .language-toggle {
                top: 20px;
                right: 20px;
                padding: 12px 18px;
                min-height: 42px;
            }
            
            body.rtl .language-toggle {
                left: 20px;
                right: auto;
            }
            
            .language-toggle i {
                font-size: 16px;
            }
            
            .language-toggle .lang-text {
                font-size: 12px;
            }
        }
        
        @media (max-width: 480px) {
            .language-toggle {
                top: 15px;
                right: 15px;
                padding: 10px 16px;
                min-height: 40px;
            }
            
            body.rtl .language-toggle {
                left: 15px;
                right: auto;
            }
            
            .language-toggle i {
                font-size: 15px;
            }
            
            .logo-section {
                flex-direction: column;
                gap: 10px;
            }
            
            .login-logo {
                width: 50px;
            }
            
            .login-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body class="login-body">
    <!-- Language Toggle Button - Dashboard Style -->
    <button class="language-toggle" id="languageToggle" onclick="toggleLanguage()" title="Toggle Language">
        <i class="fas fa-language"></i>
        <span class="lang-text">EN</span>
    </button>
    
    <div class="login-container">
        <div class="login-header">
            <div class="logo-section">
                <img src="components/css/logo.jpeg" alt="IBS Store Logo" class="login-logo" />
                <h1 class="login-title">IBS - Inventory Management System</h1>
            </div>
            <p class="login-subtitle">Please login to continue</p>
        </div>
        
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($_SESSION['login_error']); ?>
            </div>
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Enter username" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter password">
            </div>
            
            <button type="submit" name="login" class="btn">Login</button>
        </form>
    </div>
    
    <script>
        // Language translations
        const translations = {
            en: {
                title: "IBS - Inventory Management System",
                subtitle: "Please login to continue",
                username: "Username:",
                password: "Password:",
                login: "Login",
                usernamePlaceholder: "Enter username",
                passwordPlaceholder: "Enter password"
            },
            ar: {
                title: "نظام إدارة المخزون - IBS",
                subtitle: "يرجى تسجيل الدخول للمتابعة",
                username: "اسم المستخدم:",
                password: "كلمة المرور:",
                login: "تسجيل الدخول",
                usernamePlaceholder: "أدخل اسم المستخدم",
                passwordPlaceholder: "أدخل كلمة المرور"
            }
        };
        
        // Language cycling
        const languages = ['en', 'ar'];
        const langCodes = {
            'en': 'EN',
            'ar': 'AR'
        };
        
        let currentLangIndex = 0;
        
        // Toggle language function
        function toggleLanguage() {
            currentLangIndex = (currentLangIndex + 1) % languages.length;
            const lang = languages[currentLangIndex];
            
            // Update text content
            const t = translations[lang];
            document.querySelector('.login-title').textContent = t.title;
            document.querySelector('.login-subtitle').textContent = t.subtitle;
            document.querySelector('label[for="username"]').textContent = t.username;
            document.querySelector('label[for="password"]').textContent = t.password;
            document.querySelector('.btn').textContent = t.login;
            
            // Update placeholders
            document.getElementById('username').placeholder = t.usernamePlaceholder;
            document.getElementById('password').placeholder = t.passwordPlaceholder;
            
            // Update button text
            document.querySelector('.lang-text').textContent = langCodes[lang];
            
            // Save language preference
            localStorage.setItem('preferredLanguage', lang);
            
            // Change text direction for Arabic
            if (lang === 'ar') {
                document.body.classList.add('rtl');
                document.body.style.direction = 'rtl';
                document.body.style.textAlign = 'right';
            } else {
                document.body.classList.remove('rtl');
                document.body.style.direction = 'ltr';
                document.body.style.textAlign = 'left';
            }
        }
        
        // Load saved language preference
        window.addEventListener('DOMContentLoaded', function() {
            const savedLang = localStorage.getItem('preferredLanguage') || 'en';
            currentLangIndex = languages.indexOf(savedLang);
            if (currentLangIndex === -1) currentLangIndex = 0;
            
            // Set initial language
            const lang = languages[currentLangIndex];
            const t = translations[lang];
            
            document.querySelector('.login-title').textContent = t.title;
            document.querySelector('.login-subtitle').textContent = t.subtitle;
            document.querySelector('label[for="username"]').textContent = t.username;
            document.querySelector('label[for="password"]').textContent = t.password;
            document.querySelector('.btn').textContent = t.login;
            document.querySelector('.lang-text').textContent = langCodes[lang];
            
            document.getElementById('username').placeholder = t.usernamePlaceholder;
            document.getElementById('password').placeholder = t.passwordPlaceholder;
            
            // Set initial direction
            if (lang === 'ar') {
                document.body.classList.add('rtl');
                document.body.style.direction = 'rtl';
                document.body.style.textAlign = 'right';
            }
        });
    </script>
</body>
</html>
