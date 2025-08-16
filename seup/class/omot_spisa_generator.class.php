<?php

/**
 * Plaćena licenca
 * (c) 2025 8Core Association
 * Tomislav Galić <tomislav@8core.hr>
 * Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 * Sva prava pridržana. Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima 
 * te ga je izričito zabranjeno umnožavati, distribuirati, mijenjati, objavljivati ili 
 * na drugi način eksploatirati bez pismenog odobrenja autora.
 */

/**
 * Omot Spisa Generator Class for SEUP Module
 * Generates A3 cover sheets that fold into 4 A4 sections
 */
class Omot_Spisa_Generator
{
    private $db;
    private $conf;
    private $user;
    private $langs;
    private $pdf;
    
    // A3 dimensions in mm
    const A3_WIDTH = 420;
    const A3_HEIGHT = 297;
    
    // A4 zone dimensions (A3 divided by 2)
    const ZONE_WIDTH = 210;
    const ZONE_HEIGHT = 148.5;
    
    // Margins
    const MARGIN_TOP = 10;
    const MARGIN_LEFT = 10;
    const MARGIN_RIGHT = 10;
    const MARGIN_BOTTOM = 10;

    public function __construct($db, $conf, $user, $langs)
    {
        $this->db = $db;
        $this->conf = $conf;
        $this->user = $user;
        $this->langs = $langs;
        
        require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/modules/barcode/doc/tcpdfbarcode.modules.php';
    }

    /**
     * Generate cover sheet PDF for a predmet
     */
    public function generateOmotSpisa($predmet_id, $output_type = 'F')
    {
        try {
            // Get predmet data
            $predmetData = $this->getPredmetData($predmet_id);
            if (!$predmetData) {
                throw new Exception("Predmet not found: $predmet_id");
            }

            // Get documents list
            $documents = $this->getDocumentsList($predmet_id);
            
            // Initialize PDF
            $this->initializePDF();
            
            // Generate the 4 zones
            $this->generateZone1_Prednja($predmetData);
            $this->generateZone2_UnutarnjaLijeva($documents);
            $this->generateZone3_UnutarnjaDesna($documents);
            $this->generateZone4_Zadnja($predmetData);
            
            // Add fold lines
            $this->addFoldLines();
            
            // Generate filename
            $filename = $this->generateFilename($predmetData);
            
            // Output PDF
            return $this->outputPDF($filename, $output_type);
            
        } catch (Exception $e) {
            dol_syslog("Error generating omot spisa: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }

    /**
     * Initialize PDF with A3 landscape settings
     */
    private function initializePDF()
    {
        $this->pdf = pdf_getInstance();
        $this->pdf->SetCreator('SEUP - 8Core Association');
        $this->pdf->SetAuthor($this->user->getFullName($this->langs));
        $this->pdf->SetTitle('Omot Spisa');
        $this->pdf->SetSubject('Omot spisa za predmet');
        
        // A3 Landscape
        $this->pdf->AddPage('L', array(self::A3_WIDTH, self::A3_HEIGHT));
        $this->pdf->SetMargins(self::MARGIN_LEFT, self::MARGIN_TOP, self::MARGIN_RIGHT);
        $this->pdf->SetAutoPageBreak(false);
        
        // Set font
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 10);
    }

    /**
     * Generate Zone 1 - Prednja strana (Front page)
     */
    private function generateZone1_Prednja($predmetData)
    {
        $x = self::MARGIN_LEFT;
        $y = self::MARGIN_TOP;
        $width = self::ZONE_WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT;
        $height = self::ZONE_HEIGHT - self::MARGIN_TOP - self::MARGIN_BOTTOM;
        
        // Zone border for development (remove in production)
        $this->pdf->Rect($x, $y, $width, $height, 'D');
        
        // Header - Ustanova
        $this->pdf->SetXY($x + 5, $y + 5);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 14);
        $this->pdf->Cell($width - 10, 8, $predmetData->name_ustanova, 0, 1, 'C');
        
        $this->pdf->SetXY($x + 5, $y + 15);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 10);
        $this->pdf->Cell($width - 10, 6, 'Oznaka ustanove: ' . $predmetData->code_ustanova, 0, 1, 'C');
        
        // Separator line
        $this->pdf->Line($x + 10, $y + 25, $x + $width - 10, $y + 25);
        
        // Klasifikacijska oznaka - VELIKI FONT
        $klasa = $predmetData->klasa_br . '-' . $predmetData->sadrzaj . '/' . 
                 $predmetData->godina . '-' . $predmetData->dosje_broj . '/' . 
                 $predmetData->predmet_rbr;
        
        $this->pdf->SetXY($x + 5, $y + 35);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 16);
        $this->pdf->Cell($width - 10, 10, 'KLASA: ' . $klasa, 0, 1, 'C');
        
