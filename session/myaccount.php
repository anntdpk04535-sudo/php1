<?php

session_start();
 if(isset($_SESSION['user']) && $_SESSION['user']!="") {
    $user = $_SESSION['user'];

 }

 if(isset($_SESSION['pass']) && $_SESSION['pass']!="") {
    $pass = $_SESSION['pass'];

 }

  if($user!="") 
    $logined = '<a href="myaccount.php">'.$user.'</a>';
  else $logined = '<a href="login-register.php">Login</a>';
  

?>



<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CAO NGUYÊN — Specialty Coffee</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400&family=Playfair+Display:ital@1&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --bg: #0a0e07;
    --bg2: #111508;
    --bg3: #161c0c;
    --gold: #c4943a;
    --gold2: #e8b96a;
    --cream: #e2d5b8;
    --muted: #7a8a62;
    --dark-muted: #3a4530;
    --accent: #4a6635;
  }

  html { scroll-behavior: smooth; }

  body {
    background: var(--bg);
    color: var(--cream);
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    overflow-x: hidden;
    cursor: default;
  }

  /* ── CUSTOM CURSOR ── */
  * { cursor: none !important; }
  #cursor {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: var(--gold);
    position: fixed;
    top: 0; left: 0;
    pointer-events: none;
    z-index: 9999;
    transition: transform 0.15s ease, opacity 0.3s;
    mix-blend-mode: difference;
  }
  #cursor-ring {
    width: 36px; height: 36px;
    border-radius: 50%;
    border: 1px solid var(--gold);
    position: fixed;
    top: 0; left: 0;
    pointer-events: none;
    z-index: 9998;
    transition: transform 0.4s cubic-bezier(.25,.8,.25,1), opacity 0.3s, width 0.3s, height 0.3s;
    opacity: 0.5;
  }

  /* ── NAV ── */
  nav {
    position: fixed; top: 0; width: 100%; z-index: 100;
    display: flex; justify-content: space-between; align-items: center;
    padding: 2rem 4rem;
    mix-blend-mode: difference;
  }
  .nav-logo {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    letter-spacing: 0.4em;
    color: var(--cream);
    text-decoration: none;
    text-transform: uppercase;
  }
  .nav-links {
    display: flex; gap: 3rem; list-style: none;
  }
  .nav-links a {
    color: var(--cream);
    text-decoration: none;
    font-size: 0.7rem;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    opacity: 0.7;
    transition: opacity 0.3s;
  }
  .nav-links a:hover { opacity: 1; }

  /* ── HERO ── */
  .hero {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
    position: relative;
    overflow: hidden;
  }

  .hero-bg {
    position: absolute; inset: 0;
    background:
      radial-gradient(ellipse 60% 80% at 70% 50%, #1e2c12 0%, transparent 60%),
      radial-gradient(ellipse 40% 60% at 20% 80%, #0f1a08 0%, transparent 50%);
  }

  /* Noise texture overlay */
  .hero-bg::after {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    opacity: 0.4;
    pointer-events: none;
  }

  .hero-left {
    display: flex; flex-direction: column;
    justify-content: flex-end;
    padding: 0 4rem 6rem;
    position: relative; z-index: 2;
  }

  .hero-tag {
    font-size: 0.65rem;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 2rem;
    opacity: 0;
    animation: fadeUp 1s 0.3s forwards;
  }

  .hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(5rem, 9vw, 9rem);
    font-weight: 300;
    line-height: 0.9;
    letter-spacing: -0.02em;
    margin-bottom: 3rem;
    opacity: 0;
    animation: fadeUp 1.2s 0.5s forwards;
  }

  .hero-title em {
    font-style: italic;
    color: var(--gold);
    display: block;
  }

  .hero-desc {
    max-width: 340px;
    font-size: 0.85rem;
    line-height: 1.9;
    color: var(--muted);
    margin-bottom: 3rem;
    opacity: 0;
    animation: fadeUp 1s 0.8s forwards;
  }

  .hero-cta {
    display: inline-flex; align-items: center; gap: 1.5rem;
    opacity: 0;
    animation: fadeUp 1s 1s forwards;
  }

  .btn-primary {
    background: var(--gold);
    color: var(--bg);
    padding: 1rem 2.5rem;
    font-size: 0.7rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    text-decoration: none;
    font-family: 'Jost', sans-serif;
    font-weight: 400;
    transition: background 0.3s;
  }
  .btn-primary:hover { background: var(--gold2); }

  .btn-ghost {
    font-size: 0.7rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--cream);
    text-decoration: none;
    opacity: 0.6;
    border-bottom: 1px solid currentColor;
    padding-bottom: 2px;
    transition: opacity 0.3s;
  }
  .btn-ghost:hover { opacity: 1; }

  .hero-right {
    position: relative; z-index: 2;
    display: flex; align-items: center; justify-content: center;
  }

  .hero-visual {
    position: relative;
    width: 460px; height: 560px;
    opacity: 0;
    animation: fadeIn 1.5s 0.6s forwards;
  }

  /* Coffee circle illustration */
  .circle-outer {
    position: absolute;
    width: 380px; height: 380px;
    border-radius: 50%;
    border: 1px solid rgba(196, 148, 58, 0.15);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    animation: spinSlow 40s linear infinite;
  }
  .circle-inner {
    position: absolute;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: radial-gradient(ellipse at 40% 35%, #2a3d18, #0f1a08);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 80px rgba(196, 148, 58, 0.08), inset 0 0 40px rgba(0,0,0,0.5);
  }
  .circle-inner::after {
    content: '';
    position: absolute;
    width: 180px; height: 180px;
    border-radius: 50%;
    border: 1px solid rgba(196, 148, 58, 0.25);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
  }

  .bean {
    position: absolute;
    width: 24px; height: 40px;
    border-radius: 50%;
    background: var(--bg3);
    border: 1px solid rgba(196, 148, 58, 0.3);
    top: 50%; left: 50%;
  }
  .bean::after {
    content: '';
    position: absolute;
    width: 1px; height: 70%;
    background: rgba(196, 148, 58, 0.4);
    top: 15%; left: 50%;
  }

  .hero-year {
    position: absolute;
    bottom: 2rem; right: 2rem;
    font-size: 0.6rem;
    letter-spacing: 0.3em;
    color: var(--muted);
    writing-mode: vertical-rl;
    text-transform: uppercase;
    opacity: 0;
    animation: fadeIn 1s 1.5s forwards;
  }

  .hero-scroll {
    position: absolute;
    bottom: 4rem; left: 50%;
    transform: translateX(-50%);
    display: flex; flex-direction: column; align-items: center; gap: 1rem;
    z-index: 10;
    opacity: 0;
    animation: fadeIn 1s 1.8s forwards;
  }
  .scroll-line {
    width: 1px; height: 60px;
    background: linear-gradient(to bottom, var(--gold), transparent);
    animation: scrollPulse 2s ease-in-out infinite;
  }
  .scroll-label {
    font-size: 0.6rem;
    letter-spacing: 0.3em;
    color: var(--muted);
    text-transform: uppercase;
    writing-mode: vertical-rl;
  }

  /* ── MARQUEE ── */
  .marquee-wrap {
    border-top: 1px solid var(--dark-muted);
    border-bottom: 1px solid var(--dark-muted);
    overflow: hidden;
    padding: 1.2rem 0;
    background: var(--bg2);
  }
  .marquee-track {
    display: flex; gap: 4rem;
    animation: marquee 30s linear infinite;
    white-space: nowrap;
  }
  .marquee-item {
    font-size: 0.65rem;
    letter-spacing: 0.35em;
    text-transform: uppercase;
    color: var(--muted);
    display: flex; align-items: center; gap: 1.5rem;
    flex-shrink: 0;
  }
  .marquee-dot {
    width: 4px; height: 4px;
    border-radius: 50%;
    background: var(--gold);
    flex-shrink: 0;
  }

  /* ── ORIGINS SECTION ── */
  .origins {
    padding: 8rem 4rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8rem;
    align-items: center;
  }

  .section-label {
    font-size: 0.6rem;
    letter-spacing: 0.4em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 1.5rem;
    display: flex; align-items: center; gap: 1rem;
  }
  .section-label::before {
    content: '';
    display: block;
    width: 30px; height: 1px;
    background: var(--gold);
  }

  .origins-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.8rem, 4vw, 4.5rem);
    font-weight: 300;
    line-height: 1.1;
    margin-bottom: 2rem;
  }
  .origins-title em {
    font-style: italic;
    color: var(--gold);
  }

  .origins-text {
    font-size: 0.85rem;
    line-height: 1.9;
    color: var(--muted);
    max-width: 440px;
    margin-bottom: 3rem;
  }

  .stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    border-top: 1px solid var(--dark-muted);
    padding-top: 2.5rem;
  }
  .stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3.5rem;
    font-weight: 300;
    color: var(--gold);
    line-height: 1;
  }
  .stat-label {
    font-size: 0.65rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--muted);
    margin-top: 0.5rem;
  }

  .origins-visual {
    position: relative;
    height: 540px;
  }
  .origins-card {
    position: absolute;
    background: var(--bg3);
    border: 1px solid var(--dark-muted);
  }
  .origins-card-main {
    width: 320px; height: 420px;
    right: 0; top: 0;
    display: flex; align-items: flex-end;
    padding: 2rem;
    background: linear-gradient(160deg, #1a2a0f 0%, #0a0e07 100%);
    overflow: hidden;
  }
  .origins-card-main::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 30% 20%, rgba(74, 102, 53, 0.3), transparent 60%);
  }
  .origins-card-float {
    width: 200px; height: 160px;
    left: 0; bottom: 0;
    display: flex; flex-direction: column;
    justify-content: flex-end;
    padding: 1.5rem;
    background: var(--accent);
  }

  .card-quote {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem;
    font-style: italic;
    font-weight: 300;
    color: var(--cream);
    line-height: 1.2;
    position: relative; z-index: 1;
  }
  .card-float-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 4rem;
    font-weight: 300;
    color: rgba(255,255,255,0.15);
    line-height: 1;
  }
  .card-float-label {
    font-size: 0.65rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.7);
    margin-top: 0.25rem;
  }

  /* altitude decoration */
  .altitude-lines {
    position: absolute;
    right: 330px; top: 60px;
    display: flex; flex-direction: column; gap: 8px;
  }
  .alt-line {
    height: 1px;
    background: var(--dark-muted);
  }
  .alt-label {
    font-size: 0.55rem;
    letter-spacing: 0.2em;
    color: var(--muted);
  }

  /* ── PRODUCTS ── */
  .products {
    padding: 6rem 4rem;
    background: var(--bg2);
  }
  .products-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 4rem;
  }
  .products-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.5rem, 4vw, 4rem);
    font-weight: 300;
  }

  .product-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2px;
  }

  .product-card {
    position: relative;
    background: var(--bg3);
    padding: 3rem 2.5rem;
    border: 1px solid var(--dark-muted);
    overflow: hidden;
    transition: border-color 0.4s;
    
  }
  .product-card:hover { border-color: rgba(196, 148, 58, 0.3); }

  .product-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(196, 148, 58, 0.05), transparent 60%);
    opacity: 0;
    transition: opacity 0.4s;
  }
  .product-card:hover::before { opacity: 1; }

  .product-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 0.85rem;
    color: var(--muted);
    margin-bottom: 3rem;
    letter-spacing: 0.1em;
  }

  .product-icon {
    width: 60px; height: 80px;
    margin-bottom: 2.5rem;
    position: relative;
  }

  .product-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem;
    font-weight: 300;
    margin-bottom: 0.5rem;
    line-height: 1.2;
  }
  .product-origin {
    font-size: 0.65rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 1.5rem;
  }
  .product-desc {
    font-size: 0.8rem;
    color: var(--muted);
    line-height: 1.8;
    margin-bottom: 2.5rem;
  }

  .product-footer {
    display: flex; justify-content: space-between; align-items: center;
    border-top: 1px solid var(--dark-muted);
    padding-top: 1.5rem;
  }
  .product-price {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    font-weight: 300;
  }
  .product-add {
    width: 36px; height: 36px;
    border: 1px solid var(--dark-muted);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    color: var(--gold);
    transition: all 0.3s;
    text-decoration: none;
    cursor: none;
  }
  .product-add:hover {
    background: var(--gold);
    color: var(--bg);
    border-color: var(--gold);
  }

  /* Cup SVG icons */
  .cup-svg { width: 100%; height: 100%; }

  /* ── PHILOSOPHY ── */
  .philosophy {
    padding: 10rem 4rem;
    display: grid;
    grid-template-columns: 1fr 1.4fr;
    gap: 6rem;
    align-items: center;
    position: relative;
    overflow: hidden;
  }
  .philosophy::before {
    content: 'CAO NGUYÊN';
    position: absolute;
    font-family: 'Cormorant Garamond', serif;
    font-size: 18vw;
    font-weight: 300;
    color: rgba(255,255,255, 0.015);
    white-space: nowrap;
    bottom: -2rem;
    left: -1rem;
    pointer-events: none;
  }

  .philosophy-quote {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2rem, 3vw, 3.2rem);
    font-weight: 300;
    line-height: 1.35;
    font-style: italic;
    color: var(--cream);
  }
  .philosophy-quote span { color: var(--gold); }

  .philosophy-right 
  .philosophy-text {
    font-size: 0.85rem;
    line-height: 1.9;
    color: var(--muted);
    margin-bottom: 2rem;
  }

  .values-list {
    list-style: none;
    display: flex; flex-direction: column; gap: 1.5rem;
    margin-top: 3rem;
    border-top: 1px solid var(--dark-muted);
    padding-top: 2.5rem;
  }
  .value-item {
    display: grid;
    grid-template-columns: 2rem 1fr;
    gap: 1.5rem;
    align-items: start;
  }
  .value-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 0.8rem;
    color: var(--gold);
    padding-top: 3px;
  }
  .value-title {
    font-size: 0.85rem;
    font-weight: 400;
    margin-bottom: 0.3rem;
  }
  .value-desc {
    font-size: 0.78rem;
    color: var(--muted);
    line-height: 1.7;
  }

  /* ── BREWING GUIDE ── */
  .brew {
    background: var(--accent);
    padding: 6rem 4rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6rem;
    align-items: center;
  }
  .brew-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.5rem, 3.5vw, 4rem);
    font-weight: 300;
    line-height: 1.1;
    margin-bottom: 1.5rem;
  }
  .brew-sub {
    font-size: 0.85rem;
    line-height: 1.8;
    color: rgba(226, 213, 184, 0.7);
    max-width: 380px;
    margin-bottom: 2.5rem;
  }

  .brew-steps {
    display: flex;
    flex-direction: column;
    gap: 0;
  }
  .brew-step {
    display: grid;
    grid-template-columns: 3.5rem 1fr;
    gap: 1.5rem;
    padding: 2rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    align-items: center;
  }
  .brew-step:last-child { border-bottom: none; }
  .step-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3rem;
    font-weight: 300;
    color: rgba(255,255,255,0.15);
    line-height: 1;
  }
  .step-title {
    font-size: 0.85rem;
    font-weight: 400;
    margin-bottom: 0.3rem;
  }
  .step-desc {
    font-size: 0.75rem;
    color: rgba(226, 213, 184, 0.6);
    line-height: 1.7;
  }

  /* ── NEWSLETTER ── */
  .newsletter {
    padding: 8rem 4rem;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .newsletter::before {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(74, 102, 53, 0.12), transparent 70%);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
  }
  .newsletter-label {
    font-size: 0.6rem;
    letter-spacing: 0.4em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 1.5rem;
  }
  .newsletter-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.5rem, 4vw, 4rem);
    font-weight: 300;
    margin-bottom: 1.5rem;
    line-height: 1.1;
  }
  .newsletter-sub {
    font-size: 0.85rem;
    color: var(--muted);
    margin-bottom: 3rem;
    max-width: 400px;
    margin-left: auto; margin-right: auto;
    line-height: 1.8;
  }
  .newsletter-form {
    display: flex;
    max-width: 480px;
    margin: 0 auto;
    gap: 0;
  }
  .newsletter-input {
    flex: 1;
    background: var(--bg3);
    border: 1px solid var(--dark-muted);
    border-right: none;
    padding: 1rem 1.5rem;
    color: var(--cream);
    font-family: 'Jost', sans-serif;
    font-size: 0.85rem;
    outline: none;
  }
  .newsletter-input::placeholder { color: var(--muted); }
  .newsletter-input:focus { border-color: var(--gold); }
  .newsletter-btn {
    background: var(--gold);
    color: var(--bg);
    border: none;
    padding: 1rem 2rem;
    font-family: 'Jost', sans-serif;
    font-size: 0.7rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    font-weight: 400;
    cursor: none;
    transition: background 0.3s;
  }
  .newsletter-btn:hover { background: var(--gold2); }

  /* ── FOOTER ── */
  footer {
    background: var(--bg);
    border-top: 1px solid var(--dark-muted);
    padding: 4rem;
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 4rem;
  }
  .footer-logo {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.5rem;
    letter-spacing: 0.3em;
    color: var(--cream);
    margin-bottom: 1rem;
    text-transform: uppercase;
  }
  .footer-tagline {
    font-size: 0.78rem;
    color: var(--muted);
    line-height: 1.8;
    max-width: 240px;
  }
  .footer-col-title {
    font-size: 0.6rem;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 1.5rem;
  }
  .footer-links { list-style: none; display: flex; flex-direction: column; gap: 0.8rem; }
  .footer-links a {
    font-size: 0.8rem;
    color: var(--muted);
    text-decoration: none;
    transition: color 0.3s;
  }
  .footer-links a:hover { color: var(--cream); }
  .footer-bottom {
    border-top: 1px solid var(--dark-muted);
    padding: 1.5rem 4rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .footer-copy {
    font-size: 0.65rem;
    letter-spacing: 0.15em;
    color: var(--dark-muted);
  }

  /* ── ANIMATIONS ── */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
  }
  @keyframes spinSlow {
    to { transform: translate(-50%, -50%) rotate(360deg); }
  }
  @keyframes scrollPulse {
    0%, 100% { opacity: 0.3; transform: scaleY(1); }
    50%       { opacity: 1;   transform: scaleY(1.2); }
  }
  @keyframes marquee {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
  }
  @keyframes floatUp {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-12px); }
  }

  /* ── SCROLL REVEAL ── */
  .reveal {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.9s cubic-bezier(.25,.8,.25,1), transform 0.9s cubic-bezier(.25,.8,.25,1);
  }
  .reveal.visible {
    opacity: 1;
    transform: translateY(0);
  }

  @media (max-width: 900px) {
    nav { padding: 1.5rem 2rem; }
    .hero { grid-template-columns: 1fr; }
    .hero-right { display: none; }
    .hero-left { padding: 8rem 2rem 6rem; }
    .origins, .philosophy { grid-template-columns: 1fr; gap: 3rem; padding: 5rem 2rem; }
    .brew { grid-template-columns: 1fr; padding: 4rem 2rem; gap: 3rem; }
    .product-grid { grid-template-columns: 1fr; }
    footer { grid-template-columns: 1fr 1fr; padding: 3rem 2rem; gap: 3rem; }
    .footer-bottom { padding: 1.5rem 2rem; flex-direction: column; gap: 1rem; text-align: center; }
    .products { padding: 5rem 2rem; }
    .newsletter { padding: 6rem 2rem; }
    .newsletter-form { flex-direction: column; }
    .newsletter-input { border-right: 1px solid var(--dark-muted); border-bottom: none; }
  }
