<?php
session_start();
require_once "../db_utils.php";
$db = new DB_UTILS();

// CHẶN BẢO MẬT: Nếu không có quyền Admin, lập tức từ chối quyền truy cập
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Lấy nhanh một vài số liệu thống kê để làm giao diện Admin trông chuyên nghiệp hơn
$count_orders = $db->getOne("SELECT COUNT(*) as total FROM orders")['total'] ?? 0;
$count_products = $db->getOne("SELECT COUNT(*) as total FROM products") ?? 0; // Tùy thuộc cấu trúc hàm db_utils của bạn
$count_users = $db->getOne("SELECT COUNT(*) as total FROM users")['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>TechShop - Hệ Thống Quản Trị</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR ĐIỀU HƯỚNG */
        .sidebar {
            width: 260px;
            background: #1e293b;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
        }

        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 1px solid #334155;
            padding-bottom: 15px;
            color: #38bdf8;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-weight: bold;
            transition: all 0.2s;
        }

        .sidebar a:hover {
            background: #334155;
            color: white;
        }

        .sidebar a.active {
            background: #0284c7;
            color: white;
        }

        /* KHỐI NỘI DUNG CHÍNH */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 40px;
        }

        .header-panel {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 35px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-title h1 {
            font-size: 24px;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .welcome-title p {
            color: #64748b;
            font-size: 14px;
        }

        /* KHU VỰC CÁC NÚT BẤM LỚN (GRID) */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .menu-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        /* Màu sắc chủ đạo từng thẻ */
        .card-orders {
            border-top: 4px solid #0284c7;
        }

        .card-products {
            border-top: 4px solid #10b981;
        }

        .card-users {
            border-top: 4px solid #f59e0b;
        }

        .card-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .card-name {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .card-desc {
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .btn-action {
            display: inline-block;
            align-self: flex-start;
            padding: 8px 16px;
            background: #f1f5f9;
            color: #334155;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
        }

        .menu-card:hover .btn-action {
            background: #1e293b;
            color: white;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>🛠️ TECHSHOP ADMIN</h2>
        <a href="index.php" class="active">🏠 Bảng Điều Khiển</a>
        <a href="orders.php">📦 Quản lý đơn hàng</a>
        <a href="products.php">🏷️ Quản lý sản phẩm</a>
        <a href="users.php">👥 Quản lý người dùng</a>
        <a href="../index.php" style="margin-top: 80px; background: #b91c1c; text-align: center; color: white;">Trang
            chủ User</a>
    </div>

    <div class="main-content">
        <div class="header-panel">
            <div class="welcome-title">
                <h1>Chào mừng trở lại, Admin!</h1>
                <p>Hôm nay là <?= date('d/m/Y') ?>. Chọn một phân hệ bên dưới để bắt đầu làm việc.</p>
            </div>
            <div>Tài khoản: <strong><?= htmlspecialchars($_SESSION['user']['full_name']) ?></strong></div>
        </div>

        <div class="dashboard-grid">

            <a href="orders.php" class="menu-card card-orders">
                <div>
                    <div class="card-icon">📦</div>
                    <div class="card-name">Quản Lý Đơn Hàng</div>
                    <div class="card-desc">Xem danh sách khách mua hàng, xử lý duyệt đơn, kiểm tra trạng thái giao nhận
                        và tổng tiền.</div>
                </div>
                <div class="btn-action">Truy cập ngay →</div>
            </a>

            <a href="products.php" class="menu-card card-products">
                <div>
                    <div class="card-icon">🏷️</div>
                    <div class="card-name">Quản Lý Sản Phẩm</div>
                    <div class="card-desc">Thêm thiết bị mới, điều chỉnh thông tin tên gọi, cập nhật giá bán hoặc cập
                        nhật đường dẫn hình ảnh.</div>
                </div>
                <div class="btn-action">Truy cập ngay →</div>
            </a>

            <a href="users.php" class="menu-card card-users">
                <div>
                    <div class="card-icon">👥</div>
                    <div class="card-name">Quản Lý Người Dùng</div>
                    <div class="card-desc">Theo dõi danh sách các tài khoản thành viên đăng ký trong hệ thống, cấp quyền
                        hoặc hạ quyền Quản trị.</div>
                </div>
                <div class="btn-action">Truy cập ngay →</div>
            </a>

        </div>
    </div>
</body>

</html>