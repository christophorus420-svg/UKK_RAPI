<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use ZipArchive;

class UserImportService
{
    /**
     * Memproses import berkas Excel (.xlsx) atau CSV
     *
     * @return array{
     *     total: int,
     *     success: int,
     *     failed: int,
     *     failures: array<int, array{row: int, fullname: string, username: string, reason: string}>,
     *     imported_users: array<int, string>
     * }
     */
    public function import(UploadedFile $file): array
    {
        $rows = $this->parseFileToRows($file);

        if (empty($rows)) {
            return [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'failures' => [
                    ['row' => 1, 'fullname' => '-', 'username' => '-', 'reason' => 'Berkas kosong atau format baris tidak dapat dibaca.']
                ],
                'imported_users' => [],
            ];
        }

        // Ambil baris pertama sebagai header untuk mapping kolom
        $header = array_shift($rows);
        $headerMap = $this->mapHeaderColumns($header);

        $total = 0;
        $success = 0;
        $failed = 0;
        $failures = [];
        $importedUsers = [];

        // Track duplikat dalam berkas itu sendiri
        $seenUsernames = [];
        $seenEmails = [];

        // Muat roles ke memory untuk validasi cepat
        $roles = Role::all()->keyBy(fn ($r) => strtolower($r->role_name));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 untuk 1-based, +1 karena header

            // Lewati baris yang sepenuhnya kosong
            $nonEmptyCells = array_filter($row, fn ($cell) => $cell !== null && trim((string) $cell) !== '');
            if (empty($nonEmptyCells)) {
                continue;
            }

            $total++;

            $fullname = trim((string) ($row[$headerMap['fullname']] ?? ''));
            $username = trim((string) ($row[$headerMap['username']] ?? ''));
            $email = trim((string) ($row[$headerMap['email']] ?? ''));
            $phone = trim((string) ($row[$headerMap['phone_number']] ?? ''));
            $roleStr = strtolower(trim((string) ($row[$headerMap['role']] ?? '')));
            $password = trim((string) ($row[$headerMap['password']] ?? ''));

            // 1. Validasi Data Kosong
            if ($fullname === '') {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => '(Kosong)',
                    'username' => $username ?: '-',
                    'reason' => 'Nama lengkap wajib diisi dan tidak boleh kosong.',
                ];
                continue;
            }

