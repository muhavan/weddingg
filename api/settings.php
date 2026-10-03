<?php
require_once __DIR__ . '/db.php';

$pdo = getDbConnection();

$isDirectApi = (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__));

if ($isDirectApi) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

$defaultSettings = [
    'general' => [
        'title' => 'The Wedding of Evan & Salwa',
        'wedding_date' => '2026-06-15T08:00:00',
        'wedding_date_formatted' => 'Minggu, 15 Juni 2026',
        'love_quote' => 'Dua jiwa namun satu pikiran, dua hati yang berdetak sebagai satu. Bersama kami melangkah, menyatukan cinta dalam ikatan janji seumur hidup.',
        'quote_author' => 'Evan & Salwa',
        'greeting_title' => 'Salam Hangat & Penuh Cinta',
        'greeting_message' => 'Dengan penuh rasa syukur dan sukacita, kami mengundang Bapak/Ibu/Saudara/i serta sahabat terkasih untuk menjadi bagian dari hari bahagia kami berdua.'
    ],
    'groom' => [
        'nickname' => 'Evan',
        'fullname' => 'Muhamad Evan Fauzan',
        'parents' => 'Putra Pertama dari Bapak Fauzan & Ibu ...',
        'instagram' => 'muhavann',
        'photo' => 'assets/images/cowo.png'
    ],
    'bride' => [
        'nickname' => 'Salwa',
        'fullname' => 'Salwa Carelina Maybrella',
        'parents' => 'Putri Tercinta dari Bapak ... & Ibu ...',
        'instagram' => 'salwacarel_',
        'photo' => 'assets/images/cewe.jpeg'
    ],
    'cover' => [
        'photo' => 'assets/images/savan.jpg'
    ],
    'events' => [
        'akad' => [
            'title' => 'Akad Nikah',
            'subtitle' => 'Ijab Qabul & Janji Suci',
            'date' => 'Minggu, 15 Juni 2026',
            'time' => '08.00 - 10.00 WIB',
            'venue' => 'Masjid Istiqlal Jakarta',
            'address' => 'Jl. Taman Wijaya Kusuma, Ps. Baru, Sawah Besar, Jakarta Pusat',
            'maps_url' => 'https://maps.app.goo.gl/SGwtYZqm3dLAxDAb9'
        ],
        'resepsi' => [
            'title' => 'Resepsi Pernikahan',
            'subtitle' => 'Ramah Tamah & Perayaan Cinta',
            'date' => 'Minggu, 15 Juni 2026',
            'time' => '11.00 - 14.00 WIB',
            'venue' => 'Grand Ballroom Masjid Istiqlal Jakarta',
            'address' => 'Jl. Taman Wijaya Kusuma, Ps. Baru, Sawah Besar, Jakarta Pusat',
            'maps_url' => 'https://maps.app.goo.gl/SGwtYZqm3dLAxDAb9'
        ],
        'streaming' => [
            'enabled' => true,
            'title' => 'Virtual Wedding Celebration',
            'desc' => 'Bagi keluarga dan sahabat yang berhalangan hadir secara langsung, silakan bergabung secara virtual:',
            'ig_live' => 'https://instagram.com/muhavann',
            'zoom_url' => 'https://zoom.us/join',
            'zoom_id' => '123 456 7890',
            'zoom_pass' => 'EVANSALWA'
        ]
    ],
    'gift' => [
        'bca_no' => '12345678',
        'bca_name' => 'Muhamad Evan Fauzan',
        'dana_no' => '085718945476',
        'dana_name' => 'Muhamad Evan Fauzan',
        'address_recipient' => 'Evan & Salwa',
        'address_phone' => '0857-1894-5476',
        'address_full' => 'Jl. Taman Wijaya Kusuma, Ps. Baru, Kecamatan Sawah Besar, Kota Jakarta Pusat, DKI Jakarta 10710'
    ],
    'gallery' => [
        'assets/images/savan.jpg',
        'assets/images/img1.jpeg',
        'assets/images/img2.jpeg',
        'assets/images/img3.jpeg',
        'assets/images/img4.jpeg'
    ]
];

$method = $_SERVER['REQUEST_METHOD'];

// Helper to get all settings from DB or fallback
function getAllSettings($pdo, $defaultSettings) {
    if (!$pdo) return $defaultSettings;

    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM wedding_settings");
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $merged = $defaultSettings;
        foreach ($rows as $key => $val) {
            $decoded = json_decode($val, true);
            if ($decoded !== null && is_array($decoded) && isset($defaultSettings[$key]) && is_array($defaultSettings[$key])) {
                $merged[$key] = array_replace_recursive($defaultSettings[$key], $decoded);
            } elseif ($decoded !== null) {
                $merged[$key] = $decoded;
            } else {
                $merged[$key] = $val;
            }
        }
        return $merged;
    } catch (Exception $e) {
        return $defaultSettings;
    }
}

if ($isDirectApi) {
    if ($method === 'GET') {
        $settings = getAllSettings($pdo, $defaultSettings);
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
        exit;
    }

    if ($method === 'POST') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        if (!$input) {
            $input = $_POST;
        }

        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data pengaturan kosong.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO wedding_settings (setting_key, setting_value, updated_at)
                VALUES (:key, :val, NOW())
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
            ");

            foreach ($input as $k => $v) {
                $valToStore = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : strval($v);
                $stmt->execute([':key' => $k, ':val' => $valToStore]);
            }

            $latest = getAllSettings($pdo, $defaultSettings);
            echo json_encode([
                'success' => true,
                'message' => 'Pengaturan wedding berhasil diperbarui!',
                'data' => $latest
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak didukung.']);
    exit;
}
