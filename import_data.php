<?php
// File: import_data.php
require 'config/db.php';

class SimpleXLSXParse {
    private $sharedStrings = [];
    private $sheetData = [];
    private $zip;

    public function __construct($filePath) {
        $this->zip = new ZipArchive;
        if ($this->zip->open($filePath) === TRUE) {
            $this->loadSharedStrings();
            $this->loadSheet('xl/worksheets/sheet1.xml');
            $this->zip->close();
        } else {
            die("Failed to open XLSX file.");
        }
    }

    private function loadSharedStrings() {
        $xml = $this->zip->getFromName('xl/sharedStrings.xml');
        if ($xml) {
            $dom = new DOMDocument();
            $dom->loadXML($xml);
            // Namespace handling can be tricky, simple parsing by tag name 't' usually works for basic text
            $ts = $dom->getElementsByTagName('t');
            foreach ($ts as $t) {
                $this->sharedStrings[] = $t->nodeValue;
            }
        }
    }

    private function loadSheet($sheetName) {
        $xml = $this->zip->getFromName($sheetName);
        if ($xml) {
            $dom = new DOMDocument();
            $dom->loadXML($xml);
            $rows = $dom->getElementsByTagName('row');
            foreach ($rows as $row) {
                $rowData = [];
                $cells = $row->getElementsByTagName('c');
                $colIndex = 0;
                foreach ($cells as $cell) {
                    // Determine column index explicitly from 'r' attribute (e.g., "A1", "B1")
                    // This is simplified; we assume sequential columns for now or basic mapping
                    $type = $cell->getAttribute('t');
                    $val = $cell->nodeValue;
                    
                    // Value is usually inside <v> tag
                    $vTag = $cell->getElementsByTagName('v')->item(0);
                    $val = $vTag ? $vTag->nodeValue : '';

                    if ($type == 's') { // Shared String
                        $val = isset($this->sharedStrings[(int)$val]) ? $this->sharedStrings[(int)$val] : '';
                    }
                    $rowData[] = trim($val);
                }
                $this->sheetData[] = $rowData;
            }
        }
    }

    public function getData() {
        return $this->sheetData;
    }
}

// Run Import
echo "Starting import...\n";
$xlsx = new SimpleXLSXParse('DA-data.xlsx');
$data = $xlsx->getData();

if (empty($data)) {
    die("No data found or failed to parse.\n");
}

// Assume Row 1 is Header, Data starts from Row 2
// Expected Columns based on Shared Strings analysis:
// Col 0: Tên dự án
// Col 1: Mã dự án
// Col 2: Địa chỉ đường
// Col 3: Phường/Xã
// Col 4: Tỉnh/TP

$count = 0;
foreach ($data as $index => $row) {
    if ($index === 0) continue; // Skip header

    // Validate row data (at least name and code must exist)
    if (empty($row[0]) || empty($row[1])) continue;

    $ten_du_an = $row[0];
    $ma_du_an = $row[1];
    $loai_du_an = $row[2] ?? 'Chung cư';
    $duong = $row[3] ?? '';
    $phuong = $row[4] ?? '';
    $tp = $row[5] ?? '';

    // Insert into DB
    try {
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM projects WHERE ma_du_an = ?");
        $check->execute([$ma_du_an]);
        if ($check->fetch()) {
            echo "Skipped (Exists): $ma_du_an\n";
            continue;
        }

        $stmt = $pdo->prepare("INSERT INTO projects (ten_du_an, ma_du_an, loai_du_an, dia_chi_duong, dia_chi_phuong_xa, dia_chi_tinh_tp) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $ten_du_an,
            $ma_du_an,
            $loai_du_an,
            $duong,
            $phuong,
            $tp
        ]);
        $count++;
        echo "Imported: $ten_du_an ($ma_du_an)\n";
    } catch (PDOException $e) {
        echo "Error importing $ma_du_an: " . $e->getMessage() . "\n";
    }
}

echo "Done. Imported $count projects.\n";
?>