</style>
</head>
<body>

<!-- Custom cursor -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- Navigation -->
<nav>
  <a href="#" class="nav-logo">Cao Nguyên</a>
  <ul class="nav-links">
    <li><a href="#origins">Nguồn gốc</a></li>
    <li><a href="#products">Sản phẩm</a></li>
    <li><a href="#philosophy">Triết lý</a></li>
    <li><a href="#brew">Pha chế</a></li>
  </ul>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-bg"></div>

  <div class="hero-left">
    <p class="hero-tag">Buôn Ma Thuột · Đắk Lắk · Est. 2024</p>
    <h1 class="hero-title">
      Cà phê<br>
      <em>Đặc sản</em><br>
      Tây Nguyên
    </h1>
    <p class="hero-desc">
      Mỗi hạt cà phê là một câu chuyện được viết trên đất đỏ bazan —
      nơi mây mù và nắng vàng giao thoa trên những đồi cao nguyên hùng vĩ.
    </p>
    <div class="hero-cta">
      <a href="#products" class="btn-primary">Khám phá ngay</a>
      <a href="#philosophy" class="btn-ghost">Triết lý của chúng tôi</a>
    </div>
  </div>

  <div class="hero-right">
    <div class="hero-visual">
      <div class="circle-outer"></div>
      <div class="circle-inner"></div>
      <!-- Decorative beans -->
      <div class="bean" style="transform: translate(-12px,-20px) rotate(25deg); top: 38%; left: 55%;"></div>
      <div class="bean" style="transform: translate(8px,15px) rotate(-15deg); top: 44%; left: 42%;"></div>
      <div class="bean" style="transform: translate(-5px,8px) rotate(60deg); top: 52%; left: 58%;"></div>
      <!-- Label -->
      <div style="position:absolute; bottom: 3rem; left: 50%; transform: translateX(-50%); text-align: center;">
        <div style="font-family:'Cormorant Garamond',serif; font-size:0.7rem; letter-spacing:0.4em; color:var(--gold); text-transform:uppercase;">1,200m</div>
        <div style="font-size:0.55rem; letter-spacing:0.2em; color:var(--muted); text-transform:uppercase; margin-top:4px;">Độ cao</div>
      </div>
    </div>
  </div>

  <p class="hero-year">© Cao Nguyên 2024</p>

  <div class="hero-scroll">
    <span class="scroll-label">Cuộn xuống</span>
    <div class="scroll-line"></div>
  </div>
