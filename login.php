<?php
session_start();
include 'includes/db_connect.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/index.php");
    } elseif ($_SESSION['role'] == 'staff') {
        header("Location: staff/index.php");
    } else {
        header("Location: student/index.php");
    }
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $conn->prepare("SELECT id, email, password, role, full_name, department FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['department'] = $user['department'];  // ← ADD THIS LINE
                
                if ($user['role'] == 'admin') {
                    header("Location: admin/index.php");
                } elseif ($user['role'] == 'staff') {
                    header("Location: staff/index.php");
                } else {
                    header("Location: student/index.php");
                }
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KSTU Clearance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            padding: 20px;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Main Container */
        .login-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 900px;
            display: flex;
            overflow: hidden;
            animation: slideIn 0.8s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        /* Left Side */
        .animation-side {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .animation-side h2 {
            font-size: 28px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: 700;
            animation: fadeInUp 0.8s ease-out 0.3s both;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .animation-side p {
            font-size: 15px;
            opacity: 0.95;
            animation: fadeInUp 0.8s ease-out 0.5s both;
        }
        
        /* SVG Cartoon Character */
        .cartoon-container {
            width: 60%;
            max-width: 200px;
            margin: 0 auto;
            animation: floatBody 3s ease-in-out infinite;
        }
        
        @keyframes floatBody {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        /* The Graduation Cap Animation */
        #grad-cap {
            animation: liftHat 4s ease-in-out infinite;
            transform-origin: 50px 35px; /* Pivot point at the base of the hat */
        }
        
        @keyframes liftHat {
            0%, 20% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-25px) rotate(-20deg); } /* Lifts off and tilts */
            80%, 100% { transform: translateY(0) rotate(0deg); }
        }
        
        /* Right Side */
        .form-side {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-header {
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease-out 0.2s both;
        }
        
        .form-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
        }
        
        .form-header p {
            color: #666;
            font-size: 14px;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Floating Inputs */
        .form-group {
            margin-bottom: 25px;
            position: relative;
            animation: fadeInUp 0.8s ease-out both;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.4s; }
        .form-group:nth-child(2) { animation-delay: 0.5s; }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: transparent;
        }
        
        .form-group label {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 15px;
            pointer-events: none;
            transition: all 0.3s ease;
            background: white;
            padding: 0 5px;
        }
        
        .form-group input:focus,
        .form-group input:not(:placeholder-shown) {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input:focus + label,
        .form-group input:not(:placeholder-shown) + label {
            top: 0;
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
        }
        
        /* Error Message */
        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: shake 0.5s ease-in-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Button */
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            animation: fadeInUp 0.8s ease-out 0.6s both;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5);
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: rippleEffect 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes rippleEffect {
            to { transform: scale(4); opacity: 0; }
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            animation: fadeInUp 0.8s ease-out 0.7s both;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover { text-decoration: underline; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; max-width: 450px; }
            .animation-side { padding: 30px 20px; }
            .cartoon-container { width: 40%; max-width: 150px; }
            .form-side { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side: Animated SVG Cartoon -->
        <div class="animation-side">
            <div class="cartoon-container">
                <!-- SVG Character Code -->
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <!-- Body -->
                    <rect x="30" y="60" width="40" height="40" rx="10" fill="#ffffff" opacity="0.2" />
                    <rect x="35" y="65" width="30" height="35" rx="8" fill="#ffffff" />
                    
                    <!-- Head -->
                    <circle cx="50" cy="40" r="18" fill="#ffffff" />
                    <!-- Eyes -->
                    <circle cx="43" cy="38" r="2" fill="#333" />
                    <circle cx="57" cy="38" r="2" fill="#333" />
                    <!-- Smile -->
                    <path d="M 45 44 Q 50 48 55 44" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" />
                    
                    <!-- Animated Graduation Cap -->
                    <g id="grad-cap">
                        <!-- Tassel String -->
                        <rect x="55" y="15" width="2" height="15" fill="#f093fb" rx="1" />
                        <!-- Tassel Ball -->
                        <circle cx="56" cy="30" r="3" fill="#f093fb" />
                        <!-- Cap Base -->
                        <rect x="35" y="35" width="30" height="5" rx="2" fill="#ffffff" />
                        <!-- Cap Top -->
                        <polygon points="50,10 25,25 50,40 75,25" fill="#ffffff" />
                        <!-- Cap Button -->
                        <circle cx="50" cy="25" r="2" fill="#f093fb" />
                    </g>
                </svg>
            </div>
            <h2>Welcome to KSTU</h2>
            <p>Streamlining your graduation journey</p>
        </div>
        
        <!-- Right Side: Form -->
        <div class="form-side">
            <div class="form-header">
                <h1>Student Login</h1>
                <p>Access your clearance dashboard</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <input type="email" id="email" name="email" required placeholder=" ">
                    <label for="email">Email Address</label>
                </div>
                
                <div class="form-group">
                    <input type="password" id="password" name="password" required placeholder=" ">
                    <label for="password">Password</label>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">Login</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
    
    <script>
        // Ripple Effect
        function createRipple(event) {
            const button = event.currentTarget;
            const circle = document.createElement('span');
            const diameter = Math.max(button.clientWidth, button.clientHeight);
            const radius = diameter / 2;
            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
            circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
            circle.classList.add('ripple');
            const ripple = button.getElementsByClassName('ripple')[0];
            if (ripple) ripple.remove();
            button.appendChild(circle);
        }
        document.getElementById('loginBtn').addEventListener('click', createRipple);
    </script>
</body>
</html>