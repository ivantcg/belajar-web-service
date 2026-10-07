<?php
header("Content-Type: application/json");
require "data.php";

// Data awal dari data.php, lalu ditimpa data.json bila sudah ada,
// supaya perubahan tetap tersimpan antar request.
$dataFile = __DIR__ . "/data.json";
if (file_exists($dataFile)) {
    $students = json_decode(file_get_contents($dataFile), true);
}

function simpan($students) {
    global $dataFile;
    file_put_contents($dataFile, json_encode(array_values($students), JSON_PRETTY_PRINT));
}

function tambahData($students) {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!isset($input['nim']) || !isset($input['name']) || !isset($input['major'])) {
        http_response_code(400);
        echo json_encode(["error" => "Field nim, name, dan major wajib diisi"]);
        return;
    }

    // max id + 1 (count()+1 bisa menghasilkan id ganda setelah ada data dihapus)
    $newId = $students ? max(array_column($students, "id")) + 1 : 1;
    $newStudent = [
        "id" => $newId,
        "nim" => $input['nim'],
        "name" => $input['name'],
        "major" => $input['major'],
    ];
    $students[] = $newStudent;
    simpan($students);

    http_response_code(201);
    header("Location: /mahasiswa.php?id=$newId");
    echo json_encode($newStudent);
}

function ambilSemuaData($students) {
    http_response_code(200);
    echo json_encode($students);
}

function ambilSatuData($students, $id) {
    foreach ($students as $s) {
        if ($s["id"] == $id) {
            http_response_code(200);
            echo json_encode($s);
            return;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Mahasiswa tidak ditemukan"]);
}

function ubahData($students, $id) {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    unset($input['id']); // id tidak boleh diubah

    foreach ($students as $i => $s) {
        if ($s["id"] == $id) {
            $students[$i] = array_merge($s, $input);
            simpan($students);
            http_response_code(200);
            echo json_encode($students[$i]);
            return;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Mahasiswa tidak ditemukan"]);
}

function hapusData($students, $id) {
    foreach ($students as $i => $s) {
        if ($s["id"] == $id) {
            unset($students[$i]);
            simpan($students);
            header_remove("Content-Type");
            http_response_code(204); // No Content: tanpa body
            return;
        }
    }
    http_response_code(404);
    echo json_encode(["error" => "Mahasiswa tidak ditemukan"]);
}

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

match ($method) {
    'GET' => $id ? ambilSatuData($students, $id) : ambilSemuaData($students),
    'POST' => tambahData($students),
    'PATCH' => ubahData($students, $id),
    'DELETE' => hapusData($students, $id),
    default => http_response_code(405),
};