</section>

<!-- Marquee -->
<div class="marquee-wrap">
  <div class="marquee-track">
    <!-- Repeat twice for seamless loop -->
    <div class="marquee-item"><span class="marquee-dot"></span><span>Arabica Cao Nguyên</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Rang thủ công truyền thống</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>100% Đắk Lắk</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Chứng nhận hữu cơ</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Thu hái tay chọn lọc</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Single Origin</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Arabica Cao Nguyên</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Rang thủ công truyền thống</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>100% Đắk Lắk</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Chứng nhận hữu cơ</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Thu hái tay chọn lọc</span></div>
    <div class="marquee-item"><span class="marquee-dot"></span><span>Single Origin</span></div>
  </div>
</div>

<!-- Origins -->
<section class="origins" id="origins">
  <div class="reveal">
    <p class="section-label">Nguồn gốc</p>
    <h2 class="origins-title">Từ vùng đất<br><em>đỏ bazan</em><br>ngàn năm tuổi</h2>
    <p class="origins-text">
      Trải dài trên cao nguyên Buôn Ma Thuột ở độ cao 1.200 mét so với mực nước biển,
      những vườn cà phê của chúng tôi tắm mình trong khí hậu nhiệt đới ẩm ướt và đất
      đỏ bazan giàu khoáng chất — điều kiện lý tưởng để tạo ra những hạt cà phê
      với hương vị phức tạp và độc đáo.
    </p>
    <div class="stat-grid">
      <div>
        <div class="stat-num">1.200<span style="font-size:1.5rem">m</span></div>
        <div class="stat-label">Độ cao trồng cà phê</div>
      </div>
      <div>
        <div class="stat-num">40+</div>
        <div class="stat-label">Năm kinh nghiệm</div>
      </div>
      <div>
        <div class="stat-num">100%</div>
        <div class="stat-label">Thu hái thủ công</div>
      </div>
      <div>
        <div class="stat-num">12</div>
        <div class="stat-label">Giống cà phê quý hiếm</div>
      </div>
    </div>
  </div>

  <div class="origins-visual reveal">
    <div class="altitude-lines">
      <div class="alt-line" style="width:80px;"></div>
      <div class="alt-label">1.400m — Mây mù</div>
      <div class="alt-line" style="width:60px; margin-top:20px;"></div>
      <div class="alt-label">1.200m — Vườn cà phê</div>
      <div class="alt-line" style="width:90px; margin-top:30px;"></div>
      <div class="alt-label">800m — Thung lũng</div>
    </div>
    <div class="origins-card origins-card-main">
      <div style="position:absolute; top:2rem; left:2rem; font-size:0.6rem; letter-spacing:0.3em; color:var(--muted); text-transform:uppercase;">Đắk Lắk, Việt Nam</div>
      <!-- Decorative circle -->
      <div style="position:absolute; top:50%; left:50%; width:200px; height:200px; border-radius:50%; border:1px solid rgba(196,148,58,0.1); transform:translate(-50%,-50%);"></div>
      <div style="position:absolute; top:50%; left:50%; width:120px; height:120px; border-radius:50%; border:1px solid rgba(196,148,58,0.15); transform:translate(-50%,-50%);"></div>
      <p class="card-quote">"Hương thơm<br>của núi rừng<br>trong từng ngụm"</p>
    </div>
    <div class="origins-card origins-card-float">
      <div class="card-float-num">12°</div>
      <div class="card-float-label">Vĩ độ Bắc</div>
    </div>
  </div>
