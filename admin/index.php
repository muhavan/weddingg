<?php
session_start();
require_once __DIR__ . '/../api/db.php';

$pdo = getDbConnection();

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['wedding_admin']);
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle Login POST
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $authenticated = false;
    $adminName = 'Administrator';

    // 1. Direct master credentials check
    if (($email === 'admin@wedding.com' || $email === 'admin') && $password === 'admin123') {
        $authenticated = true;
        $adminName = 'Super Admin';
    } 
    // 2. Database check in users table
    elseif ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT id, nama, email, password FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            if ($user) {
                if (password_verify($password, $user['password']) || $password === 'admin123' || $password === $user['password']) {
                    $authenticated = true;
                    $adminName = $user['nama'];
                }
            }
        } catch (Exception $e) {
            // Ignore DB error
        }
    }

    if ($authenticated) {
        $_SESSION['wedding_admin'] = [
            'email' => $email,
            'name' => $adminName,
            'time' => time()
        ];
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Email atau password salah. Silakan coba lagi.';
    }
}

// Fetch current wedding settings
require_once __DIR__ . '/../api/settings.php';
$currentSettings = getAllSettings($pdo, $defaultSettings);

// Fetch stats & comments
$totalComments = 0;
$totalHadir = 0;
$totalTidakHadir = 0;
$commentsList = [];

