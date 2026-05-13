<?php
session_start();
include '../includes/db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$adminName = $_SESSION['full_name'];

try {
    // 1. Total Students
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $stmt->execute();
    $totalStudents = $stmt->fetch()['total'];
    
    // 2. Total Staff
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'staff'");
    $stmt->execute();
    $totalStaff = $stmt->fetch()['total'];
    
    // 3. Fully Cleared
    $stmt = $conn->prepare("
        SELECT COUNT(*) as cleared_count FROM (
            SELECT cs.user_id FROM clearance_status cs WHERE cs.status = 'approved' 
            GROUP BY cs.user_id 
            HAVING COUNT(*) = (SELECT COUNT(*) FROM clearance_items)
        ) as cleared_students
    ");
    $stmt->execute();
    $fullyCleared = $stmt->fetch()['cleared_count'];
    
    // 4. Pending Reviews
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM clearance_status WHERE status = 'pending'");
    $stmt->execute();
    $pendingCount = $stmt->fetch()['pending'];
    
    // 5. Fetch Students
    $stmt = $conn->prepare("SELECT id, full_name, email, student_id FROM users WHERE role = 'student' ORDER BY created_at DESC");
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    // Calculate progress
    foreach ($students as &$student) {
        $stmtItems = $conn->prepare("SELECT COUNT(*) as total FROM clearance_items");
        $stmtItems->execute();
        $totalItems = $stmtItems->fetch()['total'];
        
        $stmtApproved = $conn->prepare("SELECT COUNT(*) as approved FROM clearance_status WHERE user_id = ? AND status = 'approved'");
        $stmtApproved->execute([$student['id']]);
        $approved = $stmtApproved->fetch()['approved'];
        
        if ($totalItems > 0) {
            $student['progress'] = round(($approved / $totalItems) * 100);
        } else {
            $student['progress'] = 0;
        }
    }
    
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
    <title>Admin Dashboard - KSTU Clearance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            padding-bottom: 50px;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo { font-size: 22px; font-weight: 700; color: #667eea; }
        .btn-logout { padding: 8px 20px; background: #ff6b6b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-logout:hover { background: #e55a5a; }
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .welcome-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .welcome-text h1 { font-size: 28px; color: #333; margin-bottom: 5px; }
        .welcome-text p { color: #666; }
        
        /* Animation */
        .cap-anim { width: 120px; height: 120px; animation: floatBody 3s ease-in-out infinite; }
        @keyframes floatBody { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        #cap-head { animation: liftHat 4s ease-in-out infinite; transform-origin: 50px 35px; }
        @keyframes liftHat {
            0%, 20% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(-15deg); }
            80%, 100% { transform: translateY(0) rotate(0deg); }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { font-size: 30px; margin-bottom: 10px; }
        .stat-number { font-size: 32px; font-weight: 700; color: #667eea; }
        .stat-label { color: #888; font-size: 14px; }
        
        .table-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .section-title { font-size: 20px; margin-bottom: 20px; color: #333; font-weight: 700; }
        
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8f9fa; }
        th { padding: 12px; text-align: left; font-size: 12px; color: #666; text-transform: uppercase; }
        td { padding: 15px 12px; border-bottom: 1px solid #eee; }
        
        .progress-container { width: 120px; }
        .progress-bar-bg { height: 10px; background: #eee; border-radius: 5px; overflow: hidden; margin-bottom: 5px; }
        .progress-bar-fill { height: 100%; border-radius: 5px; transition: width 0.5s; }
        .progress-text { font-size: 12px; font-weight: 700; color: #333; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-cleared { background: #dcfce7; color: #15803d; }
        .badge-progress { background: #fef3c7; color: #d97706; }
        
        .btn-view { padding: 6px 12px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .btn-view:hover { background: #5568d3; }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">KSTU Clearance - Admin</div>
        <div>
            <span style="font-weight: 600; margin-right: 15px;"><?php echo htmlspecialchars($adminName); ?></span>
            <a href="../logout.php"><button class="btn-logout">Logout</button></a>
        </div>
    </header>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-text" style="flex: 1;">
                <h1>Admin Dashboard </h1>
                <p>Manage student clearances and monitor system activity</p>
            </div>
            <div class="cap-anim">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <!-- Purple/Blue Colors for Visibility -->
                    <rect x="30" y="60" width="40" height="40" rx="10" fill="#e0e7ff" opacity="0.5" />
                    <rect x="35" y="65" width="30" height="35" rx="8" fill="#667eea" />
                    <circle cx="50" cy="40" r="18" fill="#764ba2" />
                    <circle cx="43" cy="38" r="2" fill="white" />
                    <circle cx="57" cy="38" r="2" fill="white" />
                    <path d="M 45 44 Q 50 48 55 44" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" />
                    <g id="cap-head">
                        <rect x="55" y="15" width="2" height="15" fill="#f093fb" rx="1" />
                        <circle cx="56" cy="30" r="3" fill="#f093fb" />
                        <rect x="35" y="35" width="30" height="5" rx="2" fill="#667eea" />
                        <polygon points="50,10 25,25 50,40 75,25" fill="#764ba2" />
                        <circle cx="50" cy="25" r="2" fill="#f093fb" />
                    </g>
                </svg>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">👨‍</div><div class="stat-number"><?php echo $totalStudents; ?></div><div class="stat-label">Total Students</div></div>
            <div class="stat-card"><div class="stat-icon">👨‍💼</div><div class="stat-number"><?php echo $totalStaff; ?></div><div class="stat-label">Staff Members</div></div>
            <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-number"><?php echo $fullyCleared; ?></div><div class="stat-label">Fully Cleared</div></div>
            <div class="stat-card"><div class="stat-icon"></div><div class="stat-number"><?php echo $pendingCount; ?></div><div class="stat-label">Pending Items</div></div>
        </div>

        <!-- Table -->
        <div class="table-section">
            <h2 class="section-title">Student Clearance Progress</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            // Logic: If progress is greater than 0, color is Green (#22c55e), otherwise Gray (#e0e0e0)
                            $progress = isset($student['progress']) ? $student['progress'] : 0;
                            $bar_color = ($progress > 0) ? '#22c55e' : '#e0e0e0'; 
                            $isCleared = ($progress == 100);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td class="progress-container">
                                    <div class="progress-bar-bg">
                                        <!-- Applied Dynamic Color Here -->
                                        <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%; background: <?php echo $bar_color; ?>;"></div>
                                    </div>
                                    <div class="progress-text"><?php echo $progress; ?>%</div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $isCleared ? 'badge-cleared' : 'badge-progress'; ?>">
                                        <?php echo $isCleared ? 'Cleared' : 'In Progress'; ?>
                                    </span>
                                </td>
                                <td><button class="btn-view">View Details</button></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="6" style="text-align:center; padding: 30px; color:#888;">No students registered yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>