</section>

<!-- Products -->
<section class="products" id="products">
  <div class="products-header reveal">
    <div>
      <p class="section-label">Sản phẩm</p>
      <h2 class="products-title">Bộ sưu tập<br>đặc sản</h2>
    </div>
    <a href="#" class="btn-ghost">Xem tất cả</a>
  </div>

  <div class="product-grid">
    <div class="product-card reveal">
      <div class="product-num">01</div>
      <div class="product-icon">
        <svg viewBox="0 0 60 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="cup-svg">
          <rect x="10" y="20" width="40" height="50" rx="4" stroke="rgba(196,148,58,0.4)" stroke-width="1"/>
          <path d="M10 35h40" stroke="rgba(196,148,58,0.2)" stroke-width="1"/>
          <ellipse cx="30" cy="20" rx="20" ry="5" stroke="rgba(196,148,58,0.3)" stroke-width="1"/>
          <path d="M25 10 C25 5, 35 5, 35 10" stroke="rgba(196,148,58,0.3)" stroke-width="1" fill="none"/>
          <circle cx="30" cy="52" r="8" stroke="rgba(196,148,58,0.25)" stroke-width="1"/>
          <text x="30" y="56" text-anchor="middle" font-family="Cormorant Garamond" font-size="8" fill="rgba(196,148,58,0.5)">A</text>
        </svg>
      </div>
      <h3 class="product-name">Thiên Sơn<br>Arabica</h3>
      <p class="product-origin">Buôn Ma Thuột · Rang vừa</p>
      <p class="product-desc">Hương hoa nhài, vị mận và dư vị sô-cô-la đen tinh tế. Rang vừa để giữ trọn độ phức tạp của terroir cao nguyên.</p>
      <div class="product-footer">
        <span class="product-price">280.000₫</span>
        <a href="#" class="product-add">+</a>
      </div>
    </div>

    <div class="product-card reveal" style="transition-delay:0.15s">
      <div class="product-num">02</div>
      <div class="product-icon">
        <svg viewBox="0 0 60 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="cup-svg">
          <rect x="8" y="15" width="44" height="55" rx="3" stroke="rgba(196,148,58,0.4)" stroke-width="1"/>
          <path d="M8 30h44M8 45h44" stroke="rgba(196,148,58,0.15)" stroke-width="1"/>
          <rect x="15" y="22" width="30" height="6" rx="1" fill="rgba(196,148,58,0.1)" stroke="rgba(196,148,58,0.3)" stroke-width="0.5"/>
          <circle cx="30" cy="58" r="6" stroke="rgba(196,148,58,0.2)" stroke-width="1"/>
        </svg>
      </div>
      <h3 class="product-name">Đêm Tây<br>Nguyên</h3>
      <p class="product-origin">Cư M'gar · Rang đậm</p>
      <p class="product-desc">Đậm đà, mạnh mẽ với hương khói nhẹ và vị ca cao nguyên chất. Dành cho những người yêu cà phê truyền thống Việt Nam.</p>
      <div class="product-footer">
        <span class="product-price">320.000₫</span>
        <a href="#" class="product-add">+</a>
      </div>
    </div>

    <div class="product-card reveal" style="transition-delay:0.3s">
      <div class="product-num">03</div>
      <div class="product-icon">
        <svg viewBox="0 0 60 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="cup-svg">
          <path d="M15 20 L10 70 L50 70 L45 20 Z" stroke="rgba(196,148,58,0.4)" stroke-width="1" fill="none"/>
          <path d="M15 20h30" stroke="rgba(196,148,58,0.3)" stroke-width="1"/>
          <ellipse cx="30" cy="20" rx="15" ry="3" stroke="rgba(196,148,58,0.2)" stroke-width="1"/>
          <path d="M20 35 Q30 38 40 35M18 50 Q30 54 42 50" stroke="rgba(196,148,58,0.2)" stroke-width="1" fill="none"/>
        </svg>
      </div>
      <h3 class="product-name">Bình Minh<br>Natural</h3>
      <p class="product-origin">Ea H'leo · Rang nhạt</p>
      <p class="product-desc">Chế biến tự nhiên với 30 ngày phơi quả. Hương dâu tây, vải thiều và mật ong tự nhiên — một trải nghiệm cà phê đặc biệt.</p>
      <div class="product-footer">
        <span class="product-price">420.000₫</span>
        <a href="#" class="product-add">+</a>
      </div>
    </div>
  </div>