        // Naziv predmeta
        $this->pdf->SetXY($x + 5, $y + 50);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 12);
        $this->pdf->MultiCell($width - 10, 6, 'NAZIV PREDMETA:', 0, 'L');
        
        $this->pdf->SetXY($x + 5, $y + 60);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 11);
        $this->pdf->MultiCell($width - 10, 5, $predmetData->naziv_predmeta, 1, 'L');
        
        // Osnovni podaci
        $this->pdf->SetXY($x + 5, $y + 85);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 9);
        
        $podaci = [
            'Datum otvaranja: ' . date('d.m.Y', strtotime($predmetData->tstamp_created)),
            'Odgovorna osoba: ' . $predmetData->ime_prezime,
            'Vrijeme čuvanja: ' . ($predmetData->vrijeme_cuvanja == 0 ? 'Trajno' : $predmetData->vrijeme_cuvanja . ' godina'),
            'Ukupno dokumenata: ' . $predmetData->broj_dokumenata
        ];
        
        $currentY = $y + 85;
        foreach ($podaci as $podatak) {
            $this->pdf->SetXY($x + 5, $currentY);
            $this->pdf->Cell($width - 10, 5, $podatak, 0, 1, 'L');
            $currentY += 6;
        }
        
        // Barkod - Code 128 (gornji desni kut)
        $this->generateBarkod($predmetData, $x + $width - 60, $y + 5);
    }

    /**
     * Generate Zone 2 - Unutrašnja lijeva (Document list start)
     */
    private function generateZone2_UnutarnjaLijeva($documents)
    {
        $x = self::MARGIN_LEFT;
        $y = self::ZONE_HEIGHT + self::MARGIN_TOP;
        $width = self::ZONE_WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT;
        $height = self::ZONE_HEIGHT - self::MARGIN_TOP - self::MARGIN_BOTTOM;
        
        // Zone border
        $this->pdf->Rect($x, $y, $width, $height, 'D');
        
        // Header
        $this->pdf->SetXY($x + 5, $y + 5);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 12);
        $this->pdf->Cell($width - 10, 8, 'POPIS DOKUMENATA I PRILOGA', 0, 1, 'C');
        
        // Table header
        $this->pdf->SetXY($x + 5, $y + 18);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 8);
        
        $colWidths = [15, 80, 25, 25, 45];
        $headers = ['R.br.', 'Naziv dokumenta', 'Datum', 'Veličina', 'Digitalni potpis'];
        
        $currentX = $x + 5;
        foreach ($headers as $i => $header) {
            $this->pdf->SetXY($currentX, $y + 18);
            $this->pdf->Cell($colWidths[$i], 6, $header, 1, 0, 'C');
            $currentX += $colWidths[$i];
        }
        
        // Documents list (first half)
        $this->generateDocumentsList($documents, $x + 5, $y + 24, $width - 10, $height - 30, 1);
    }

    /**
     * Generate Zone 3 - Unutrašnja desna (Document list continuation)
     */
    private function generateZone3_UnutarnjaDesna($documents)
    {
        $x = self::ZONE_WIDTH + self::MARGIN_LEFT;
        $y = self::ZONE_HEIGHT + self::MARGIN_TOP;
        $width = self::ZONE_WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT;
        $height = self::ZONE_HEIGHT - self::MARGIN_TOP - self::MARGIN_BOTTOM;
        
        // Zone border
        $this->pdf->Rect($x, $y, $width, $height, 'D');
        
        // Continuation header
        $this->pdf->SetXY($x + 5, $y + 5);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 12);
        $this->pdf->Cell($width - 10, 8, 'POPIS DOKUMENATA (nastavak)', 0, 1, 'C');
        
        // Table header
        $this->pdf->SetXY($x + 5, $y + 18);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 8);
        
        $colWidths = [15, 80, 25, 25, 45];
        $headers = ['R.br.', 'Naziv dokumenta', 'Datum', 'Veličina', 'Digitalni potpis'];
        
        $currentX = $x + 5;
        foreach ($headers as $i => $header) {
            $this->pdf->SetXY($currentX, $y + 18);
            $this->pdf->Cell($colWidths[$i], 6, $header, 1, 0, 'C');
            $currentX += $colWidths[$i];
        }
        
        // Documents list (second half)
        $this->generateDocumentsList($documents, $x + 5, $y + 24, $width - 10, $height - 30, 2);
    }

    /**
     * Generate Zone 4 - Zadnja strana (Back page)
     */
    private function generateZone4_Zadnja($predmetData)
    {
        $x = self::ZONE_WIDTH + self::MARGIN_LEFT;
        $y = self::MARGIN_TOP;
        $width = self::ZONE_WIDTH - self::MARGIN_LEFT - self::MARGIN_RIGHT;
        $height = self::ZONE_HEIGHT - self::MARGIN_TOP - self::MARGIN_BOTTOM;
        
        // Zone border
        $this->pdf->Rect($x, $y, $width, $height, 'D');
        
        // Napomene section
        $this->pdf->SetXY($x + 5, $y + 5);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 11);
        $this->pdf->Cell($width - 10, 8, 'NAPOMENE I BILJEŠKE', 0, 1, 'L');
        
        // Napomene area
        $this->pdf->Rect($x + 5, $y + 15, $width - 10, 40, 'D');
        
        // Arhiviranje section
        $this->pdf->SetXY($x + 5, $y + 60);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 11);
        $this->pdf->Cell($width - 10, 8, 'ARHIVIRANJE', 0, 1, 'L');
        
        $this->pdf->SetXY($x + 5, $y + 70);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 9);
        $this->pdf->Cell(60, 6, 'Datum arhiviranja:', 0, 0, 'L');
        $this->pdf->Cell(80, 6, '___________________', 0, 1, 'L');
        
        $this->pdf->SetXY($x + 5, $y + 78);
        $this->pdf->Cell(60, 6, 'Razlog arhiviranja:', 0, 0, 'L');
        $this->pdf->Cell(80, 6, '___________________', 0, 1, 'L');
        
        // Potpisi section
        $this->pdf->SetXY($x + 5, $y + 95);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), 'B', 11);
        $this->pdf->Cell($width - 10, 8, 'POTPISI OVLAŠTENIH OSOBA', 0, 1, 'L');
        
        // Signature boxes
        $signatureY = $y + 108;
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 8);
        
        // Kreirao
        $this->pdf->SetXY($x + 5, $signatureY);
        $this->pdf->Cell(90, 6, 'Kreirao:', 0, 0, 'L');
        $this->pdf->SetXY($x + 5, $signatureY + 8);
        $this->pdf->Cell(90, 6, $predmetData->ime_prezime, 0, 0, 'L');
        $this->pdf->Line($x + 5, $signatureY + 15, $x + 95, $signatureY + 15);
        
        // Odobrio
        $this->pdf->SetXY($x + 105, $signatureY);
        $this->pdf->Cell(90, 6, 'Odobrio:', 0, 0, 'L');
        $this->pdf->Line($x + 105, $signatureY + 15, $x + 195, $signatureY + 15);
        
        // Footer info
        $this->pdf->SetXY($x + 5, $y + $height - 15);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 7);
        $this->pdf->Cell($width - 10, 4, 'Generirano: ' . date('d.m.Y H:i:s') . ' | SEUP v.14.0.4', 0, 1, 'C');
        $this->pdf->Cell($width - 10, 4, '© 8Core Association - Sva prava pridržana', 0, 1, 'C');
    }

    /**
     * Generate documents list for zones 2 and 3
     */
    private function generateDocumentsList($documents, $startX, $startY, $maxWidth, $maxHeight, $zone)
    {
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 7);
        
        $colWidths = [15, 80, 25, 25, 45];
        $rowHeight = 5;
        $maxRows = floor($maxHeight / $rowHeight);
        
        // Calculate which documents to show in this zone
        $docsPerZone = floor($maxRows);
        $startIndex = ($zone == 1) ? 0 : $docsPerZone;
        $endIndex = ($zone == 1) ? $docsPerZone : count($documents);
        
        $currentY = $startY;
        $rowNum = $startIndex + 1;
        
        for ($i = $startIndex; $i < $endIndex && $i < count($documents); $i++) {
            if ($currentY + $rowHeight > $startY + $maxHeight) break;
            
            $doc = $documents[$i];
            $currentX = $startX;
            
            // Row number
            $this->pdf->SetXY($currentX, $currentY);
            $this->pdf->Cell($colWidths[0], $rowHeight, $rowNum, 1, 0, 'C');
            $currentX += $colWidths[0];
            
            // Document name (truncated if too long)
            $this->pdf->SetXY($currentX, $currentY);
            $truncatedName = $this->truncateText($doc->filename, 35);
            $this->pdf->Cell($colWidths[1], $rowHeight, $truncatedName, 1, 0, 'L');
            $currentX += $colWidths[1];
            
            // Date
            $this->pdf->SetXY($currentX, $currentY);
            $date = isset($doc->date_c) ? date('d.m.Y', $doc->date_c) : 'N/A';
            $this->pdf->Cell($colWidths[2], $rowHeight, $date, 1, 0, 'C');
            $currentX += $colWidths[2];
            
            // Size
            $this->pdf->SetXY($currentX, $currentY);
            $size = $this->formatFileSize($doc);
            $this->pdf->Cell($colWidths[3], $rowHeight, $size, 1, 0, 'C');
            $currentX += $colWidths[3];
            
            // Digital signature status
            $this->pdf->SetXY($currentX, $currentY);
            $signatureStatus = $this->getSignatureStatus($doc);
            $this->pdf->Cell($colWidths[4], $rowHeight, $signatureStatus, 1, 0, 'C');
            
            $currentY += $rowHeight;
            $rowNum++;
        }
    }

    /**
     * Generate barkod (Code 128 + QR)
     */
    private function generateBarkod($predmetData, $x, $y)
    {
        // Code 128 - Predmet ID
        $barkodText = 'SEUP-' . date('Y') . '-' . str_pad($predmetData->ID_predmeta, 6, '0', STR_PAD_LEFT);
        
        // Generate Code 128
        require_once DOL_DOCUMENT_ROOT . '/core/modules/barcode/doc/tcpdfbarcode.modules.php';
        $barcode = new modTcpdfbarcode();
        
        if (method_exists($barcode, 'writeBarcode')) {
            $this->pdf->SetXY($x, $y);
            $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 8);
            $this->pdf->Cell(50, 4, $barkodText, 0, 1, 'C');
            
            // Simple barcode representation (you can enhance with actual barcode library)
            $this->pdf->Rect($x, $y + 5, 50, 8, 'D');
            $this->pdf->SetXY($x, $y + 6);
            $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 6);
            $this->pdf->Cell(50, 6, '||||| ' . $barkodText . ' |||||', 0, 1, 'C');
        }
        
        // QR Code placeholder
        $qrUrl = $this->generateQRUrl($predmetData);
        $this->pdf->SetXY($x, $y + 18);
        $this->pdf->SetFont(pdf_getPDFFont($this->langs), '', 6);
        $this->pdf->Cell(50, 4, 'QR: ' . $qrUrl, 0, 1, 'C');
        $this->pdf->Rect($x + 15, $y + 22, 20, 20, 'D');
        $this->pdf->SetXY($x + 20, $y + 30);
        $this->pdf->Cell(10, 4, 'QR', 0, 1, 'C');
    }

    /**
     * Add fold lines for A3 to A4 folding
     */
    private function addFoldLines()
    {
        // Vertical fold line (middle)
        $this->pdf->SetDrawColor(200, 200, 200);
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Line(self::ZONE_WIDTH, 0, self::ZONE_WIDTH, self::A3_HEIGHT);
        
        // Horizontal fold line (middle)
        $this->pdf->Line(0, self::ZONE_HEIGHT, self::A3_WIDTH, self::ZONE_HEIGHT);
        
        // Reset draw color
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetLineWidth(0.2);
    }

    /**
     * Get predmet data from database
     */
    private function getPredmetData($predmet_id)
    {
        $sql = "SELECT 
                    p.ID_predmeta,
                    p.klasa_br,
                    p.sadrzaj,
                    p.dosje_broj,
                    p.godina,
                    p.predmet_rbr,
                    p.naziv_predmeta,
                    p.vrijeme_cuvanja,
                    p.tstamp_created,
                    u.code_ustanova,
                    u.name_ustanova,
                    k.ime_prezime,
                    ko.opis_klasifikacijske_oznake,
                    (SELECT COUNT(*) FROM " . MAIN_DB_PREFIX . "ecm_files ef 
                     WHERE ef.filepath LIKE CONCAT('SEUP/Predmeti/%/', p.klasa_br, '-', p.sadrzaj, '_', p.godina, '-', p.dosje_broj, '_', p.predmet_rbr, '-%')
                    ) as broj_dokumenata
                FROM " . MAIN_DB_PREFIX . "a_predmet p
                LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
                LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON p.ID_interna_oznaka_korisnika = k.ID
                LEFT JOIN " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ko ON p.ID_klasifikacijske_oznake = ko.ID_klasifikacijske_oznake
                WHERE p.ID_predmeta = " . (int)$predmet_id;

        $resql = $this->db->query($sql);
        if ($resql && $obj = $this->db->fetch_object($resql)) {
            return $obj;
        }
        
        return false;
    }

    /**
     * Get documents list for predmet
     */
    private function getDocumentsList($predmet_id)
    {
        require_once __DIR__ . '/predmet_helper.class.php';
        return Predmet_helper::getCombinedDocuments($this->db, $this->conf, $predmet_id);
    }

    /**
     * Generate filename for the cover sheet
     */
    private function generateFilename($predmetData)
    {
        $klasa = $predmetData->klasa_br . '-' . $predmetData->sadrzaj . '_' . 
                 $predmetData->godina . '-' . $predmetData->dosje_broj . '_' . 
                 $predmetData->predmet_rbr;
        
        $timestamp = date('Ymd_His');
        return "omot_spisa_{$klasa}_{$timestamp}.pdf";
    }

    /**
     * Generate QR URL for digital access
     */
    private function generateQRUrl($predmetData)
    {
        global $dolibarr_main_url_root;
        $baseUrl = $dolibarr_main_url_root ?: 'https://your-dolibarr.com';
        return $baseUrl . '/custom/seup/pages/predmet.php?id=' . $predmetData->ID_predmeta;
    }

    /**
     * Format file size
     */
    private function formatFileSize($doc)
    {
        if (isset($doc->size) && $doc->size > 0) {
            return Predmet_helper::formatFileSize($doc->size);
        }
        
        // Try to get size from filesystem
        if (isset($doc->filepath)) {
            require_once __DIR__ . '/predmet_helper.class.php';
            $relative_path = Predmet_helper::getPredmetFolderPath($doc->ID_predmeta ?? 0, $this->db);
            $full_path = DOL_DATA_ROOT . '/ecm/' . rtrim($relative_path, '/') . '/' . $doc->filename;
            if (file_exists($full_path)) {
                return Predmet_helper::formatFileSize(filesize($full_path));
            }
        }
        
        return 'N/A';
    }

    /**
     * Get digital signature status
     */
    private function getSignatureStatus($doc)
    {
        if (isset($doc->digital_signature) && $doc->digital_signature == 1) {
            $status = $doc->signature_status ?? 'unknown';
            switch ($status) {
                case 'valid':
                    return 'POTPISAN';
                case 'invalid':
                    return 'NEVALJAN';
                case 'expired':
                    return 'ISTEKAO';
                default:
                    return 'NEPOZNATO';
            }
        }
        
        // Check if it's PDF and scan if needed
        if (strtolower(pathinfo($doc->filename, PATHINFO_EXTENSION)) === 'pdf') {
            return 'PROVJERI';
        }
        
        return 'NIJE PDF';
    }

    /**
     * Truncate text to fit in cell
     */
    private function truncateText($text, $maxLength)
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Output PDF to file or browser
     */
    private function outputPDF($filename, $output_type = 'F')
    {
        switch ($output_type) {
            case 'F': // Save to file
                $filepath = DOL_DATA_ROOT . '/ecm/temp/' . $filename;
                $this->pdf->Output($filepath, 'F');
                return $filepath;
                
            case 'D': // Download
                $this->pdf->Output($filename, 'D');
                return true;
                
            case 'I': // Inline browser
                $this->pdf->Output($filename, 'I');
                return true;
                
            default:
                return false;
        }
    }

    /**
     * Generate omot for multiple predmeti (batch)
     */
    public static function batchGenerateOmoti($db, $conf, $user, $langs, $predmet_ids, $output_dir = null)
    {
        $results = [];
        $output_dir = $output_dir ?: DOL_DATA_ROOT . '/ecm/temp/omoti/';
        
        // Ensure output directory exists
        if (!is_dir($output_dir)) {
            dol_mkdir($output_dir);
        }
        
        foreach ($predmet_ids as $predmet_id) {
            try {
                $generator = new self($db, $conf, $user, $langs);
                $filepath = $generator->generateOmotSpisa($predmet_id, 'F');
                
                if ($filepath) {
                    // Move to batch directory
                    $filename = basename($filepath);
                    $newPath = $output_dir . $filename;
                    
                    if (rename($filepath, $newPath)) {
                        $results[] = [
                            'predmet_id' => $predmet_id,
                            'success' => true,
                            'filepath' => $newPath,
                            'filename' => $filename
                        ];
                    } else {
                        $results[] = [
                            'predmet_id' => $predmet_id,
                            'success' => false,
                            'error' => 'Failed to move file'
                        ];
                    }
                } else {
                    $results[] = [
                        'predmet_id' => $predmet_id,
                        'success' => false,
                        'error' => 'PDF generation failed'
                    ];
                }
                
            } catch (Exception $e) {
                $results[] = [
                    'predmet_id' => $predmet_id,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Get omot generation statistics
     */
    public static function getOmotStatistics($db, $conf)
    {
        try {
            $stats = [
                'total_predmeti' => 0,
                'generated_omoti' => 0,
                'last_generated' => null
            ];

            // Count total predmeti
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_predmet";
            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['total_predmeti'] = (int)$obj->count;
            }

            // Count generated omoti (files in temp/omoti directory)
            $omoti_dir = DOL_DATA_ROOT . '/ecm/temp/omoti/';
            if (is_dir($omoti_dir)) {
                $files = glob($omoti_dir . 'omot_spisa_*.pdf');
                $stats['generated_omoti'] = count($files);
                
                if (!empty($files)) {
                    // Get last modification time
                    $lastFile = max(array_map('filemtime', $files));
                    $stats['last_generated'] = date('Y-m-d H:i:s', $lastFile);
                }
            }

            return $stats;

        } catch (Exception $e) {
            dol_syslog("Error getting omot statistics: " . $e->getMessage(), LOG_ERR);
            return null;
        }
    }
}