            if ($username === '') {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => '(Kosong)',
                    'reason' => 'Username wajib diisi dan tidak boleh kosong.',
                ];
                continue;
            }

            if ($roleStr === '') {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => $username,
                    'reason' => 'Peran (role) wajib ditentukan (admin, pic, guru, atau guest).',
                ];
                continue;
            }

            // 2. Validasi Format Username (hanya alfanumerik & underscore)
            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => $username,
                    'reason' => 'Format username tidak valid (hanya huruf, angka, titik, strip, dan garis bawah tanpa spasi).',
                ];
                continue;
            }

            // 3. Validasi Format Peran / Role
            if (!$roles->has($roleStr)) {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => $username,
                    'reason' => "Peran '{$roleStr}' tidak valid. Pilihan yang tersedia: admin, pic, guru, guest.",
                ];
                continue;
            }

            // 4. Validasi Format Email jika diisi
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => $username,
                    'reason' => "Format email '{$email}' tidak valid.",
                ];
                continue;
            }

            // 5. Validasi Duplikasi di Dalam File Sendiri
            $lowerUsername = strtolower($username);
            if (isset($seenUsernames[$lowerUsername])) {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => $username,
                    'reason' => "Username '{$username}' terduplikasi di dalam berkas (sebelumnya ada di baris {$seenUsernames[$lowerUsername]}).",
                ];
                continue;
            }
            $seenUsernames[$lowerUsername] = $rowNumber;

            if ($email !== '') {
                $lowerEmail = strtolower($email);
                if (isset($seenEmails[$lowerEmail])) {
                    $failed++;
                    $failures[] = [
                        'row' => $rowNumber,
                        'fullname' => $fullname,
                        'username' => $username,
                        'reason' => "Email '{$email}' terduplikasi di dalam berkas (sebelumnya ada di baris {$seenEmails[$lowerEmail]}).",
                    ];
                    continue;
                }
                $seenEmails[$lowerEmail] = $rowNumber;
            }

            // 6. Validasi Duplikasi Terhadap Database
            if (User::where('username', $username)->exists()) {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => $username,
                    'reason' => "Username '{$username}' sudah terdaftar dalam sistem database.",
                ];
                continue;
            }

            if ($email !== '' && User::where('email', $email)->exists()) {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => $username,
                    'reason' => "Email '{$email}' sudah digunakan oleh pengguna lain di database.",
                ];
                continue;
            }

            // Lolos semua validasi -> Simpan Pengguna Baru
            try {
                $targetRole = $roles->get($roleStr);
                $finalPassword = $password !== '' ? $password : 'password123';

                $createdUser = User::create([
                    'fullname' => $fullname,
                    'username' => $username,
                    'email' => $email !== '' ? $email : null,
                    'phone_number' => $phone !== '' ? $phone : '081234567890',
                    'role_id' => $targetRole->id,
                    'password' => Hash::make($finalPassword),
                ]);

                $success++;
                $importedUsers[] = "{$createdUser->fullname} (@{$createdUser->username})";
            } catch (\Throwable $e) {
                $failed++;
                $failures[] = [
                    'row' => $rowNumber,
                    'fullname' => $fullname,
                    'username' => $username,
                    'reason' => 'Kesalahan database saat menyimpan: ' . $e->getMessage(),
                ];
            }
        }

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'failures' => $failures,
            'imported_users' => $importedUsers,
        ];
    }

    /**
     * Parsing berkas ke kumpulan baris array teks
     */
    protected function parseFileToRows(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        if ($ext === 'xlsx') {
            return $this->parseXlsx($realPath);
        }

        // Default ke CSV / TSV / text spreadsheet
        return $this->parseCsv($realPath);
    }

    /**
     * Membaca file OpenXML .xlsx murni dengan ZipArchive & SimpleXML
     */
    protected function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Baca shared strings jika ada
        $sharedStrings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent) {
            $xml = @simplexml_load_string($sharedXmlContent);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $tParts = [];
                        foreach ($si->r as $r) {
                            $tParts[] = (string) $r->t;
                        }
                        $sharedStrings[] = implode('', $tParts);
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca worksheet pertama (sheet1.xml)
        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXmlContent) {
            $zip->close();
            return [];
        }

        $sheetXml = @simplexml_load_string($sheetXmlContent);
        $zip->close();

        if (!$sheetXml || !isset($sheetXml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($sheetXml->sheetData->row as $rowNode) {
            $rowCells = [];
            $lastColIndex = -1;

            foreach ($rowNode->c as $cell) {
                $cellRef = (string) $cell['r']; // e.g. "A1", "B2"
                $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                $colIndex = $this->columnLettersToIndex($colLetters);

                // Isi sel kosong di antara kolom
                while ($lastColIndex + 1 < $colIndex) {
                    $rowCells[] = '';
                    $lastColIndex++;
                }

                $type = (string) $cell['t'];
                $val = (string) $cell->v;

                if ($type === 's' && isset($sharedStrings[(int) $val])) {
                    $val = $sharedStrings[(int) $val];
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $val = (string) $cell->is->t;
                }

                $rowCells[] = trim($val);
                $lastColIndex = $colIndex;
            }

            $rows[] = $rowCells;
        }

        return $rows;
    }

    /**
     * Konversi kode kolom huruf (A, B, C... AA) ke indeks 0-based
     */
    protected function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    /**
     * Membaca file format CSV / TSV
     */
    protected function parseCsv(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Deteksi dan bersihkan UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        // Deteksi delimiter (koma atau titik koma)
        $firstLine = strtok($content, "\r\n");
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        $delimiter = $semicolonCount > $commaCount ? ';' : ',';

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line, $delimiter);
            $rows[] = array_map('trim', $row);
        }

        return $rows;
    }

    /**
     * Petakan kolom header berkas ke field user
     */
    protected function mapHeaderColumns(array $header): array
    {
        $map = [
            'fullname' => 0,
            'username' => 1,
            'email' => 2,
            'phone_number' => 3,
            'role' => 4,
            'password' => 5,
        ];

        foreach ($header as $idx => $colName) {
            $norm = strtolower(trim(str_replace(['_', '-', ' '], '', $colName)));

            if (str_contains($norm, 'namalengkap') || str_contains($norm, 'fullname') || $norm === 'nama') {
                $map['fullname'] = $idx;
            } elseif (str_contains($norm, 'username') || str_contains($norm, 'pengguna')) {
                $map['username'] = $idx;
            } elseif (str_contains($norm, 'email') || str_contains($norm, 'surel')) {
                $map['email'] = $idx;
            } elseif (str_contains($norm, 'telepon') || str_contains($norm, 'phone') || str_contains($norm, 'whatsapp') || str_contains($norm, 'wa') || str_contains($norm, 'hp')) {
                $map['phone_number'] = $idx;
            } elseif (str_contains($norm, 'peran') || str_contains($norm, 'role') || str_contains($norm, 'jabatan')) {
                $map['role'] = $idx;
            } elseif (str_contains($norm, 'password') || str_contains($norm, 'katasandi')) {
                $map['password'] = $idx;
            }
        }

        return $map;
    }

    /**
     * Menghasilkan isi template Excel / CSV lengkap dengan contoh dan BOM UTF-8
     */
    public function generateTemplateCsv(): string
    {
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM agar Excel membukanya langsung dengan encoding yang benar
        $delimiter = ",";

        $rows = [
            ['Nama Lengkap', 'Username', 'Email', 'Nomor Telepon', 'Peran (admin/pic/guru/guest)', 'Password (Opsional)'],
            ['Ahmad Dahlan, S.Pd', 'ahmad_guru', 'ahmad@sekolah.sch.id', '081234567891', 'guru', 'password123'],
            ['Siti Maryam, S.Kom', 'siti_pic', 'siti@sekolah.sch.id', '081234567892', 'pic', 'password123'],
            ['Drs. Hendro Wibowo', 'hendro_admin', 'hendro@sekolah.sch.id', '081234567893', 'admin', 'password123'],
            ['Muhammad Farhan', 'farhan_osis', 'farhan@siswa.sekolah.sch.id', '081234567894', 'guest', 'password123'],
        ];

        foreach ($rows as $row) {
            $escaped = array_map(function ($val) use ($delimiter) {
                if (str_contains($val, $delimiter) || str_contains($val, '"') || str_contains($val, "\n")) {
                    return '"' . str_replace('"', '""', $val) . '"';
                }
                return $val;
            }, $row);
            $output .= implode($delimiter, $escaped) . "\r\n";
        }

        return $output;
    }
}