</section>

<!-- Philosophy -->
<section class="philosophy" id="philosophy">
  <div class="reveal">
    <p class="philosophy-quote">
      "Cà phê không chỉ là thức uống — đó là ngôn ngữ của <span>đất trời</span> và con người Tây Nguyên."
    </p>
  </div>

  <div class="philosophy-right reveal">
    <p class="section-label">Triết lý</p>
    <p class="philosophy-text">
      Chúng tôi tin rằng mỗi tách cà phê tốt bắt đầu từ một vùng đất tốt
      và đôi bàn tay cẩn thận. Từ khâu chọn giống, chăm sóc đất, thu hái
      đến rang xay — mọi bước đều được thực hiện với tình yêu và trách nhiệm
      với thiên nhiên.
    </p>
    <p class="philosophy-text">
      Chúng tôi làm việc trực tiếp với các gia đình nông dân Ê Đê bản địa,
      đảm bảo thu nhập công bằng và bền vững cho những người đã gắn bó
      cả đời với những đồi cà phê xanh mướt.
    </p>

    <ul class="values-list">
      <li class="value-item">
        <span class="value-num">01</span>
        <div>
          <div class="value-title">Truy xuất nguồn gốc</div>
          <div class="value-desc">Từng túi cà phê đều có mã QR dẫn đến câu chuyện của vườn và người trồng.</div>
        </div>
      </li>
      <li class="value-item">
        <span class="value-num">02</span>
        <div>
          <div class="value-title">Canh tác bền vững</div>
          <div class="value-desc">Không thuốc trừ sâu, không phân bón hóa học — thuận theo thiên nhiên.</div>
        </div>
      </li>
      <li class="value-item">
        <span class="value-num">03</span>
        <div>
          <div class="value-title">Thương mại công bằng</div>
          <div class="value-desc">Trả giá cao hơn 40% so với thị trường cho nông dân đối tác.</div>
        </div>
      </li>
    </ul>
  </div>
