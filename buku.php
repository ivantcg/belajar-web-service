<?php
header("Content-Type: application/json");

$fileJson = __DIR__ . '/data_buku.json';

// Fungsi helper untuk membaca data dari file JSON
function bacaData($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

// Fungsi helper untuk menyimpan data kembali ke file JSON
function simpanData($file, $data) {
    file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT));
}

$books = bacaData($fileJson);
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

function ambilSemuaData($books) {
    http_response_code(200);
    echo json_encode($books);
}

function ambilSatuData($books, $id) {
    foreach ($books as $b) {
        if ($b["id"] == $id) {
            http_response_code(200);
            echo json_encode($b);
            return;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Buku tidak ditemukan"]);
}

function tambahData(&$books, $fileJson) {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($input['isbn']) || !isset($input['judul_buku']) || !isset($input['kategori']) || !isset($input['stok']) || !isset($input['harga'])) {
        http_response_code(400);
        echo json_encode(["error" => "Field isbn, judul_buku, kategori, stok, dan harga wajib diisi"]);
        return;
    }
    
    // Auto-increment ID berdasarkan ID tertinggi
    $lastBook = end($books);
    $newId = $lastBook ? $lastBook['id'] + 1 : 1;
    
    $newBook = [
        "id" => $newId,
        "isbn" => $input['isbn'],
        "judul_buku" => $input['judul_buku'],
        "kategori" => $input['kategori'],
        "stok" => (int)$input['stok'],
        "harga" => (int)$input['harga']
    ];
    
    $books[] = $newBook;
    simpanData($fileJson, $books); // Simpan otomatis ke file JSON
    
    http_response_code(201);
    header("Location: /buku.php?id=$newId");
    echo json_encode($newBook);
}

function ubahData(&$books, $id, $fileJson) {
    $input = json_decode(file_get_contents("php://input"), true);
    foreach ($books as $i => $b) {
        if ($b["id"] == $id) {
            $books[$i] = array_merge($b, $input);
            simpanData($fileJson, $books); // Simpan otomatis perubahan
            http_response_code(200);
            echo json_encode($books[$i]);
            return;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Buku tidak ditemukan"]);
}

function hapusData(&$books, $id, $fileJson) {
    foreach ($books as $i => $b) {
        if ($b["id"] == $id) {
            unset($books[$i]);
            simpanData($fileJson, $books); // Simpan otomatis setelah dihapus
            http_response_code(204);
            return;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Buku tidak ditemukan"]);
}

match ($method) {
    'GET'    => $id ? ambilSatuData($books, $id) : ambilSemuaData($books),
    'POST'   => tambahData($books, $fileJson),
    'PATCH'  => ubahData($books, $id, $fileJson),
    'DELETE' => hapusData($books, $id, $fileJson),
    default  => http_response_code(405),
};