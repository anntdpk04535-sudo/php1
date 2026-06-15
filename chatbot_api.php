<?php
// chatbot_api.php
session_start();
require_once __DIR__ . "/db_utils.php";
$db = new DB_UTILS();

header('Content-Type: application/json; charset=utf-8');

// Nhận dữ liệu chat từ phía giao diện gửi lên
$inputData = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($_POST['message'] ?? $inputData['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['status' => 'success', 'reply' => 'Chào bạn! Mình có thể giúp gì cho bạn hôm nay?']);
    exit;
}

// ĐÃ SỬA LỖI: Đổi mb_toLowerCase thành mb_strtolower chuẩn cú pháp PHP
$cleanMessage = mb_strtolower($userMessage, 'UTF-8');

// Quy đổi nhanh các chữ viết tắt phổ biến của người dùng (k, ngàn, triệu) về số 000 để toán toán chuẩn
$cleanMessage = str_replace(['tr triệu', 'triệu', 'tr'], '000000', $cleanMessage);
$cleanMessage = str_replace(['k', 'ngàn', 'nghìn'], '000', $cleanMessage);
$cleanMessage = str_replace(['.', ',', 'đ', '₫', 'đồng', ' '], '', $cleanMessage);

$reply = "";
$productsResult = [];

// ──────── KỊCH BẢN 1: PHÂN TÍCH KHOẢNG GIÁ (Ví dụ: từ 5000 đến 1000000) ────────
if (preg_match('/(?:từ|khoảng)?(\d+)(?:đến|-|đếnkhoảng)(\d+)/', $cleanMessage, $matches)) {
    $minPrice = (int)$matches[1];
    $maxPrice = (int)$matches[2];
    
    if ($minPrice > $maxPrice) {
        $temp = $minPrice; $minPrice = $maxPrice; $maxPrice = $temp;
    }

    // Thực hiện ép kiểu dữ liệu CAST giá tiền trong CSDL về số để so sánh chính xác khoảng giá của bạn hỏi
    $productsResult = $db->getAll("SELECT * FROM products WHERE CAST(REPLACE(REPLACE(price, '.', ''), 'đ', '') AS UNSIGNED) BETWEEN ? AND ? LIMIT 6", [$minPrice, $maxPrice]);
    $reply = "Dưới đây là các sản phẩm có giá từ **" . number_format($minPrice, 0, ',', '.') . "đ** đến **" . number_format($maxPrice, 0, ',', '.') . "đ** thỏa mãn điều kiện của bạn:";
}
// ──────── KỊCH BẢN 2: PHÂN TÍCH GIÁ DƯỚI MỘT MỐC (Ví dụ: dưới 500k) ────────
elseif (preg_match('/(?:dưới|nhỏhơn|thấphơn)(\d+)/', $cleanMessage, $matches)) {
    $maxPrice = (int)$matches[1];
    $productsResult = $db->getAll("SELECT * FROM products WHERE CAST(REPLACE(REPLACE(price, '.', ''), 'đ', '') AS UNSIGNED) <= ? LIMIT 6", [$maxPrice]);
    $reply = "TechShop có các sản phẩm có giá dưới **" . number_format($maxPrice, 0, ',', '.') . "đ** dành cho bạn:";
}
// ──────── KỊCH BẢN 3: PHÂN TÍCH GIÁ TRÊN MỘT MỐC (Ví dụ: trên 1 triệu) ────────
elseif (preg_match('/(?:trên|lớnhơn|caohơn|hơn)(\d+)/', $cleanMessage, $matches)) {
    $minPrice = (int)$matches[1];
    $productsResult = $db->getAll("SELECT * FROM products WHERE CAST(REPLACE(REPLACE(price, '.', ''), 'đ', '') AS UNSIGNED) >= ? LIMIT 6", [$minPrice]);
    $reply = "Dưới đây là danh sách sản phẩm phân khúc cao cấp giá trên **" . number_format($minPrice, 0, ',', '.') . "đ**:";
}
// ──────── KỊCH BẢN 4: CHAT TỰ ĐỘNG THÔNG THƯỜNG ────────
else {
    $rawLow = mb_strtolower($userMessage, 'UTF-8');
    if (str_contains($rawLow, 'chào') || str_contains($rawLow, 'hi') || str_contains($rawLow, 'hello')) {
        $reply = "Xin chào! Mình là Trợ lý ảo TechShop. Bạn có thể hỏi mình tìm sản phẩm theo khoảng giá (Ví dụ: gõ *'có sản phẩm từ 5000 đến 1000000 không'*).";
    } elseif (str_contains($rawLow, 'ship') || str_contains($rawLow, 'giao hàng')) {
        $reply = "TechShop giao hàng toàn quốc nhanh chóng từ 2-4 ngày và freeship cho đơn hàng từ 500k ạ!";
    } else {
        $reply = "TechShop có rất nhiều sản phẩm công nghệ chất lượng! Bạn có thể thử gõ câu hỏi tìm giá như: **'Sản phẩm từ 10k đến 500k'** để mình lọc giúp bạn nhé.";
    }
}

// Xuất JSON sạch trả về Client
echo json_encode([
    'status' => 'success',
    'reply' => $reply,
    'products' => $productsResult
], JSON_UNESCAPED_UNICODE);
exit;