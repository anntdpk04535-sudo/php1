<?php
session_start();
require_once "./db_utils.php";
$db = new DB_UTILS();

// Lấy danh sách sản phẩm từ CSDL đổ ra trang chủ
$products = $db->getAll("SELECT * FROM products ORDER BY product_id DESC");

// Tính số lượng trong giỏ hàng để hiển thị ban đầu khi tải trang
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>TechShop - Trang Chủ</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #334155;
        }

        header {
            background: #fff;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            text-decoration: none;
            color: #475569;
            font-weight: 600;
            font-size: 15px;
        }

        .nav-links a:hover {
            color: #2563eb;
        }

        .btn-cart-nav {
            background: #eff6ff;
            color: #2563eb;
            padding: 8px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: bold;
        }

        /* Chú ý: Đã có ID cartBadge để JS tìm kiếm */
        .badge {
            background: #dc2626;
            color: white;
            padding: 2px 7px;
            border-radius: 50%;
            font-size: 12px;
        }

        .hero-banner {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 25px;
            border-left: 5px solid #2563eb;
            padding-left: 10px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .p-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .p-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .p-img-box {
            width: 100%;
            height: 200px;
            background: #f1f5f9;
        }

        .p-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .p-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .p-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1e293b;
            min-height: 44px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .p-price {
            color: #dc2626;
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 15px;
        }

        /* KHỐI NÚT HÀNH ĐỘNG MỚI */
        .btn-group-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: auto;
        }

        .btn-row-top {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            display: block;
            text-align: center;
            padding: 10px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 13px;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-detail {
            flex: 1;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .btn-detail:hover {
            background: #e2e8f0;
        }

        .btn-add-cart {
            flex: 1;
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .btn-add-cart:hover {
            background: #dcfce7;
        }

        .btn-buy-now {
            background: #2563eb;
            color: #fff;
            width: 100%;
        }

        .btn-buy-now:hover {
            background: #1d4ed8;
        }

        /* GIAO DIỆN HỘP THÔNG BÁO TOAST */
        .toast-notification {
            position: fixed;
            top: 90px;
            right: -350px;
            background: #10b981;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            transition: right 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 15px;
        }

        .toast-notification.show {
            right: 30px;
        }

        .toast-error {
            background: #ef4444 !important;
        }
    </style>
</head>

<body>
    <div id="cartToast" class="toast-notification">
        <span id="toastIcon">🎉</span> <span id="toastMessage">Thêm sản phẩm vào giỏ hàng thành công!</span>
    </div>

    <header>
        <a href="index.php" class="brand">🛒 Shop</a>
        <div class="nav-links">
            <a href="index.php">Trang chủ</a>
            <a href="DonHang.php">📦 Đơn hàng của tôi</a>
            <a href="send_report.php" style="color: #f59e0b; font-weight: bold;">
                <i class="fas fa-exclamation-circle"></i> Khiếu nại</a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="cart.php" class="btn-cart-nav">Giỏ hàng <span id="cartBadge"
                        class="badge"><?= $cart_count ?></span></a>
                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                    <a href="admin/orders.php" style="color: #10b981; font-weight: bold;">🛠️ Trang Admin</a>
                <?php endif; ?>
                <a href="logout.php" style="color:#dc2626;">Đăng xuất
                    (<?= htmlspecialchars($_SESSION['user']['full_name']) ?>)</a>
            <?php else: ?>
                <a href="login.php" style="color: #2563eb;">Đăng nhập</a>
                <a href="register.php">Đăng ký</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="hero-banner">
        <h1>Chào mừng đến với Shop</h1>

    </div>

    <div class="container">
        <h2 class="section-title">Sản Phẩm Nổi Bật</h2>
        <div class="grid">
            <?php if (empty($products)): ?>
                <p style="grid-column: 1/-1; text-align: center; color: #94a3b8;">Hiện tại chưa có sản phẩm nào.</p>
            <?php else: ?>
                <?php foreach ($products as $p):
                    $cleanPrice = (int) preg_replace('/[^\d]/', '', $p['price']);
                    ?>
                    <div class="p-card">
                        <div class="p-img-box">
                            <img src="<?= htmlspecialchars($p['image'] ?? '') ?>"
                                onerror="this.src='https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500'">
                        </div>
                        <div class="p-info">
                            <div class="p-title"><?= htmlspecialchars($p['tenSP'] ?? $p['description'] ?? 'Sản phẩm') ?></div>
                            <div class="p-price"><?= number_format($cleanPrice, 0, ',', '.') ?>đ</div>

                            <div class="btn-group-actions">
                                <div class="btn-row-top">
                                    <a href="chi-tiet.php?id=<?= urlencode($p['product_id']) ?>" class="btn-action btn-detail">
                                        🔍 Chi tiết
                                    </a>
                                    <button onclick="addToCartAjax('<?= urlencode($p['product_id']) ?>')"
                                        class="btn-action btn-add-cart">
                                        🛒 Thêm giỏ
                                    </button>
                                </div>
                                <a href="cart.php?add=<?= urlencode($p['product_id']) ?>" class="btn-action btn-buy-now">
                                    ⚡ Mua ngay
                                </a>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function addToCartAjax(productId) {
            // Gửi request fetch ngầm sang file cart.php kèm tham số ajax=1
            fetch('cart.php?add=' + productId + '&ajax=1')
                .then(response => response.json())
                .then(data => {
                    const toast = document.getElementById('cartToast');
                    const toastIcon = document.getElementById('toastIcon');
                    const toastMsg = document.getElementById('toastMessage');
                    const badge = document.getElementById('cartBadge');

                    if (data.status === 'success') {
                        // Cập nhật số lượng trên giỏ hàng Header ngay lập tức
                        if (badge) {
                            badge.innerText = data.cart_count;
                        }
                        // Gán thông báo thành công
                        toast.classList.remove('toast-error');
                        toastIcon.innerText = "🎉";
                        toastMsg.innerText = "Thêm sản phẩm vào giỏ hàng thành công!";
                    } else {
                        // Gán thông báo lỗi (Nếu chưa đăng nhập)
                        toast.classList.add('toast-error');
                        toastIcon.innerText = "⚠️";
                        toastMsg.innerText = data.message;

                        // Nếu chưa đăng nhập, tự động đá sang trang login sau 1.5 giây
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1500);
                    }

                    // Kích hoạt hiệu ứng trượt Toast ra ngoài màn hình
                    toast.classList.add('show');

                    // Tự ẩn thông báo đi sau 2.5 giây
                    setTimeout(() => {
                        toast.classList.remove('show');
                    }, 2500);
                })
                .catch(error => {
                    console.error('Lỗi kết nối hệ thống giỏ hàng:', error);
                });
        }
    </script>
    <div id="chatbot-wrapper">
    <button id="chatbot-toggle-btn" onclick="toggleChatbot()">
        <i class="fas fa-comments"></i> <span id="chat-badge">1</span>
    </button>

    <div id="chatbot-box">
        <div class="chat-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div class="online-dot"></div>
                <strong>Trợ lý ảo TechShop 🤖</strong>
            </div>
            <button onclick="toggleChatbot()" style="background:none; border:none; color:white; cursor:pointer; font-size:16px;">✕</button>
        </div>
        
        <div class="chat-body" id="chat-messages-container">
            <div class="msg-bot">Xin chào! Mình là Trợ lý ảo TechShop. Bạn cần tìm sản phẩm trong khoảng giá nào thế? (Ví dụ: Gõ "Tìm sản phẩm từ 100.000 đến 2.000.000")</div>
        </div>

        <div class="chat-footer">
            <input type="text" id="chat-user-input" placeholder="Nhập tin nhắn hỏi giá..." onkeypress="handleChatKeyPress(event)">
            <button onclick="sendChatMessage()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<style>
    #chatbot-wrapper { position: fixed; bottom: 25px; right: 25px; z-index: 9999; font-family: 'Segoe UI', system-ui, sans-serif; }
    
    /* Bong bóng tròn ban đầu */
    #chatbot-toggle-btn { 
        width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #7c3aed); 
        color: white; border: none; font-size: 24px; cursor: pointer; box-shadow: 0 4px 15px rgba(124, 92, 255, 0.4);
        position: relative; transition: all 0.3s;
    }
    #chatbot-toggle-btn:hover { transform: scale(1.05) rotate(-5deg); }
    #chat-badge { position: absolute; top: -2px; right: -2px; background: #ef4444; color: white; font-size: 11px; padding: 2px 6px; border-radius: 50%; font-weight: bold; }

    /* Hộp thoại Chat */
    #chatbot-box { 
        width: 360px; height: 480px; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        display: none; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; animation: fadeInUp 0.3s ease;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    
    .chat-header { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
    .online-dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; }
    
    .chat-body { flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 12px; }
    
    /* Các tin nhắn Bong bóng dòng chat */
    .msg-user { align-self: flex-end; background: #4f46e5; color: white; padding: 10px 14px; border-radius: 14px 14px 0 14px; max-width: 80%; font-size: 13px; line-height: 1.4; box-shadow: 0 2px 4px rgba(79,70,229,0.1); }
    .msg-bot { align-self: flex-start; background: #e2e8f0; color: #1e293b; padding: 10px 14px; border-radius: 14px 14px 14px 0; max-width: 80%; font-size: 13px; line-height: 1.4; }

    .chat-footer { padding: 10px; display: flex; gap: 8px; border-top: 1px solid #e2e8f0; background: white; }
    .chat-footer input { flex: 1; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 20px; outline: none; font-size: 13px; }
    .chat-footer input:focus { border-color: #4f46e5; }
    .chat-footer button { background: #4f46e5; color: white; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: background 0.2s; }
    .chat-footer button:hover { background: #3730a3; }

    /* LƯỚI CARD SẢN PHẨM TRẢ VỀ TRONG CHAT CỰC ĐẸP */
    .chat-products-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; margin-top: 8px; }
    .chat-prod-card { background: white; border-radius: 8px; border: 1px solid #e2e8f0; padding: 8px; display: flex; flex-direction: column; gap: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); text-decoration: none; color: inherit; transition: transform 0.2s; }
    .chat-prod-card:hover { transform: translateY(-2px); border-color: #4f46e5; }
    .chat-prod-img { width: 100%; height: 90px; object-fit: cover; border-radius: 4px; background: #f1f5f9; }
    .chat-prod-name { font-size: 12px; font-weight: bold; color: #1e293b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 34px; line-height: 1.4; }
    .chat-prod-price { font-size: 12px; font-weight: 800; color: #ef4444; }
</style>

<script>
    // Hàm ẩn/hiện hộp chat bong bóng
    function toggleChatbot() {
        const chatBox = document.getElementById('chatbot-box');
        const toggleBtn = document.getElementById('chatbot-toggle-btn');
        const badge = document.getElementById('chat-badge');
        
        if (chatBox.style.display === 'none' || chatBox.style.display === '') {
            chatBox.style.display = 'flex';
            if(badge) badge.style.display = 'none'; // Xem rồi thì ẩn đèn thông báo
        } else {
            chatBox.style.display = 'none';
        }
    }

    // Lắng nghe sự kiện gõ nút Enter
    function handleChatKeyPress(event) {
        if (event.key === 'Enter') {
            sendChatMessage();
        }
    }

    // Hàm gửi tin nhắn và render sản phẩm
    function sendChatMessage() {
        const inputEl = document.getElementById('chat-user-input');
        const message = inputEl.value.trim();
        if (message === '') return;

        const container = document.getElementById('chat-messages-container');

        // 1. Render tin nhắn của User ra màn hình chat
        const userMsgDiv = document.createElement('div');
        userMsgDiv.className = 'msg-user';
        userMsgDiv.innerText = message;
        container.appendChild(userMsgDiv);

        // Xóa trống thanh nhập liệu và cuộn xuống cuối chat
        inputEl.value = '';
        container.scrollTop = container.scrollHeight;

        // Tải hiệu ứng 3 chấm chờ phản hồi cho chuyên nghiệp
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'msg-bot';
        loadingDiv.innerText = '🤖 Đang tìm kiếm sản phẩm phù hợp...';
        container.appendChild(loadingDiv);
        container.scrollTop = container.scrollHeight;

        // 2. Gọi Ajax Fetch gửi ngầm chuỗi văn bản lên Server PHP xử lý bóc tách khoảng giá
        fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type: application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(message)
        })
        .then(res => res.json())
        .then(data => {
            // Xóa dòng thông báo Đang tìm kiếm
            container.removeChild(loadingDiv);

            if (data && data.status === 'success') {
                // Render câu trả lời văn bản của chatbot
                const botMsgDiv = document.createElement('div');
                botMsgDiv.className = 'msg-bot';
                botMsgDiv.innerHTML = data.reply;
                container.appendChild(botMsgDiv);

                // Nếu trong khoảng giá tìm thấy các sản phẩm phù hợp từ MySQL, dựng lưới Card sản phẩm luôn
                if (data.products && data.products.length > 0) {
                    const gridDiv = document.createElement('div');
                    gridDiv.className = 'chat-products-grid';

                    data.products.forEach(p => {
                        // Làm sạch giá tiền
                        let formatPrice = p.price;
                        if(!isNaN(p.price)) {
                            formatPrice = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(p.price);
                        }

                        gridDiv.innerHTML += `
                            <a href="chi-tiet.php?id=${p.product_id}" class="chat-prod-card" target="_blank">
                                <img src="${p.image}" class="chat-prod-img" onerror="this.src='https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=200'">
                                <div class="chat-prod-name">${p.description || 'Sản phẩm'}</div>
                                <div class="chat-prod-price">${formatPrice}</div>
                            </a>
                        `;
                    });
                    container.appendChild(gridDiv);
                } else if (message.match(/\d+/)) {
                    // Nếu khách gõ số tìm giá nhưng database hết sạch hàng
                    const noProdDiv = document.createElement('div');
                    noProdDiv.className = 'msg-bot';
                    noProdDiv.style.color = '#ef4444';
                    noProdDiv.innerText = 'Hiện tại kho hàng TechShop đã hết sản phẩm trong khoảng giá này rồi ạ. Bạn thử tìm khoảng giá khác xem sao nha!';
                    container.appendChild(noProdDiv);
                }
            }
            // Cuộn xuống cuối cùng để đọc kết quả
            container.scrollTop = container.scrollHeight;
        })
        .catch(() => {
            container.removeChild(loadingDiv);
            console.log('Lỗi kết nối chatbot!');
        });
    }
</script>
</body>

</html>