<?php
session_start();
include '../includes/db_connect.php';

// 1. Security Check: Must be logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../login.php");
    exit();
}

$staffName = $_SESSION['full_name'];
$staffDept = $_SESSION['department'] ?? '';

// 2. Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $userId = intval($_POST['user_id']);
    $itemId = intval($_POST['item_id']);
    $action = $_POST['action'];
    $remark = trim($_POST['remark'] ?? '');
    
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    
    try {
        // Upsert: Insert if not exists, Update if exists
        $stmt = $conn->prepare("
            INSERT INTO clearance_status (user_id, item_id, status, remark, reviewed_by, reviewed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                remark = VALUES(remark), 
                reviewed_by = VALUES(reviewed_by), 
                reviewed_at = NOW()
        ");
        $stmt->execute([$userId, $itemId, $status, $remark, $_SESSION['user_id']]);
        $success_msg = "Status updated successfully!";
    } catch(PDOException $e) {
        $error_msg = "Error updating status: " . $e->getMessage();
    }
}

// 3. Fetch Clearance Items for this Department
if (empty($staffDept)) {
    $error_msg = "Your profile is missing a Department. Please contact the Admin to update your profile.";
    $items = [];
} else {
    try {
        $stmt = $conn->prepare("
            SELECT 
                u.id as user_id,
                u.full_name,
                u.student_id,
                ci.id as item_id,
                ci.item_name,
                cs.status,
                cs.remark
            FROM clearance_items ci
            CROSS JOIN users u
            LEFT JOIN clearance_status cs ON u.id = cs.user_id AND ci.id = cs.item_id
            WHERE ci.department = ? AND u.role = 'student'
            ORDER BY u.full_name, ci.display_order
        ");
        $stmt->execute([$staffDept]);
        $items = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error_msg = "Error fetching data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - KSTU Clearance</title>
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
        
        .cap-anim { width: 100px; height: 100px; animation: floatBody 3s ease-in-out infinite; }
        @keyframes floatBody { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        #cap-head { animation: liftHat 4s ease-in-out infinite; transform-origin: 50px 35px; }
        @keyframes liftHat {
            0%, 20% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(-15deg); }
            80%, 100% { transform: translateY(0) rotate(0deg); }
        }
        
        .alert { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 20px; font-weight: 700; color: #333; }
        .badge-dept { background: #e0e7ff; color: #667eea; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        thead { background: #f8f9fa; }
        th { padding: 12px; text-align: left; font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #fafbfc; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .remark-text { font-size: 12px; color: #666; margin-top: 4px; font-style: italic; }
        
        .action-form { display: flex; gap: 8px; align-items: center; }
        .btn-approve {
            padding: 6px 14px;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-approve:hover { background: #16a34a; }
        
        .btn-reject {
            padding: 6px 14px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-reject:hover { background: #dc2626; }
        
        .reject-form { display: none; margin-top: 8px; }
        .reject-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
            resize: vertical;
            min-height: 40px;
        }
        .reject-form .btn-group { display: flex; gap: 8px; margin-top: 6px; }
        
        @media (max-width: 768px) {
            .welcome-section { flex-direction: column-reverse; text-align: center; }
            .header { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="logo">KSTU Clearance - Staff</div>
        <div>
            <span style="font-weight: 600; margin-right: 15px;"><?php echo htmlspecialchars($staffName); ?></span>
            <a href="../logout.php"><button class="btn-logout">Logout</button></a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <div class="welcome-text" style="flex: 1;">
                <h1>Staff Dashboard </h1>
                <p>Review and process student clearance requests for your department.</p>
            </div>
            <div class="cap-anim">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
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

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Clearance Requests</h2>
                <span class="badge-dept">Department: <?php echo htmlspecialchars($staffDept ?: 'Not Assigned'); ?></span>
            </div>

            <?php if (empty($staffDept)): ?>
                <p style="text-align:center; color:#888; padding: 40px;">Your profile needs a Department assigned. Please contact the Administrator.</p>
            <?php elseif (empty($items)): ?>
                <p style="text-align:center; color:#888; padding: 40px;">No students found for your department.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Item</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $row): 
                            $statusClass = 'status-' . ($row['status'] ?: 'pending');
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                    <small style="color:#888;"><?php echo htmlspecialchars($row['student_id'] ?: 'N/A'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($row['status'] ?: 'Pending'); ?>
                                    </span>
                                    <?php if ($row['remark']): ?>
                                        <div class="remark-text">"<?php echo htmlspecialchars($row['remark']); ?>"</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] != 'approved'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                            <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn-approve">✓ Approve</button>
                                        </form>
                                        
                                        <button type="button" class="btn-reject" onclick="toggleReject(this)">✗ Reject</button>
                                        <div class="reject-form">
                                            <form method="POST">
                                                <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <textarea name="remark" placeholder="Reason for rejection (required)" required></textarea>
                                                <div class="btn-group">
                                                    <button type="submit" class="btn-reject">Confirm Reject</button>
                                                    <button type="button" class="btn-approve" style="background:#666;" onclick="toggleReject(this)">Cancel</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#22c55e; font-weight:600; font-size:13px;">✓ Approved</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleReject(btn) {
            const row = btn.closest('td');
            const form = row.querySelector('.reject-form');
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>