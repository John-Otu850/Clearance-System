<?php
session_start();
include 'includes/db_connect.php';

$error = '';
$success = '';

// Handle registration form submission
// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $student_id = trim($_POST['student_id']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Set student_id to NULL for staff/admin (fixes duplicate entry error)
    if (empty($student_id) || $role != 'student') {
        $student_id = NULL;
    }
    
    // Validation
    if (empty($full_name) || empty($email) || empty($role) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        try {
            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->fetch()) {
                $error = "Email already registered!";
            } else {
                // Hash password & prepare insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, student_id, role, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $student_id, $role, $hashed_password]);
                
                $success = "Registration successful! You can now login.";
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - KSTU Clearance System</title>
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
        
        .register-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 950px;
            display: flex;
            overflow: hidden;
            animation: slideIn 0.8s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
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
        
        /* FIXED: Added Keyframes for Animation */
        @keyframes floatBody {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        @keyframes liftHat {
            0%, 20% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-25px) rotate(-20deg); }
            80%, 100% { transform: translateY(0) rotate(0deg); }
        }
        /* END FIX */
        
        .form-side {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-header {
            margin-bottom: 25px;
            animation: fadeInDown 0.8s ease-out 0.2s both;
        }
        
        .form-header h1 {
            color: #333;
            font-size: 26px;
            margin-bottom: 8px;
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
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            animation: fadeInUp 0.8s ease-out both;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.35s; }
        .form-group:nth-child(3) { animation-delay: 0.4s; }
        .form-group:nth-child(4) { animation-delay: 0.45s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.55s; }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: transparent;
        }
        
        .form-group select {
            cursor: pointer;
        }
        
        .form-group label {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 15px;
            pointer-events: none;
            transition: all 0.3s ease;
            background: white;
            padding: 0 5px;
        }
        
        .form-group select + label,
        .form-group select:not([value=""]):not(:focus) + label {
            top: 0;
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
        }
        
        .form-group input:focus,
        .form-group input:not(:placeholder-shown),
        .form-group select:focus {
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
        
        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            animation: shake 0.5s ease-in-out;
        }
        
        .success-message {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
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
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            animation: fadeInUp 0.8s ease-out 0.7s both;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover { text-decoration: underline; }
        
        @media (max-width: 768px) {
            .register-wrapper { flex-direction: column; max-width: 450px; }
            .animation-side { padding: 30px 20px; }
            .form-side { padding: 30px 20px; max-height: none; }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="animation-side">
            <!-- Same SVG Cartoon as Login -->
            <!-- Applied floatBody to the container SVG and liftHat to the group ID -->
            <svg viewBox="0 0 100 100" width="160" height="160" style="margin-bottom: 20px; animation: floatBody 3s ease-in-out infinite;">
                <rect x="30" y="60" width="40" height="40" rx="10" fill="#ffffff" opacity="0.2" />
                <rect x="35" y="65" width="30" height="35" rx="8" fill="#ffffff" />
                <circle cx="50" cy="40" r="18" fill="#ffffff" />
                <circle cx="43" cy="38" r="2" fill="#333" />
                <circle cx="57" cy="38" r="2" fill="#333" />
                <path d="M 45 44 Q 50 48 55 44" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" />
                <!-- ID matches the animation keyframe -->
                <g id="grad-cap" style="animation: liftHat 4s ease-in-out infinite; transform-origin: 50px 35px;">
                    <rect x="55" y="15" width="2" height="15" fill="#f093fb" rx="1" />
                    <circle cx="56" cy="30" r="3" fill="#f093fb" />
                    <rect x="35" y="35" width="30" height="5" rx="2" fill="#ffffff" />
                    <polygon points="50,10 25,25 50,40 75,25" fill="#ffffff" />
                    <circle cx="50" cy="25" r="2" fill="#f093fb" />
                </g>
            </svg>
            <h2>Join KSTU</h2>
            <p>Start your clearance journey today</p>
        </div>
        
        <div class="form-side">
            <div class="form-header">
                <h1>Create Account</h1>
                <p>Fill in your details to register</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">️ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" id="full_name" name="full_name" required placeholder=" ">
                    <label for="full_name">Full Name</label>
                </div>
                
                <div class="form-group">
                    <input type="email" id="email" name="email" required placeholder=" ">
                    <label for="email">Email Address</label>
                </div>
                
                <div class="form-group">
                    <input type="text" id="student_id" name="student_id" placeholder=" ">
                    <label for="student_id">Student ID (Optional for Staff/Admin)</label>
                </div>
                
                <div class="form-group">
                    <select id="role" name="role" required>
                        <option value="" disabled selected> </option>
                        <option value="student">Student</option>
                        <option value="staff">Staff / Unit Head</option>
                        <option value="admin">Administrator</option>
                    </select>
                    <label for="role">Account Type</label>
                </div>
                
                <div class="form-group">
                    <input type="password" id="password" name="password" required placeholder=" ">
                    <label for="password">Password</label>
                </div>
                
                <div class="form-group">
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder=" ">
                    <label for="confirm_password">Confirm Password</label>
                </div>
                
                <button type="submit" class="btn-register" id="registerBtn">Register</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
    
    <script>
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
        document.getElementById('registerBtn').addEventListener('click', createRipple);
    </script>
</body>
</html>