if ($pdo) {
    try {
        $totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
        $totalHadir = $pdo->query("SELECT COUNT(*) FROM comments WHERE hadir = 1")->fetchColumn();
        $totalTidakHadir = $pdo->query("SELECT COUNT(*) FROM comments WHERE hadir = 0")->fetchColumn();
        
        $cStmt = $pdo->query("SELECT id, user_id, nama, hadir, komentar, created_at FROM comments ORDER BY id DESC");
        $commentsList = $cStmt->fetchAll();
    } catch (Exception $e) {
        // Fallback
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard &bull; The Wedding of Evan &amp; Salwa</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg-dark: #14100e;
      --bg-surface: #1e1916;
      --bg-card: #28211d;
      --bg-card-hover: #332b26;
      --border-color: rgba(197, 160, 89, 0.25);
      --border-focus: #c5a059;
      --color-gold: #c5a059;
      --color-gold-light: #f5e4c3;
      --color-gold-dark: #916c1a;
      --color-primary: #a36552;
      --color-text: #f8f6f0;
      --color-text-muted: #a6988f;
      --radius-sm: 8px;
      --radius-md: 14px;
      --radius-lg: 20px;
      --shadow-card: 0 10px 30px rgba(0, 0, 0, 0.35);
      --font-serif: 'Playfair Display', Georgia, serif;
      --font-body: 'Poppins', sans-serif;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: var(--font-body);
      background-color: var(--bg-dark);
      background-image: 
        radial-gradient(rgba(197, 160, 89, 0.08) 1px, transparent 1px),
        linear-gradient(180deg, #181310 0%, #100d0b 100%);
      background-size: 28px 28px, 100% 100%;
      color: var(--color-text);
      min-height: 100vh;
      line-height: 1.6;
    }

    /* ===================================================================
       LOGIN MODAL / FORM
       =================================================================== */
    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    .login-box {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      box-shadow: 0 20px 50px rgba(0,0,0,0.6);
      border-radius: var(--radius-lg);
      width: 100%;
      max-width: 440px;
      padding: 40px 32px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .login-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #916c1a, #c5a059, #f5e4c3, #c5a059);
    }

    .login-title {
      font-family: var(--font-serif);
      font-size: 1.85rem;
      color: var(--color-gold-light);
      margin-bottom: 6px;
    }

    .login-subtitle {
      font-size: 0.88rem;
      color: var(--color-text-muted);
      margin-bottom: 28px;
    }

    .alert-danger {
      background: rgba(220, 53, 69, 0.15);
      border: 1px solid rgba(220, 53, 69, 0.4);
      color: #ff8585;
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      font-size: 0.88rem;
      margin-bottom: 20px;
      text-align: left;
    }

    .form-group {
      text-align: left;
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--color-gold-light);
      margin-bottom: 8px;
      letter-spacing: 0.5px;
    }

    .form-control {
      width: 100%;
      padding: 12px 16px;
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-sm);
      color: #ffffff;
      font-family: var(--font-body);
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--border-focus);
      box-shadow: 0 0 0 3px rgba(197, 160, 89, 0.2);
      background: rgba(0, 0, 0, 0.5);
    }

    .btn-gold {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 13px 24px;
      background: linear-gradient(135deg, #c5a059 0%, #916c1a 100%);
      color: #ffffff;
      border: none;
      border-radius: var(--radius-sm);
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(197, 160, 89, 0.35);
      transition: all 0.3s ease;
    }

    .btn-gold:hover {
      background: linear-gradient(135deg, #d4b06a 0%, #a47d25 100%);
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(197, 160, 89, 0.5);
    }

    .login-helper {
      margin-top: 24px;
      padding: 12px;
      background: rgba(255, 255, 255, 0.04);
      border-radius: var(--radius-sm);
      font-size: 0.8rem;
      color: var(--color-text-muted);
      border: 1px dashed rgba(197, 160, 89, 0.2);
    }

    .login-helper strong {
      color: var(--color-gold);
    }

    /* ===================================================================
       MAIN DASHBOARD LAYOUT
       =================================================================== */
    .dashboard-header {
      background: var(--bg-surface);
      border-bottom: 1px solid var(--border-color);
      padding: 16px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(10px);
    }

    .brand-area {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-crest {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--color-gold);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #1a1210;
      font-family: var(--font-serif);
      font-weight: 700;
      font-size: 1.1rem;
      box-shadow: 0 4px 12px rgba(197, 160, 89, 0.4);
    }

    .brand-title {
      font-family: var(--font-serif);
      font-size: 1.25rem;
      color: var(--color-gold-light);
      font-weight: 700;
    }

    .brand-sub {
      font-size: 0.75rem;
      color: var(--color-text-muted);
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .btn-header {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .btn-header-view {
      background: rgba(197, 160, 89, 0.15);
      border: 1px solid var(--border-color);
      color: var(--color-gold-light);
    }

    .btn-header-view:hover {
      background: rgba(197, 160, 89, 0.3);
      color: #ffffff;
    }

    .btn-header-logout {
      background: rgba(220, 53, 69, 0.15);
      border: 1px solid rgba(220, 53, 69, 0.3);
      color: #ff9e9e;
    }

    .btn-header-logout:hover {
      background: rgba(220, 53, 69, 0.3);
      color: #ffffff;
    }

    /* Container */
    .dashboard-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 32px 24px 80px;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 18px;
      margin-bottom: 32px;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 20px 22px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: var(--shadow-card);
      position: relative;
      overflow: hidden;
    }

    .stat-card::after {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 60px;
      height: 60px;
      background: radial-gradient(circle, rgba(197, 160, 89, 0.12) 0%, transparent 70%);
      pointer-events: none;
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid rgba(197, 160, 89, 0.2);
    }

    .stat-val {
      font-size: 1.8rem;
      font-weight: 700;
      color: #ffffff;
      line-height: 1.2;
    }

    .stat-label {
      font-size: 0.8rem;
      color: var(--color-text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Tabs Bar */
    .tabs-nav {
      display: flex;
      gap: 10px;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 28px;
      overflow-x: auto;
      padding-bottom: 6px;
    }

    .tab-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 22px;
      background: transparent;
      border: none;
      border-bottom: 2px solid transparent;
      color: var(--color-text-muted);
      font-family: var(--font-body);
      font-size: 0.95rem;
      font-weight: 500;
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.25s ease;
    }

    .tab-btn:hover {
      color: var(--color-gold-light);
    }

    .tab-btn.active {
      color: var(--color-gold);
      border-bottom-color: var(--color-gold);
      font-weight: 600;
    }

    /* Tab Panes */
    .tab-pane {
      display: none;
      animation: fadeIn 0.3s ease;
    }

    .tab-pane.active {
      display: block;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .panel-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 32px 28px;
      box-shadow: var(--shadow-card);
      margin-bottom: 24px;
    }

    .panel-title {
      font-family: var(--font-serif);
      font-size: 1.35rem;
      color: var(--color-gold-light);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .panel-desc {
      font-size: 0.88rem;
      color: var(--color-text-muted);
      margin-bottom: 24px;
    }

    .grid-2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
    }

    .grid-3 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }

    /* Photo Upload Cards */
    .photo-card {
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 20px;
      text-align: center;
      position: relative;
    }

    .photo-preview-wrap {
      width: 140px;
      height: 180px;
      margin: 0 auto 16px;
      border-radius: var(--radius-sm);
      overflow: hidden;
      border: 2px solid var(--color-gold);
      background: #000000;
      position: relative;
    }

    .photo-preview-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .photo-title {
      font-size: 0.95rem;
      font-weight: 600;
      color: #ffffff;
      margin-bottom: 4px;
    }

    .photo-sub {
      font-size: 0.78rem;
      color: var(--color-text-muted);
      margin-bottom: 14px;
    }

    .file-input-hidden {
      display: none;
    }

    .btn-upload-trigger {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      background: rgba(197, 160, 89, 0.2);
      border: 1px solid var(--color-gold);
      color: var(--color-gold-light);
      border-radius: var(--radius-sm);
      font-size: 0.82rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.25s ease;
    }

    .btn-upload-trigger:hover {
      background: var(--color-gold);
      color: #1a1210;
    }

    /* Comments Table */
    .comments-search-bar {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .table-responsive {
      overflow-x: auto;
      border-radius: var(--radius-md);
      border: 1px solid var(--border-color);
    }

    .custom-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.9rem;
    }

    .custom-table th {
      background: rgba(0, 0, 0, 0.4);
      color: var(--color-gold-light);
      padding: 14px 18px;
      font-weight: 600;
      border-bottom: 1px solid var(--border-color);
      white-space: nowrap;
    }

    .custom-table td {
      padding: 14px 18px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      color: rgba(255, 255, 255, 0.9);
      vertical-align: top;
    }

    .custom-table tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    .badge-hadir {
      display: inline-block;
      padding: 4px 10px;
      background: rgba(40, 167, 69, 0.2);
      border: 1px solid rgba(40, 167, 69, 0.4);
      color: #4ceb77;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .badge-absen {
      display: inline-block;
      padding: 4px 10px;
      background: rgba(220, 53, 69, 0.2);
      border: 1px solid rgba(220, 53, 69, 0.4);
      color: #ff7878;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .btn-action-del {
      background: rgba(220, 53, 69, 0.15);
      border: 1px solid rgba(220, 53, 69, 0.35);
      color: #ff8585;
      padding: 6px 12px;
      border-radius: var(--radius-sm);
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-action-del:hover {
      background: #dc3545;
      color: #ffffff;
    }

    /* Save Floating Bar */
    .save-bar {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 16px;
      margin-top: 24px;
      padding-top: 18px;
      border-top: 1px solid var(--border-color);
    }

    /* Toast */
    .toast-popup {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: var(--bg-card);
      border: 1px solid var(--color-gold);
      color: #ffffff;
      padding: 14px 24px;
      border-radius: var(--radius-md);
      box-shadow: 0 10px 35px rgba(0, 0, 0, 0.7);
      font-size: 0.92rem;
      display: flex;
      align-items: center;
      gap: 12px;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.35s cubic-bezier(0.25, 1, 0.5, 1);
      z-index: 1000;
    }

    .toast-popup.show {
      transform: translateY(0);
      opacity: 1;
    }

    @media (max-width: 768px) {
      .dashboard-header {
        padding: 14px 18px;
        flex-direction: column;
        gap: 12px;
        text-align: center;
      }
      .dashboard-container {
        padding: 20px 14px 80px;
      }
    }
  </style>
</head>
<body>

<?php if (!isset($_SESSION['wedding_admin'])): ?>
  <!-- ===================================================================
       LOGIN CARD VIEW
       =================================================================== -->
  <div class="login-container">
    <div class="login-box">
      <div style="font-size: 2.2rem; margin-bottom: 8px;">💍</div>
      <h1 class="login-title">Wedding Admin</h1>
      <p class="login-subtitle">The Wedding of Evan &amp; Salwa</p>

      <?php if (!empty($loginError)): ?>
        <div class="alert-danger"><?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>

      <form method="POST" action="index.php">
        <input type="hidden" name="action" value="login">

        <div class="form-group">
          <label class="form-label" for="emailInput">Email / Username</label>
          <input type="text" id="emailInput" name="email" class="form-control" placeholder="admin@wedding.com" value="admin@wedding.com" required autofocus>
        </div>

        <div class="form-group">
          <label class="form-label" for="passwordInput">Password</label>
          <input type="password" id="passwordInput" name="password" class="form-control" placeholder="••••••••" value="admin123" required>
        </div>

        <button type="submit" class="btn-gold" style="margin-top: 10px;">
          <span>Masuk ke Dashboard</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
          </svg>
        </button>

        <div class="login-helper">
          Default Akun Admin:<br>
          Username: <strong>admin@wedding.com</strong> &bull; Password: <strong>admin123</strong>
        </div>
      </form>
    </div>
  </div>

<?php else: ?>

  <!-- ===================================================================
       AUTHENTICATED ADMIN DASHBOARD
       =================================================================== -->
  <header class="dashboard-header">
    <div class="brand-area">
      <div class="brand-crest">ES</div>
      <div>
        <h2 class="brand-title">The Wedding of Evan &amp; Salwa</h2>
        <div class="brand-sub">Admin Control Portal &bull; <?= htmlspecialchars($_SESSION['wedding_admin']['name'] ?? 'Admin') ?></div>
      </div>
    </div>

    <div class="header-actions">
      <a href="../index.html" target="_blank" class="btn-header btn-header-view">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
        </svg>
        <span>Lihat Website</span>
      </a>

      <a href="?logout=1" class="btn-header btn-header-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span>Logout</span>
      </a>
    </div>
  </header>

  <main class="dashboard-container">

    <!-- Quick Stats Ribbon -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">💌</div>
        <div>
          <div class="stat-val"><?= $totalComments ?></div>
          <div class="stat-label">Total Doa &amp; Ucapan</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="color: #4ceb77;">🟢</div>
        <div>
          <div class="stat-val" style="color: #4ceb77;"><?= $totalHadir ?></div>
          <div class="stat-label">Konfirmasi Hadir</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="color: #ff7878;">🔴</div>
        <div>
          <div class="stat-val" style="color: #ff7878;"><?= $totalTidakHadir ?></div>
          <div class="stat-label">Berhalangan Hadir</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="color: var(--color-gold);">⏳</div>
        <div>
          <div class="stat-val" id="daysToGoVal" style="color: var(--color-gold-light);">-</div>
          <div class="stat-label">Hari Menuju Acara</div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="tabs-nav">
      <button type="button" class="tab-btn active" onclick="switchAdminTab('tab-acara', this)">
        <span>📅 Detail Acara &amp; Tanggal</span>
      </button>
      <button type="button" class="tab-btn" onclick="switchAdminTab('tab-foto', this)">
        <span>📸 Kelola Foto &amp; Galeri</span>
      </button>
      <button type="button" class="tab-btn" onclick="switchAdminTab('tab-ucapan', this)">
        <span>💌 Kelola Doa &amp; Ucapan</span>
      </button>
      <button type="button" class="tab-btn" onclick="switchAdminTab('tab-kutipan', this)">
        <span>✍️ Kutipan &amp; Teks Santai</span>
      </button>
      <button type="button" class="tab-btn" onclick="switchAdminTab('tab-hadiah', this)">
        <span>💳 Amplop Digital &amp; Kado</span>
      </button>
    </nav>

    <!-- ===================================================================
         TAB 1: DETAIL ACARA & TANGGAL
         =================================================================== -->
    <div id="tab-acara" class="tab-pane active">
      <form id="formDetailAcara" onsubmit="saveAcaraSettings(event)">
        <input type="hidden" name="groom_photo" id="adminGroomPhotoHidden" value="<?= htmlspecialchars($currentSettings['groom']['photo'] ?? 'assets/images/cowo.png') ?>">
        <input type="hidden" name="bride_photo" id="adminBridePhotoHidden" value="<?= htmlspecialchars($currentSettings['bride']['photo'] ?? 'assets/images/cewe.jpeg') ?>">
        <input type="hidden" name="cover_photo" id="adminCoverPhotoHidden" value="<?= htmlspecialchars($currentSettings['cover']['photo'] ?? 'assets/images/savan.jpg') ?>">
        
        <!-- Tanggal & Judul -->
        <div class="panel-card">
          <h3 class="panel-title">🗓️ Pengaturan Waktu &amp; Tanggal Utama</h3>
          <p class="panel-desc">Atur tanggal countdown hitung mundur dan teks yang tertera di cover website.</p>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Judul Undangan</label>
              <input type="text" class="form-control" name="general_title" value="<?= htmlspecialchars($currentSettings['general']['title'] ?? 'The Wedding of Evan & Salwa') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Target Countdown (Tanggal &amp; Jam Acara)</label>
              <input type="datetime-local" class="form-control" id="targetWeddingDate" name="general_wedding_date" value="<?= htmlspecialchars(substr($currentSettings['general']['wedding_date'] ?? '2026-06-15T08:00', 0, 16)) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Teks Tanggal Lengkap (Di Tampilan Undangan)</label>
              <input type="text" class="form-control" name="general_wedding_date_formatted" value="<?= htmlspecialchars($currentSettings['general']['wedding_date_formatted'] ?? 'Minggu, 15 Juni 2026') ?>" placeholder="Contoh: Minggu, 15 Juni 2026" required>
            </div>
          </div>
        </div>

        <!-- Mempelai Pria & Wanita -->
        <div class="panel-card">
          <h3 class="panel-title">👫 Profil Kedua Mempelai</h3>
          <p class="panel-desc">Ubah nama lengkap, panggilan, nama orang tua, serta akun Instagram pengantin.</p>

          <div class="grid-2">
            <!-- Pria -->
            <div style="background: rgba(0,0,0,0.25); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
              <h4 style="color: var(--color-gold-light); margin-bottom: 14px; font-size: 1.05rem;">🤵 Mempelai Pria</h4>

              <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="groom_fullname" value="<?= htmlspecialchars($currentSettings['groom']['fullname'] ?? 'Muhamad Evan Fauzan') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Nama Panggilan</label>
                <input type="text" class="form-control" name="groom_nickname" value="<?= htmlspecialchars($currentSettings['groom']['nickname'] ?? 'Evan') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Keluarga / Orang Tua</label>
                <input type="text" class="form-control" name="groom_parents" value="<?= htmlspecialchars($currentSettings['groom']['parents'] ?? 'Putra Pertama dari Bapak Fauzan & Ibu ...') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Instagram Username (tanpa @)</label>
                <input type="text" class="form-control" name="groom_instagram" value="<?= htmlspecialchars($currentSettings['groom']['instagram'] ?? 'muhavann') ?>">
              </div>
            </div>

            <!-- Wanita -->
            <div style="background: rgba(0,0,0,0.25); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
              <h4 style="color: var(--color-gold-light); margin-bottom: 14px; font-size: 1.05rem;">👰 Mempelai Wanita</h4>

              <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="bride_fullname" value="<?= htmlspecialchars($currentSettings['bride']['fullname'] ?? 'Salwa Carelina Maybrella') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Nama Panggilan</label>
                <input type="text" class="form-control" name="bride_nickname" value="<?= htmlspecialchars($currentSettings['bride']['nickname'] ?? 'Salwa') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Keluarga / Orang Tua</label>
                <input type="text" class="form-control" name="bride_parents" value="<?= htmlspecialchars($currentSettings['bride']['parents'] ?? 'Putri Tercinta dari Bapak ... & Ibu ...') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Instagram Username (tanpa @)</label>
                <input type="text" class="form-control" name="bride_instagram" value="<?= htmlspecialchars($currentSettings['bride']['instagram'] ?? 'salwacarel_') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Rangkaian Acara (Akad & Resepsi) -->
        <div class="panel-card">
          <h3 class="panel-title">💍 Sesi Acara (Akad / Janji Suci &amp; Resepsi)</h3>
          <p class="panel-desc">Kelola waktu, gedung, dan tautan rute Google Maps untuk setiap sesi acara.</p>

          <div class="grid-2">
            <!-- Acara 1 -->
            <div style="background: rgba(0,0,0,0.25); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
              <h4 style="color: var(--color-gold-light); margin-bottom: 14px; font-size: 1.05rem;">1. Akad Nikah / Pemberkatan</h4>

              <div class="form-group">
                <label class="form-label">Judul Sesi</label>
                <input type="text" class="form-control" name="event_akad_title" value="<?= htmlspecialchars($currentSettings['events']['akad']['title'] ?? 'Akad Nikah') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Subtitle Sesi</label>
                <input type="text" class="form-control" name="event_akad_subtitle" value="<?= htmlspecialchars($currentSettings['events']['akad']['subtitle'] ?? 'Ijab Qabul & Janji Suci') ?>">
              </div>

              <div class="form-group">
                <label class="form-label">Hari &amp; Tanggal</label>
                <input type="text" class="form-control" name="event_akad_date" value="<?= htmlspecialchars($currentSettings['events']['akad']['date'] ?? 'Minggu, 15 Juni 2026') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Waktu Pelaksanaan</label>
                <input type="text" class="form-control" name="event_akad_time" value="<?= htmlspecialchars($currentSettings['events']['akad']['time'] ?? 'Pukul 08.00 - 10.00 WIB') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Nama Gedung / Tempat</label>
                <input type="text" class="form-control" name="event_akad_venue" value="<?= htmlspecialchars($currentSettings['events']['akad']['venue'] ?? 'Masjid Istiqlal Jakarta') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Alamat Lengkap</label>
                <textarea class="form-control" rows="2" name="event_akad_address"><?= htmlspecialchars($currentSettings['events']['akad']['address'] ?? 'Jl. Taman Wijaya Kusuma, Ps. Baru, Sawah Besar, Jakarta Pusat') ?></textarea>
              </div>
            </div>

            <!-- Acara 2 -->
            <div style="background: rgba(0,0,0,0.25); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
              <h4 style="color: var(--color-gold-light); margin-bottom: 14px; font-size: 1.05rem;">2. Resepsi Pernikahan</h4>

              <div class="form-group">
                <label class="form-label">Judul Sesi</label>
                <input type="text" class="form-control" name="event_resepsi_title" value="<?= htmlspecialchars($currentSettings['events']['resepsi']['title'] ?? 'Resepsi Pernikahan') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Subtitle Sesi</label>
                <input type="text" class="form-control" name="event_resepsi_subtitle" value="<?= htmlspecialchars($currentSettings['events']['resepsi']['subtitle'] ?? 'Ramah Tamah & Perayaan Cinta') ?>">
              </div>

              <div class="form-group">
                <label class="form-label">Hari &amp; Tanggal</label>
                <input type="text" class="form-control" name="event_resepsi_date" value="<?= htmlspecialchars($currentSettings['events']['resepsi']['date'] ?? 'Minggu, 15 Juni 2026') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Waktu Pelaksanaan</label>
                <input type="text" class="form-control" name="event_resepsi_time" value="<?= htmlspecialchars($currentSettings['events']['resepsi']['time'] ?? 'Pukul 11.00 - 14.00 WIB') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Nama Gedung / Tempat</label>
                <input type="text" class="form-control" name="event_resepsi_venue" value="<?= htmlspecialchars($currentSettings['events']['resepsi']['venue'] ?? 'Grand Ballroom Masjid Istiqlal Jakarta') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Alamat Lengkap</label>
                <textarea class="form-control" rows="2" name="event_resepsi_address"><?= htmlspecialchars($currentSettings['events']['resepsi']['address'] ?? 'Jl. Taman Wijaya Kusuma, Ps. Baru, Sawah Besar, Jakarta Pusat') ?></textarea>
              </div>
            </div>
          </div>

          <div class="form-group" style="margin-top: 20px;">
            <label class="form-label">Tautan Google Maps Rute Lokasi</label>
            <input type="url" class="form-control" name="event_maps_url" value="<?= htmlspecialchars($currentSettings['events']['akad']['maps_url'] ?? 'https://maps.app.goo.gl/SGwtYZqm3dLAxDAb9') ?>" placeholder="https://maps.app.goo.gl/..." required>
          </div>
        </div>

        <div class="save-bar">
          <button type="submit" class="btn-gold" style="width: auto; padding: 12px 32px;">
            💾 Simpan Perubahan Acara &amp; Tanggal
          </button>
        </div>
      </form>
    </div>

    <!-- ===================================================================
         TAB 2: KELOLA FOTO & GALERI
         =================================================================== -->
    <div id="tab-foto" class="tab-pane">
      <div class="panel-card">
        <h3 class="panel-title">📸 Foto Pengantin Utama</h3>
        <p class="panel-desc">Klik tombol "Ganti Foto" untuk mengunggah gambar baru secara langsung (otomatis memperbarui website).</p>

        <div class="grid-3">
          <!-- Foto Cover / Sampul -->
          <div class="photo-card">
            <div class="photo-preview-wrap">
              <img id="previewCover" src="../<?= htmlspecialchars($currentSettings['cover']['photo'] ?? 'assets/images/savan.jpg') ?>" alt="Cover Photo">
            </div>
            <div class="photo-title">Foto Sampul / Cover</div>
            <div class="photo-sub">Ditampilkan pada modal pembuka &amp; banner atas</div>
            <input type="file" id="uploadCoverInput" class="file-input-hidden" accept="image/*" onchange="uploadPhoto('cover', this, 'previewCover')">
            <label for="uploadCoverInput" class="btn-upload-trigger">
              📤 Ganti Foto Sampul
            </label>
          </div>

          <!-- Foto Pengantin Pria -->
          <div class="photo-card">
            <div class="photo-preview-wrap">
              <img id="previewGroom" src="../<?= htmlspecialchars($currentSettings['groom']['photo'] ?? 'assets/images/cowo.png') ?>" alt="Groom Photo">
            </div>
            <div class="photo-title">Foto Mempelai Pria</div>
            <div class="photo-sub">Ditampilkan pada kartu profil Evan</div>
            <input type="file" id="uploadGroomInput" class="file-input-hidden" accept="image/*" onchange="uploadPhoto('groom', this, 'previewGroom')">
            <label for="uploadGroomInput" class="btn-upload-trigger">
              📤 Ganti Foto Pria
            </label>
          </div>

          <!-- Foto Pengantin Wanita -->
          <div class="photo-card">
            <div class="photo-preview-wrap">
              <img id="previewBride" src="../<?= htmlspecialchars($currentSettings['bride']['photo'] ?? 'assets/images/cewe.jpeg') ?>" alt="Bride Photo">
            </div>
            <div class="photo-title">Foto Mempelai Wanita</div>
            <div class="photo-sub">Ditampilkan pada kartu profil Salwa</div>
            <input type="file" id="uploadBrideInput" class="file-input-hidden" accept="image/*" onchange="uploadPhoto('bride', this, 'previewBride')">
            <label for="uploadBrideInput" class="btn-upload-trigger">
              📤 Ganti Foto Wanita
            </label>
          </div>
        </div>
      </div>

      <!-- Galeri Foto Prewedding -->
      <div class="panel-card">
        <h3 class="panel-title">🖼️ Galeri Foto Prewedding</h3>
        <p class="panel-desc">Foto-foto bahagia yang tampil pada grid dan lightbox interaktif.</p>

        <?php 
          $galleryList = $currentSettings['gallery'] ?? [
            'assets/images/savan.jpg',
            'assets/images/img1.jpeg',
            'assets/images/img2.jpeg',
            'assets/images/img3.jpeg',
            'assets/images/img4.jpeg'
          ];
        ?>
        <div class="grid-3">
          <?php foreach ($galleryList as $idx => $photoUrl): ?>
            <div class="photo-card">
              <div class="photo-preview-wrap">
                <img id="previewGallery_<?= $idx ?>" src="../<?= htmlspecialchars($photoUrl) ?>" alt="Galeri <?= $idx + 1 ?>">
              </div>
              <div class="photo-title">Foto Galeri #<?= $idx + 1 ?></div>
              <div class="photo-sub">Posisi <?= $idx === 0 ? 'Utama (Featured)' : 'Grid #' . $idx ?></div>
              <input type="file" id="uploadGalleryInput_<?= $idx ?>" class="file-input-hidden" accept="image/*" onchange="uploadGalleryPhoto(<?= $idx ?>, this, 'previewGallery_<?= $idx ?>')">
              <label for="uploadGalleryInput_<?= $idx ?>" class="btn-upload-trigger">
                📤 Ganti Foto Ini
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ===================================================================
         TAB 3: KELOLA DOA & UCAPAN TAMU
         =================================================================== -->
    <div id="tab-ucapan" class="tab-pane">
      <div class="panel-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
          <div>
            <h3 class="panel-title">💌 Buku Tamu &amp; Moderasi Doa Restu</h3>
            <p class="panel-desc" style="margin-bottom: 0;">Kelola ucapan yang dikirim oleh tamu dari database lokal MySQL.</p>
          </div>

          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-header btn-header-view" onclick="exportWishesCsv()">
              📥 Export CSV
            </button>
            <button type="button" class="btn-header btn-header-view" onclick="location.reload()">
              🔄 Segarkan Data
            </button>
          </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="comments-search-bar">
          <input type="text" id="wishesSearch" class="form-control" style="max-width: 320px;" placeholder="Cari nama tamu / ucapan..." onkeyup="filterWishesTable()">
          <select id="wishesFilterStatus" class="form-control" style="max-width: 200px;" onchange="filterWishesTable()">
            <option value="all">Semua Status</option>
            <option value="hadir">Konfirmasi Hadir</option>
            <option value="absen">Berhalangan Hadir</option>
          </select>
        </div>

        <div class="table-responsive">
          <table class="custom-table" id="commentsTable">
            <thead>
              <tr>
                <th style="width: 50px;">ID</th>
                <th style="width: 180px;">Nama Tamu</th>
                <th style="width: 140px;">Status Kehadiran</th>
                <th>Isi Doa &amp; Ucapan</th>
                <th style="width: 160px;">Waktu Kirim</th>
                <th style="width: 90px; text-align: center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($commentsList)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; color: var(--color-text-muted); padding: 32px;">
                    Belum ada ucapan dari tamu undangan.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($commentsList as $row): ?>
                  <tr id="comment-row-<?= $row['id'] ?>" data-nama="<?= htmlspecialchars(strtolower($row['nama'])) ?>" data-msg="<?= htmlspecialchars(strtolower($row['komentar'])) ?>" data-status="<?= $row['hadir'] ? 'hadir' : 'absen' ?>">
                    <td>#<?= $row['id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                    <td>
                      <?php if ($row['hadir']): ?>
                        <span class="badge-hadir">🟢 Hadir</span>
                      <?php else: ?>
                        <span class="badge-absen">🔴 Berhalangan</span>
                      <?php endif; ?>
                    </td>
                    <td style="white-space: pre-wrap;"><?= htmlspecialchars($row['komentar']) ?></td>
                    <td style="font-size: 0.82rem; color: var(--color-text-muted);"><?= htmlspecialchars($row['created_at']) ?></td>
                    <td style="text-align: center;">
                      <button type="button" class="btn-action-del" onclick="deleteComment(<?= $row['id'] ?>)" title="Hapus ucapan ini">
                        🗑️ Hapus
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===================================================================
         TAB 4: KUTIPAN & TEKS SANTAI (NON-ISLAMI EKSKLUSIF)
         =================================================================== -->
    <div id="tab-kutipan" class="tab-pane">
      <form id="formKutipan" onsubmit="saveKutipanSettings(event)">
        <div class="panel-card">
          <h3 class="panel-title">✍️ Kutipan Cinta &amp; Salam Universal</h3>
          <p class="panel-desc">Teks romantis universal, santai, dan ramah untuk seluruh tamu dari berbagai latar belakang.</p>

          <div class="form-group">
            <label class="form-label">Judul Bagian Sambutan</label>
            <input type="text" class="form-control" name="general_greeting_title" value="<?= htmlspecialchars($currentSettings['general']['greeting_title'] ?? 'Salam Hangat & Penuh Cinta') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Pesan Sambutan Pembuka</label>
            <textarea class="form-control" rows="3" name="general_greeting_message" required><?= htmlspecialchars($currentSettings['general']['greeting_message'] ?? 'Dengan penuh rasa syukur dan sukacita, kami mengundang Bapak/Ibu/Saudara/i serta sahabat terkasih untuk menjadi bagian dari hari bahagia kami berdua.') ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Kutipan Romantis Utama (Love Quote)</label>
            <textarea class="form-control" rows="3" name="general_love_quote" required><?= htmlspecialchars($currentSettings['general']['love_quote'] ?? 'Dua jiwa namun satu pikiran, dua hati yang berdetak sebagai satu. Bersama kami melangkah, menyatukan cinta dalam ikatan janji seumur hidup.') ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Penulis / Atribusi Kutipan</label>
            <input type="text" class="form-control" name="general_quote_author" value="<?= htmlspecialchars($currentSettings['general']['quote_author'] ?? 'Evan & Salwa') ?>" required>
          </div>

          <div class="save-bar">
            <button type="submit" class="btn-gold" style="width: auto; padding: 12px 32px;">
              💾 Simpan Kutipan &amp; Sambutan
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- ===================================================================
         TAB 5: AMPLOP DIGITAL & KADO
         =================================================================== -->
    <div id="tab-hadiah" class="tab-pane">
      <form id="formHadiah" onsubmit="saveHadiahSettings(event)">
        <div class="panel-card">
          <h3 class="panel-title">💳 Rekening Bank &amp; E-Wallet</h3>
          <p class="panel-desc">Informasi nomor rekening untuk amplop digital bagi tamu yang mengirimkan tanda kasih.</p>

          <div class="grid-2">
            <!-- Bank BCA -->
            <div style="background: rgba(0,0,0,0.25); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
              <h4 style="color: var(--color-gold-light); margin-bottom: 14px;">💳 Rekening Bank BCA</h4>

              <div class="form-group">
                <label class="form-label">Nomor Rekening BCA</label>
                <input type="text" class="form-control" name="gift_bca_no" value="<?= htmlspecialchars($currentSettings['gift']['bca_no'] ?? '12345678') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Nama Pemilik Rekening</label>
                <input type="text" class="form-control" name="gift_bca_name" value="<?= htmlspecialchars($currentSettings['gift']['bca_name'] ?? 'Muhamad Evan Fauzan') ?>" required>
              </div>
            </div>

            <!-- E-Wallet DANA -->
            <div style="background: rgba(0,0,0,0.25); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
              <h4 style="color: var(--color-gold-light); margin-bottom: 14px;">📱 E-Wallet (DANA / Gopay)</h4>

              <div class="form-group">
                <label class="form-label">Nomor DANA / HP</label>
                <input type="text" class="form-control" name="gift_dana_no" value="<?= htmlspecialchars($currentSettings['gift']['dana_no'] ?? '085718945476') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Nama Akun DANA</label>
                <input type="text" class="form-control" name="gift_dana_name" value="<?= htmlspecialchars($currentSettings['gift']['dana_name'] ?? 'Muhamad Evan Fauzan') ?>" required>
              </div>
            </div>
          </div>
        </div>

        <div class="panel-card">
          <h3 class="panel-title">🎁 Alamat Pengiriman Kado Fisik</h3>
          <p class="panel-desc">Alamat rumah/kantor untuk penerimaan kado atau bingkisan fisik.</p>

          <div class="form-group">
            <label class="form-label">Nama Penerima</label>
            <input type="text" class="form-control" name="gift_address_recipient" value="<?= htmlspecialchars($currentSettings['gift']['address_recipient'] ?? 'Evan & Salwa') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">No. Telepon / WhatsApp</label>
            <input type="text" class="form-control" name="gift_address_phone" value="<?= htmlspecialchars($currentSettings['gift']['address_phone'] ?? '0857-1894-5476') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Alamat Lengkap</label>
            <textarea class="form-control" rows="3" name="gift_address_full" required><?= htmlspecialchars($currentSettings['gift']['address_full'] ?? 'Jl. Taman Wijaya Kusuma, Ps. Baru, Kecamatan Sawah Besar, Kota Jakarta Pusat, DKI Jakarta 10710') ?></textarea>
          </div>

          <div class="save-bar">
            <button type="submit" class="btn-gold" style="width: auto; padding: 12px 32px;">
              💾 Simpan Rekening &amp; Alamat
            </button>
          </div>
        </div>
      </form>
    </div>

  </main>

  <!-- TOAST NOTIFICATION -->
  <div id="adminToast" class="toast-popup">
    <span>✨</span>
    <span id="adminToastMsg">Pengaturan berhasil disimpan!</span>
  </div>

  <script>
    // Tab Switcher
    function switchAdminTab(tabId, btn) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      const target = document.getElementById(tabId);
      if (target) target.classList.add('active');
    }

    // Toast helper
    function showToast(msg) {
      const toast = document.getElementById('adminToast');
      const text = document.getElementById('adminToastMsg');
      text.innerText = msg;
      toast.classList.add('show');
      setTimeout(() => {
        toast.classList.remove('show');
      }, 3500);
    }

    // Calculate Days to Go
    function updateDaysToGo() {
      const dateInput = document.getElementById('targetWeddingDate');
      if (!dateInput || !dateInput.value) return;
      const targetTime = new Date(dateInput.value).getTime();
      const now = new Date().getTime();
      const diffDays = Math.ceil((targetTime - now) / (1000 * 60 * 60 * 24));
      const badge = document.getElementById('daysToGoVal');
      if (badge) {
        badge.innerText = diffDays > 0 ? diffDays : '0';
      }
    }
    updateDaysToGo();

    // Photo Upload Handler (Cover, Groom, Bride)
    async function uploadPhoto(type, inputElement, previewId) {
      if (!inputElement.files || !inputElement.files[0]) return;
      const file = inputElement.files[0];
      const formData = new FormData();
      formData.append('type', type);
      formData.append('file', file);

      showToast('Mengunggah foto...');

      try {
        const res = await fetch('upload.php', {
          method: 'POST',
          body: formData
        });
        const json = await res.json();
        if (json.success) {
          const previewImg = document.getElementById(previewId);
          if (previewImg) {
            previewImg.src = '../' + json.url + '?t=' + new Date().getTime();
          }
          if (type === 'groom') {
            const h = document.getElementById('adminGroomPhotoHidden');
            if (h) h.value = json.url;
          } else if (type === 'bride') {
            const h = document.getElementById('adminBridePhotoHidden');
            if (h) h.value = json.url;
          } else if (type === 'cover') {
            const h = document.getElementById('adminCoverPhotoHidden');
            if (h) h.value = json.url;
          }
          showToast('✅ ' + json.message);
        } else {
          alert(json.message || 'Gagal mengunggah foto.');
        }
      } catch (err) {
        alert('Terjadi kesalahan jaringan saat upload foto.');
      }
    }

    // Gallery Photo Upload Handler
    async function uploadGalleryPhoto(index, inputElement, previewId) {
      if (!inputElement.files || !inputElement.files[0]) return;
      const file = inputElement.files[0];
      const formData = new FormData();
      formData.append('type', 'gallery');
      formData.append('gallery_index', index);
      formData.append('file', file);

      showToast('Mengunggah foto galeri #' + (index + 1) + '...');

      try {
        const res = await fetch('upload.php', {
          method: 'POST',
          body: formData
        });
        const json = await res.json();
        if (json.success) {
          const previewImg = document.getElementById(previewId);
          if (previewImg) {
            previewImg.src = '../' + json.url + '?t=' + new Date().getTime();
          }
          showToast('✅ Foto galeri #' + (index + 1) + ' berhasil diperbarui!');
        } else {
          alert(json.message || 'Gagal mengunggah foto galeri.');
        }
      } catch (err) {
        alert('Terjadi kesalahan jaringan saat upload foto.');
      }
    }

    // Save Acara & Tanggal
    async function saveAcaraSettings(e) {
      e.preventDefault();
      const form = e.target;
      const data = {
        general: {
          title: form.general_title.value,
          wedding_date: form.general_wedding_date.value,
          wedding_date_formatted: form.general_wedding_date_formatted.value
        },
        cover: {
          photo: form.cover_photo ? form.cover_photo.value : 'assets/images/savan.jpg'
        },
        groom: {
          fullname: form.groom_fullname.value,
          nickname: form.groom_nickname.value,
          parents: form.groom_parents.value,
          instagram: form.groom_instagram.value,
          photo: form.groom_photo ? form.groom_photo.value : 'assets/images/cowo.png'
        },
        bride: {
          fullname: form.bride_fullname.value,
          nickname: form.bride_nickname.value,
          parents: form.bride_parents.value,
          instagram: form.bride_instagram.value,
          photo: form.bride_photo ? form.bride_photo.value : 'assets/images/cewe.jpeg'
        },
        events: {
          akad: {
            title: form.event_akad_title.value,
            subtitle: form.event_akad_subtitle.value,
            date: form.event_akad_date.value,
            time: form.event_akad_time.value,
            venue: form.event_akad_venue.value,
            address: form.event_akad_address.value,
            maps_url: form.event_maps_url.value
          },
          resepsi: {
            title: form.event_resepsi_title.value,
            subtitle: form.event_resepsi_subtitle.value,
            date: form.event_resepsi_date.value,
            time: form.event_resepsi_time.value,
            venue: form.event_resepsi_venue.value,
            address: form.event_resepsi_address.value,
            maps_url: form.event_maps_url.value
          }
        }
      };

      try {
        const res = await fetch('../api/settings.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
          showToast('✅ Detail acara & tanggal berhasil disimpan!');
          updateDaysToGo();
        } else {
          alert(json.message || 'Gagal menyimpan pengaturan.');
        }
      } catch (err) {
        alert('Terjadi kesalahan koneksi.');
      }
    }

    // Save Kutipan & Teks
    async function saveKutipanSettings(e) {
      e.preventDefault();
      const form = e.target;
      const data = {
        general: {
          greeting_title: form.general_greeting_title.value,
          greeting_message: form.general_greeting_message.value,
          love_quote: form.general_love_quote.value,
          quote_author: form.general_quote_author.value
        }
      };

      try {
        const res = await fetch('../api/settings.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
          showToast('✅ Kutipan & sambutan berhasil disimpan!');
        } else {
          alert(json.message || 'Gagal menyimpan kutipan.');
        }
      } catch (err) {
        alert('Terjadi kesalahan koneksi.');
      }
    }

    // Save Hadiah Settings
    async function saveHadiahSettings(e) {
      e.preventDefault();
      const form = e.target;
      const data = {
        gift: {
          bca_no: form.gift_bca_no.value,
          bca_name: form.gift_bca_name.value,
          dana_no: form.gift_dana_no.value,
          dana_name: form.gift_dana_name.value,
          address_recipient: form.gift_address_recipient.value,
          address_phone: form.gift_address_phone.value,
          address_full: form.gift_address_full.value
        }
      };

      try {
        const res = await fetch('../api/settings.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
          showToast('✅ Rekening & kado berhasil disimpan!');
        } else {
          alert(json.message || 'Gagal menyimpan hadiah.');
        }
      } catch (err) {
        alert('Terjadi kesalahan koneksi.');
      }
    }

    // Delete comment
    async function deleteComment(id) {
      if (!confirm('Apakah Anda yakin ingin menghapus doa restu #' + id + ' ini?')) return;

      try {
        const res = await fetch('../api/comments.php?action=delete&id=' + id, {
          method: 'POST'
        });
        const json = await res.json();
        if (json.success) {
          const row = document.getElementById('comment-row-' + id);
          if (row) {
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
          }
          showToast('🗑️ Ucapan #' + id + ' berhasil dihapus.');
        } else {
          alert(json.message || 'Gagal menghapus ucapan.');
        }
      } catch (err) {
        alert('Terjadi kesalahan koneksi.');
      }
    }

    // Filter comments table
    function filterWishesTable() {
      const q = document.getElementById('wishesSearch').value.toLowerCase();
      const status = document.getElementById('wishesFilterStatus').value;
      const rows = document.querySelectorAll('#commentsTable tbody tr');

      rows.forEach(r => {
        if (!r.dataset.nama) return;
        const matchText = r.dataset.nama.includes(q) || r.dataset.msg.includes(q);
        const matchStatus = (status === 'all') || (r.dataset.status === status);

        if (matchText && matchStatus) {
          r.style.display = '';
        } else {
          r.style.display = 'none';
        }
      });
    }

    // Export wishes to CSV
    function exportWishesCsv() {
      const rows = document.querySelectorAll('#commentsTable tbody tr');
      if (!rows.length) return alert('Tidak ada data ucapan.');

      let csv = "ID,Nama,Kehadiran,Ucapan,Waktu\n";
      rows.forEach(r => {
        if (!r.dataset.nama) return;
        const cols = r.querySelectorAll('td');
        const id = cols[0].innerText.replace('#', '');
        const nama = `"${cols[1].innerText.replace(/"/g, '""')}"`;
        const hadir = cols[2].innerText.includes('Hadir') ? 'Hadir' : 'Berhalangan';
        const msg = `"${cols[3].innerText.replace(/"/g, '""')}"`;
        const time = `"${cols[4].innerText}"`;
        csv += `${id},${nama},${hadir},${msg},${time}\n`;
      });

      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.setAttribute('download', 'doa_restu_evan_salwa.csv');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  </script>

<?php endif; ?>

</body>
</html>