</section>

<!-- Brewing Guide -->
<section class="brew" id="brew">
  <div class="reveal">
    <p class="section-label" style="color:rgba(196,148,58,0.8);">Hướng dẫn</p>
    <h2 class="brew-title">Nghệ thuật<br>pha chế<br>phin Việt</h2>
    <p class="brew-sub">Phin cà phê là linh hồn của văn hóa cà phê Việt Nam — một nghi lễ chậm rãi, thiền định và sâu sắc.</p>
    <a href="#" class="btn-primary" style="display:inline-block;">Tải hướng dẫn đầy đủ</a>
  </div>

  <div class="brew-steps reveal">
    <div class="brew-step">
      <div class="step-num">01</div>
      <div>
        <div class="step-title">Chuẩn bị phin</div>
        <div class="step-desc">Chần phin bằng nước sôi 30 giây. Cho 20g cà phê xay vừa, nhấn lọc nhẹ tay.</div>
      </div>
    </div>
    <div class="brew-step">
      <div class="step-num">02</div>
      <div>
        <div class="step-title">Làm ướt cà phê</div>
        <div class="step-desc">Rót 30ml nước 92°C, đợi 30 giây để cà phê nở ra — giai đoạn bloom quan trọng.</div>
      </div>
    </div>
    <div class="brew-step">
      <div class="step-num">03</div>
      <div>
        <div class="step-title">Rót nước chính</div>
        <div class="step-desc">Thêm 150ml nước 92°C. Đậy nắp và chờ 4-5 phút để cà phê nhỏ giọt.</div>
      </div>
    </div>
    <div class="brew-step">
      <div class="step-num">04</div>
      <div>
        <div class="step-title">Thưởng thức</div>
        <div class="step-desc">Uống đen để cảm nhận trọn vẹn, hoặc thêm đá và sữa đặc theo sở thích.</div>
      </div>
    </div>
  </div>
