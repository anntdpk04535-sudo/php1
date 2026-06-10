<?php
session_start();

if(isset($_SESSION['user'])){
   header('Location: index.php');
   exit;
}
 




?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .profile-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            width: 350px;
        }
        .profile-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
            margin-bottom: 15px;
        }
        .profile-card h2 {
            margin: 10px 0 5px;
            color: #333;
        }
        .profile-card p {
            color: #666;
            margin: 5px 0;
        }
        .info-group {
            text-align: left;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .info-item {
            margin-bottom: 10px;
            font-size: 14px;
        }
        .label {
            font-weight: bold;
            color: #444;
        }
        .logout-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #dc3545;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

<div class="profile-card">
    <img src="<?php ?>" alt="User Avatar">
    <h2>Chào mừng trở lại!</h2>
    <p><?php
    // echo $user['fullname'];
    
   



    


    ?></p>

    <div class="info-group">
        <div class="info-item">
            <span class="label">Email:</span> 
            <span><?php 
             ?></span>
        </div>
        <div class="info-item">
            <span class="label">Vai trò:</span> 
            <span><?php 
             ?></span>
        </div>
    </div>

    <a href="#" class="logout-btn">Đăng xuất</a>
</div>

</body>
</html>