<?php

header('Content-Type: application/json; charset=utf-8');

const FILE_DATA = __DIR__ . '/../data/nilai.json';

function kirim(int $status, array $body): void
{
    http_response_code($status);

    echo json_encode(
        $body,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    exit;
}

function bacaData(): array
{
    if (!file_exists(FILE_DATA)) {
        return [];
    }

    $isi = file_get_contents(FILE_DATA);

    return json_decode($isi, true) ?? [];
}

function simpanData(array $data): void
{
    file_put_contents(
        FILE_DATA,
        json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ),
        LOCK_EX
    );
}

function tentukanKeterangan(float $nilai): string
{
    return $nilai >= 60 ? 'Lulus' : 'Tidak Lulus';
}

function tentukanGrade(float $nilai): string
{
    if ($nilai >= 90) {
        return 'A';
    }

    if ($nilai >= 80) {
        return 'B';
    }

    if ($nilai >= 70) {
        return 'C';
    }

    if ($nilai >= 60) {
        return 'D';
    }

    return 'E';
}

/* ===============================
   ROUTING
   =============================== */

$method = $_SERVER['REQUEST_METHOD'];

$path = rtrim(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
    '/'
);

if ($path !== '/api/nilai') {
    kirim(404, [
        'status' => 'error',
        'pesan' => 'Endpoint tidak ditemukan'
    ]);
}

/* ===============================
   GET /api/nilai
   =============================== */

if ($method === 'GET') {
    $data = bacaData();

    kirim(200, [
        'status' => 'success',
        'total' => count($data),
        'data' => $data
    ]);
}

/* ===============================
   POST /api/nilai
   =============================== */

if ($method === 'POST') {

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        kirim(400, [
            'status' => 'error',
            'pesan' => 'Body harus berupa JSON yang valid'
        ]);
    }

    $nim = trim($input['nim'] ?? '');
    $nama = trim($input['nama'] ?? '');
    $mataKuliah = trim($input['mata_kuliah'] ?? '');
    $nilai = $input['nilai'] ?? null;

    $error = [];

    if ($nim === '') {
        $error[] = 'nim wajib diisi';
    } elseif (!preg_match('/^\d{8,}$/', $nim)) {
        $error[] = 'nim harus berupa angka minimal 8 digit';
    }

    if ($nama === '') {
        $error[] = 'nama wajib diisi';
    }

    if ($mataKuliah === '') {
        $error[] = 'mata_kuliah wajib diisi';
    }

    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $error[] = 'nilai harus angka 0 sampai 100';
    }

    if ($error) {
        kirim(400, [
            'status' => 'error',
            'pesan' => 'Data tidak valid',
            'detail' => $error
        ]);
    }

    $data = bacaData();

    $idBaru = $data
        ? max(array_column($data, 'id')) + 1
        : 1;

    $nilai = (float) $nilai;

    $baru = [
        'id' => $idBaru,
        'nim' => $nim,
        'nama' => $nama,
        'mata_kuliah' => $mataKuliah,
        'nilai' => $nilai + 0,
        'keterangan' => tentukanKeterangan($nilai),
        'grade' => tentukanGrade($nilai)
    ];

    $data[] = $baru;

    simpanData($data);

    kirim(201, [
        'status' => 'success',
        'pesan' => 'Data berhasil disimpan',
        'data' => $baru
    ]);
}

header('Allow: GET, POST');

kirim(405, [
    'status' => 'error',
    'pesan' => 'Method tidak diizinkan'
]);