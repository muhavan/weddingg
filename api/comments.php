<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = '153.92.15.11';
$dbname = 'u883909247_weddingg';
$username = 'u883909247_weddingg';
$password = 'Weddingg@88';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal terhubung ke database: ' . $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, user_id, nama, hadir, komentar, created_at FROM comments ORDER BY id DESC LIMIT 100");
        $comments = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $comments,
            'total' => count($comments)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengambil data ucapan: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($method === 'POST') {
    // Read JSON payload or regular POST body
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!$input) {
        $input = $_POST;
    }

    $nama = isset($input['nama']) ? trim($input['nama']) : '';
    $komentar = isset($input['komentar']) ? trim($input['komentar']) : '';
    $hadir = isset($input['hadir']) ? intval($input['hadir']) : 1;

    if (empty($nama)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Nama lengkap wajib diisi.'
        ]);
        exit;
    }

    if (empty($komentar)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Ucapan dan doa restu wajib diisi.'
        ]);
        exit;
    }

    $uuid = bin2hex(random_bytes(16));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO comments (user_id, nama, hadir, komentar, created_at, updated_at, uuid, ip, user_agent)
            VALUES (1, :nama, :hadir, :komentar, NOW(), NOW(), :uuid, :ip, :user_agent)
        ");

        $stmt->execute([
            ':nama' => $nama,
            ':hadir' => $hadir,
            ':komentar' => $komentar,
            ':uuid' => $uuid,
            ':ip' => $ip,
            ':user_agent' => $userAgent
        ]);

        $newId = $pdo->lastInsertId();

        // Fetch inserted record
        $stmtFetch = $pdo->prepare("SELECT id, user_id, nama, hadir, komentar, created_at FROM comments WHERE id = :id");
        $stmtFetch->execute([':id' => $newId]);
        $newComment = $stmtFetch->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Doa restu dan ucapan Anda berhasil disimpan!',
            'data' => $newComment
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan ucapan ke database: ' . $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak didukung.']);