</section>

<!-- Newsletter -->
<section class="newsletter">
  <p class="newsletter-label reveal">Kết nối</p>
  <h2 class="newsletter-title reveal">Nhận câu chuyện<br>từ đồi cà phê</h2>
  <p class="newsletter-sub reveal">Đăng ký để nhận bản tin hàng tháng về mùa vụ, công thức pha chế và câu chuyện từ những người trồng cà phê Tây Nguyên.</p>
  <div class="newsletter-form reveal">
    <input type="email" class="newsletter-input" placeholder="email của bạn">
    <button class="newsletter-btn">Đăng ký</button>
  </div>
</section>

<!-- Footer -->
<footer>
  <div>
    <div class="footer-logo">Cao Nguyên</div>
    <p class="footer-tagline">Cà phê đặc sản từ vùng đất đỏ bazan Buôn Ma Thuột, Đắk Lắk — Tây Nguyên Việt Nam.</p>
  </div>
  <div>
    <div class="footer-col-title">Sản phẩm</div>
    <ul class="footer-links">
      <li><a href="#">Arabica Single Origin</a></li>
      <li><a href="#">Espresso Blend</a></li>
      <li><a href="#">Natural Process</a></li>
      <li><a href="#">Hộp quà tặng</a></li>
    </ul>
  </div>
  <div>
    <div class="footer-col-title">Công ty</div>
    <ul class="footer-links">
      <li><a href="#">Về chúng tôi</a></li>
      <li><a href="#">Nông trại</a></li>
      <li><a href="#">Bền vững</a></li>
      <li><a href="#">Liên hệ</a></li>
    </ul>
  </div>
  <div>
    <div class="footer-col-title">Theo dõi</div>
    <ul class="footer-links">
      <li><a href="#">Instagram</a></li>
      <li><a href="#">Facebook</a></li>
      <li><a href="#">YouTube</a></li>
      <li><a href="#">TikTok</a></li>
    </ul>
  </div>
