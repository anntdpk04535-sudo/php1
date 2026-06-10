<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
 
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Sans', sans-serif;
            background-color: #0e0c0a;
            background-image:
                radial-gradient(ellipse 60% 50% at 70% 20%, rgba(180, 140, 80, 0.12) 0%, transparent 60%),
                radial-gradient(ellipse 40% 40% at 20% 80%, rgba(100, 70, 30, 0.10) 0%, transparent 50%);
        }
 
        h2 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.6rem;
            font-weight: 300;
            letter-spacing: 0.04em;
            color: #e8dfc8;
            text-align: center;
            margin-bottom: 2.4rem;
            line-height: 1;
        }
 
        form {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid rgba(200, 170, 100, 0.15);
            border-radius: 2px;
            padding: 3rem 2.8rem 2.6rem;
            backdrop-filter: blur(12px);
            box-shadow:
                0 0 0 1px rgba(0,0,0,0.4),
                0 40px 80px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(200, 170, 100, 0.08);
            position: relative;
        }
 
        form::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(200, 170, 100, 0.4), transparent);
        }
 
        form > div {
            margin-bottom: 1.4rem;
        }
 
        form > div:last-of-type {
            margin-bottom: 0;
        }
 
        label {
            display: block;
            font-size: 0.72rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #9e8e70;
        }
 
        p label {
            margin-bottom: 0.55rem;
        }
 
        label span[style] {
            color: #c8965a !important;
            font-size: 0.8rem;
        }
 
        input[type="text"],
        input[type="password"] {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(200, 170, 100, 0.18);
            border-radius: 2px;
            padding: 0.75rem 1rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 300;
            color: #e8dfc8;
            outline: none;
            transition: border-color 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
            
        }
 
        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: rgba(158, 142, 112, 0.4);
        }
 
        input[type="text"]:hover,
        input[type="password"]:hover {
            border-color: rgba(200, 170, 100, 0.35);
            background: rgba(255, 255, 255, 0.05);
        }
 
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: rgba(200, 160, 80, 0.55);
            background: rgba(200, 160, 80, 0.04);
            box-shadow: 0 0 0 3px rgba(200, 160, 80, 0.07);
        }
 
        /* Submit + remember row */
        form > div:has(input[type="submit"]) {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1.8rem;
            margin-bottom: 1.2rem;
        }
 
        input[type="submit"] {
            flex-shrink: 0;
            background: linear-gradient(135deg, #c8965a 0%, #a87040 100%);
            border: none;
            border-radius: 2px;
            padding: 0.72rem 2rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #1a1208;
            cursor: pointer;
            outline: none;
            transition: opacity 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 20px rgba(200, 150, 90, 0.25);
        }
 
        input[type="submit"]:hover {
            opacity: 0.88;
            transform: translateY(-1px);
            box-shadow: 0 6px 28px rgba(200, 150, 90, 0.35);
        }
 
        input[type="submit"]:active {
            transform: translateY(0);
            opacity: 1;
        }
 
        /* Remember me label */
        form > div:has(input[type="submit"]) > label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            cursor: pointer;
            font-size: 0.78rem;
            letter-spacing: 0.05em;
            text-transform: none;
            color: #7a6e5a;
            font-weight: 400;
            user-select: none;
        }
 
        input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 15px;
            height: 15px;
            border: 1px solid rgba(200, 170, 100, 0.3);
            border-radius: 2px;
            background: transparent;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            transition: border-color 0.2s, background 0.2s;
        }
 
        input[type="checkbox"]:checked {
            background: rgba(200, 150, 90, 0.85);
            border-color: rgba(200, 150, 90, 0.85);
        }
 
        input[type="checkbox"]:checked::after {
            content: '';
            position: absolute;
            left: 4px; top: 1.5px;
            width: 4px; height: 8px;
            border: 1.5px solid #1a1208;
            border-top: none;
            border-left: none;
            transform: rotate(45deg);
        }
 
        /* Lost password link */
        form > div:last-of-type a {
            display: inline-block;
            font-size: 0.73rem;
            letter-spacing: 0.06em;
            color: #7a6e5a;
            text-decoration: none;
            border-bottom: 1px solid rgba(200, 170, 100, 0.2);
            padding-bottom: 1px;
            transition: color 0.2s, border-color 0.2s;
        }
 
        form > div:last-of-type a:hover {
            color: #c8965a;
            border-color: rgba(200, 150, 90, 0.5);
        }
 
        form > div:last-of-type {
            text-align: center;
            padding-top: 0.8rem;
            border-top: 1px solid rgba(200, 170, 100, 0.08);
        }
    </style>
</head>
<body>

    
    <h2>Login</h2>
 
    <form action="login.php" method="post">
 
        <div>
        <p><label for="">Email hoặc sđt <span style="color: red;">*</span></label></p>
        <input type="text" name="username" value="">
        </div>
 
        <div>
        <p><label for="">Mật khẩu <span style="color: red;">*</span></label></p>
        <input type="password" name="password" value="">
        </div>
 
        <div>
            <input type="submit" name="btnlogin" value="Login">
            <label for="">
                <input type="checkbox" name="remember" value="">
                <span>Remember me</span>
            </label>
        </div>
 
        <div>
            <a href="#">Lost your password</a>
        </div>
 
 
 
 
    </form>
    

 
 
</body>
</html>