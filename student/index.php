<?php
session_start();
include '../includes/db_connect.php';

// 1. Security Check: Must be logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];

// 2. Fetch Clearance Data
// We join 'clearance_items' with 'clearance_status' to show all departments
// even if a student hasn't been reviewed yet (defaults to Pending)
try {
    $stmt = $conn->prepare("
        SELECT 
            ci.id, 
            ci.item_name, 
            ci.department, 
            ci.display_order, 
            COALESCE(cs.status, 'pending') as status, 
            cs.remark 
        FROM clearance_items ci
        LEFT JOIN clearance_status cs ON ci.id = cs.item_id AND cs.user_id = ?
        ORDER BY ci.display_order
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    // Calculate Progress
    $totalItems = count($items);
    $approvedCount = 0;
    foreach ($items as $item) {
        if ($item['status'] == 'approved') $approvedCount++;
    }
    $progress = ($totalItems > 0) ? round(($approvedCount / $totalItems) * 100) : 0;
    $isCleared = ($progress == 100);

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - KSTU Clearance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            color: #333;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 22px;
            font-weight: 700;
            color: #667eea;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name {
            font-weight: 600;
        }
        
        .btn-logout {
            padding: 8px 20px;
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-logout:hover { transform: scale(1.05); }
        
        /* Main Content */
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-text h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .welcome-text p {
            color: #666;
        }
        
        /* SVG Cap Animation */
        .cap-anim {
            width: 120px;
            height: 120px;
            animation: floatBody 3s ease-in-out infinite;
        }
        
        @keyframes floatBody {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        #cap-head {
            animation: liftHat 4s ease-in-out infinite;
            transform-origin: 50px 35px;
        }
        
        @keyframes liftHat {
            0%, 20% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(-15deg); }
            80%, 100% { transform: translateY(0) rotate(0deg); }
        }
        
        /* Progress Bar */
        .progress-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .progress-bar-bg {
            height: 25px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4ade80, #22c55e);
            width: <?php echo $progress; ?>%;
            border-radius: 15px;
            transition: width 1.5s ease-out;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 700;
        }
        
        .certificate-alert {
            margin-top: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            display: <?php echo $isCleared ? 'block' : 'none'; ?>; /* Shows only if 100% */
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        /* Clearance Items Grid */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .item-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeIn 0.6s ease-out both;
        }
        
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .item-name {
            font-weight: 700;
            font-size: 18px;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        
        .dept-name {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .remark {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            color: #555;
            margin-top: 10px;
            border-left: 4px solid #667eea;
        }
        
        /* Staggered Animation Delays */
        .item-card:nth-child(1) { animation-delay: 0.3s; }
        .item-card:nth-child(2) { animation-delay: 0.4s; }
        .item-card:nth-child(3) { animation-delay: 0.5s; }
        .item-card:nth-child(4) { animation-delay: 0.6s; }
        .item-card:nth-child(5) { animation-delay: 0.7s; }
        .item-card:nth-child(6) { animation-delay: 0.8s; }
        .item-card:nth-child(7) { animation-delay: 0.9s; }
        
        @media (max-width: 768px) {
            .welcome-card { flex-direction: column-reverse; text-align: center; }
            .header { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="logo">KSTU Clearance</div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
            <a href="../logout.php"><button class="btn-logout">Logout</button></a>
        </div>
    </header>

    <div class="container">
        
        <!-- Welcome Card with Animation -->
        <div class="welcome-card">
            <div class="welcome-text">
                <h1>Welcome Back! 👋</h1>
                <p>Track your graduation clearance progress below. Good luck!</p>
            </div>
            <div class="cap-anim">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <rect x="30" y="60" width="40" height="40" rx="10" fill="#ffffff" opacity="0.2" />
                    <rect x="35" y="65" width="30" height="35" rx="8" fill="#ffffff" />
                    <circle cx="50" cy="40" r="18" fill="#ffffff" />
                    <circle cx="43" cy="38" r="2" fill="#333" />
                    <circle cx="57" cy="38" r="2" fill="#333" />
                    <path d="M 45 44 Q 50 48 55 44" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" />
                    <g id="cap-head">
                        <rect x="55" y="15" width="2" height="15" fill="#f093fb" rx="1" />
                        <circle cx="56" cy="30" r="3" fill="#f093fb" />
                        <rect x="35" y="35" width="30" height="5" rx="2" fill="#ffffff" />
                        <polygon points="50,10 25,25 50,40 75,25" fill="#ffffff" />
                        <circle cx="50" cy="25" r="2" fill="#f093fb" />
                    </g>
                </svg>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-section">
            <div class="progress-header">
                <span>Overall Progress</span>
                <span><?php echo $progress; ?>% Cleared</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill"></div>
            </div>
            
            <?php if ($isCleared): ?>
                <div class="certificate-alert">
                    🎉 Congratulations! You are fully cleared. 
                </div>
            <?php endif; ?>
        </div>

        <!-- Clearance Items -->
        <h2 style="margin-bottom: 20px; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">Your Clearance Checklist</h2>
        
        <div class="items-grid">
            <?php foreach ($items as $item): 
                $statusClass = 'status-' . $item['status'];
            ?>
                <div class="item-card">
                    <div class="item-header">
                        <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($item['status']); ?>
                        </span>
                    </div>
                    <div class="dept-name">Department: <?php echo htmlspecialchars($item['department']); ?></div>
                    
                    <?php if ($item['remark']): ?>
                        <div class="remark">
                            <strong>Remark:</strong> <?php echo htmlspecialchars($item['remark']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

</body>
</html>