</footer>
<div class="footer-bottom">
  <span class="footer-copy">© 2024 Cao Nguyên Coffee. Mọi quyền được bảo lưu.</span>
  <span class="footer-copy">Buôn Ma Thuột · Đắk Lắk · Việt Nam</span>
</div>

<script>
// Custom cursor
const cursor = document.getElementById('cursor');
const ring = document.getElementById('cursor-ring');
let mx = 0, my = 0, rx = 0, ry = 0;

document.addEventListener('mousemove', e => {
  mx = e.clientX; my = e.clientY;
  cursor.style.transform = `translate(${mx - 5}px, ${my - 5}px)`;
});

function animateRing() {
  rx += (mx - rx - 18) * 0.12;
  ry += (my - ry - 18) * 0.12;
  ring.style.transform = `translate(${rx}px, ${ry}px)`;
  requestAnimationFrame(animateRing);
}
animateRing();

document.querySelectorAll('a, button').forEach(el => {
  el.addEventListener('mouseenter', () => {
    cursor.style.transform += ' scale(2)';
    ring.style.width = '56px';
    ring.style.height = '56px';
    ring.style.opacity = '0.3';
  });
  el.addEventListener('mouseleave', () => {
    ring.style.width = '36px';
    ring.style.height = '36px';
    ring.style.opacity = '0.5';
  });
});

// Scroll reveal
const reveals = document.querySelectorAll('.reveal');
const observer = new (entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      observer.unobserve(e.target);
    }
  });
}, { threshold: 0.12 });
reveals.forEach(el => observer.observe(el));
</script>
</body>
</html>
