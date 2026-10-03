<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['wedding_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

require_once __DIR__ . '/../api/db.php';
$pdo = getDbConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak didukung.']);
    exit;
}

$type = $_POST['type'] ?? 'general'; // cover, groom, bride, gallery

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau terjadi kesalahan saat upload.']);
    exit;
}

$file = $_FILES['file'];
$maxSize = 12 * 1024 * 1024; // 12MB

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (maksimal 12MB).']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
];

if (!isset($allowedMimes[$mime])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF.']);
    exit;
}

$ext = $allowedMimes[$mime];
$targetDir = __DIR__ . '/../assets/images/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Generate appropriate filename based on type
$timestamp = time();
$safeName = $type . '_' . $timestamp . '.' . $ext;
$targetPath = $targetDir . $safeName;
$relativeUrl = 'assets/images/' . $safeName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memindahkan file yang diunggah.']);
    exit;
}

// Update database wedding_settings if pdo available
if ($pdo) {
    try {
        if ($type === 'cover') {
            $stmt = $pdo->prepare("SELECT setting_value FROM wedding_settings WHERE setting_key = 'cover'");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            $cover = $val ? json_decode($val, true) : [];
            $cover['photo'] = $relativeUrl;
            $save = $pdo->prepare("INSERT INTO wedding_settings (setting_key, setting_value, updated_at) VALUES ('cover', :val, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
            $save->execute([':val' => json_encode($cover, JSON_UNESCAPED_UNICODE)]);
        } elseif ($type === 'groom') {
            $stmt = $pdo->prepare("SELECT setting_value FROM wedding_settings WHERE setting_key = 'groom'");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            $groom = $val ? json_decode($val, true) : [];
            $groom['photo'] = $relativeUrl;
            $save = $pdo->prepare("INSERT INTO wedding_settings (setting_key, setting_value, updated_at) VALUES ('groom', :val, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
            $save->execute([':val' => json_encode($groom, JSON_UNESCAPED_UNICODE)]);
        } elseif ($type === 'bride') {
            $stmt = $pdo->prepare("SELECT setting_value FROM wedding_settings WHERE setting_key = 'bride'");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            $bride = $val ? json_decode($val, true) : [];
            $bride['photo'] = $relativeUrl;
            $save = $pdo->prepare("INSERT INTO wedding_settings (setting_key, setting_value, updated_at) VALUES ('bride', :val, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
            $save->execute([':val' => json_encode($bride, JSON_UNESCAPED_UNICODE)]);
        } elseif ($type === 'gallery') {
            $galleryIndex = isset($_POST['gallery_index']) ? intval($_POST['gallery_index']) : -1;
            $stmt = $pdo->prepare("SELECT setting_value FROM wedding_settings WHERE setting_key = 'gallery'");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            $gallery = $val ? json_decode($val, true) : [
                'assets/images/savan.jpg',
                'assets/images/img1.jpeg',
                'assets/images/img2.jpeg',
                'assets/images/img3.jpeg',
                'assets/images/img4.jpeg'
            ];
            if ($galleryIndex >= 0 && $galleryIndex < count($gallery)) {
                $gallery[$galleryIndex] = $relativeUrl;
            } else {
                $gallery[] = $relativeUrl;
            }
            $save = $pdo->prepare("INSERT INTO wedding_settings (setting_key, setting_value, updated_at) VALUES ('gallery', :val, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
            $save->execute([':val' => json_encode($gallery, JSON_UNESCAPED_UNICODE)]);
        }
    } catch (Exception $e) {
        // Keep file, return partial warning if db fails
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Foto berhasil diunggah dan disimpan!',
    'url' => $relativeUrl,
    'type' => $type
]);
