<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$data = json_decode(file_get_contents("php://input"), true);

$host = 'localhost';
$db   = 'Bintang';
$user = 'postgres';
$pass = 'firbin00';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Koneksi DB gagal: " . $e->getMessage()]);
    exit;
}

// === REGISTER ===
if (strpos($requestUri, '/register') !== false && $method === 'POST') {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Username sudah terdaftar."]);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $stmt->execute(['username' => $username, 'password' => $hashedPassword]);

        echo json_encode(["success" => true, "message" => "Akun berhasil dibuat."]);
    } else {
        echo json_encode(["success" => false, "message" => "Isi username dan password."]);
    }

// === LOGIN ===
} elseif (strpos($requestUri, '/login') !== false && $method === 'POST') {
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(["success" => true, "message" => "Login berhasil"]);
        } else {
            echo json_encode(["success" => false, "message" => "Username atau password salah"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Isi username dan password."]);
    }

// === SEARCH BIDANG TANAH ===
} elseif (strpos($requestUri, '/search') !== false && $method === 'GET') {
    $keyword = $_GET['q'] ?? '';

    if ($keyword) {
        $stmt = $pdo->prepare("
            SELECT * FROM bidang_tanah_percil_klewor
            WHERE LOWER(d_nop) LIKE LOWER(:keyword)
               OR LOWER(nik) LIKE LOWER(:keyword)
               OR LOWER(nama_wp) LIKE LOWER(:keyword)
            LIMIT 50
        ");
        $stmt->execute(['keyword' => '%' . $keyword . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "results" => $results]);
    } else {
        echo json_encode(["success" => false, "message" => "Query kosong."]);
    }

// === DEFAULT ===
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Endpoint tidak ditemukan."]);
}
?>
