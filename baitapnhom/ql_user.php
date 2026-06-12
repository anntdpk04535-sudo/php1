<?php
session_start();
// Chặn nếu chưa đăng nhập hoặc không phải là quản trị viên (admin)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Quyền truy cập bị từ chối! Trang này chỉ dành cho Admin.</h2><p style='text-align:center;'><a href='cart.php'>Quay lại trang chủ mua hàng</a></p>");
    exit;
}

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lab4;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

$success = '';
$error = '';

// XỬ LÝ FORM POST (THÊM, SỬA QUYỀN, XÓA USER)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CHỨC NĂNG: ADMIN THÊM TÀI KHOẢN MỚI TRỰC TIẾP
    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $fullname = trim($_POST['full_name'] ?? '');
        $roleSelect = $_POST['role'] ?? 'user';

        if (empty($username) || empty($password) || empty($fullname)) {
            $error = "Vui lòng điền đầy đủ thông tin tài khoản cần thêm!";
        } else {
            // Kiểm tra xem tài khoản (username) này đã tồn tại trong hệ thống chưa
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmtCheck->execute([$username]);
            
            if ($stmtCheck->fetchColumn() > 0) {
                $error = "Tên tài khoản [ $username ] đã tồn tại trên hệ thống, vui lòng chọn tên khác!";
            } else {
                // Mã hóa mật khẩu an toàn bằng BCRYPT giống chuẩn hệ thống đăng nhập
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Tiến hành nạp dữ liệu tài khoản mới vào DB
                $stmtInsert = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$username, $hashedPassword, $fullname, $roleSelect]);
                $success = "🎉 Đã thêm thành công tài khoản mới: <b>$username</b>!";
            }
        }
    }

    // 2. CHỨC NĂNG: THAY ĐỔI QUYỀN HẠN USER
    elseif ($action === 'change_role') {
        $targetUserId = $_POST['target_user_id'] ?? '';
        $newRole = $_POST['role'] ?? 'user';

        if (!empty($targetUserId)) {
            if ((int)$targetUserId === (int)$_SESSION['user']['id']) {
                $error = "Bạn không thể tự hạ quyền hạn của chính mình!";
            } else {
                if (in_array($newRole, ['user', 'admin'])) {
                    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->execute([$newRole, $targetUserId]);
                    $success = "Cập nhật quyền hạn thành công!";
                }
            }
        }
    }

    // 3. CHỨC NĂNG: XÓA THÀNH VIÊN
    elseif ($action === 'delete_user') {
        $targetUserId = $_POST['target_user_id'] ?? '';
        if (!empty($targetUserId)) {
            if ((int)$targetUserId === (int)$_SESSION['user']['id']) {
                $error = "Bạn không thể tự xóa tài khoản của chính mình!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$targetUserId]);
                $success = "Đã xóa tài khoản thành viên thành công!";
            }
        }
    }
}

// XỬ LÝ TÌM KIẾM THÀNH VIÊN
$search = trim($_GET['search'] ?? '');
$searchQuery = "";
$params = [];
if ($search !== '') {
    $searchQuery = " WHERE username LIKE ? OR full_name LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Lấy danh sách toàn bộ tài khoản thành viên
$stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users" . $searchQuery . " ORDER BY id DESC");
$stmt->execute($params);
$usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Thành Viên - Admin</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; }
        body { background: #f4f6f9; padding: 30px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        h1, h2 { margin-bottom: 20px; color: #2c3e50; font-weight: 700; }
        h1 { text-align: center; }
        .btn-back { display: inline-block; padding: 10px 20px; background: #34495e; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-bottom: 20px; font-size: 14px; }
        .btn-back:hover { background: #2c3e50; }
        
        /* CẤU TRÚC FORM THÊM MỚI */
        .add-user-box { background: #f8f9fa; border: 1px solid #e2e8f0; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 14px; font-weight: 600; color: #4a5568; }
        .form-group input, .form-group select { padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 14px; outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: #3182ce; }
        .btn-submit-add { background: #10b981; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; transition: background 0.2s; }
        .btn-submit-add:hover { background: #059669; }

        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e1e8ed; font-size: 15px; }
        th { background: #f1f4f6; color: #475569; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #fff; text-transform: uppercase; }
        .badge-admin { background: #e74c3c; }
        .badge-user { background: #3498db; }

        .search-container { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-input { flex: 1; padding: 10px 15px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; }
        .btn-search { padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-clear { padding: 10px 15px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; text-decoration: none; font-size: 14px; display: flex; align-items: center; }

        .btn-delete { background: #dc2626; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px; }
        .btn-delete:hover { background: #b91c1c; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center; font-weight: bold; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
<div class="container">
    <a href="lab4.php" class="btn-back">🛠️ Quản lý sản phẩm</a>
    <a href="DonHang.php" class="btn-back" style="background:#2ecc71;">📋 Quản lý đơn hàng</a>

    <h1>👥 Quản Lý Danh Sách Thành Viên</h1>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="add-user-box">
        <h2 style="font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px; color: #2d3748;">➕ Thêm tài khoản thành viên mới</h2>
        <form method="POST" action="ql_user.php">
            <input type="hidden" name="action" value="add_user">
            <div class="form-grid">
                <div class="form-group">
                    <label>Tên tài khoản (Username):</label>
                    <input type="text" name="username" placeholder="Ví dụ: nguyenvana" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu gốc:</label>
                    <input type="password" name="password" placeholder="Nhập mật khẩu..." required>
                </div>
                <div class="form-group">
                    <label>Họ và Tên đầy đủ:</label>
                    <input type="text" name="full_name" placeholder="Ví dụ: Nguyễn Văn A" required>
                </div>
                <div class="form-group">
                    <label>Phân quyền gốc:</label>
                    <select name="role">
                        <option value="user" selected>User thường</option>
                        <option value="admin">Quản trị viên (Admin)</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit-add">💾 Thêm tài khoản</button>
            </div>
        </form>
    </div>

    <form class="search-container" method="GET" action="ql_user.php">
        <input type="text" name="search" class="search-input" placeholder="Tìm thành viên theo Tài khoản hoặc Họ tên..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-search">🔍 Tìm kiếm</button>
        <?php if ($search !== ''): ?>
            <a href="ql_user.php" class="btn-clear">Xóa lọc</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tài khoản</th>
                <th>Họ và Tên</th>
                <th>Vai trò</th>
                <th>Thay đổi quyền</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($usersList) > 0): ?>
                <?php foreach ($usersList as $u): ?>
                    <tr>
                        <td>#<?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge badge-admin">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-user">User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                <select name="role" onchange="this.form.submit()" <?= (int)$u['id'] === (int)$_SESSION['user']['id'] ? 'disabled' : '' ?>>
                                    <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User thường</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <?php if ((int)$u['id'] !== (int)$_SESSION['user']['id']): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn tài khoản [<?= htmlspecialchars($u['username']) ?>] không?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-delete">🗑️ Xóa</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#7f8c8d; font-size:13px; font-style:italic;">Đang dùng</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center; color: #7f8c8d; padding: 20px;">Không tìm thấy thành viên nào khớp từ khóa.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>