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
 * Omot Helper Class for SEUP Module
 * Utility functions for cover sheet operations
 */
class Omot_Helper
{
    /**
     * Croatian month names for proper date formatting
     */
    const CROATIAN_MONTHS = [
        1 => 'siječnja', 2 => 'veljače', 3 => 'ožujka', 4 => 'travnja',
        5 => 'svibnja', 6 => 'lipnja', 7 => 'srpnja', 8 => 'kolovoza',
        9 => 'rujna', 10 => 'listopada', 11 => 'studenoga', 12 => 'prosinca'
    ];

    /**
     * Format date in Croatian format for official documents
     */
    public static function formatCroatianDate($timestamp, $include_time = false)
    {
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        $day = date('j', $timestamp);
        $month = (int)date('n', $timestamp);
        $year = date('Y', $timestamp);
        
        $formatted = $day . '. ' . self::CROATIAN_MONTHS[$month] . ' ' . $year . '.';
        
        if ($include_time) {
            $formatted .= ' u ' . date('H:i', $timestamp) . ' sati';
        }
        
        return $formatted;
    }

    /**
     * Generate barcode data for predmet
     */
    public static function generateBarcodeData($predmet_id, $klasa_data)
    {
        $year = date('Y');
        $paddedId = str_pad($predmet_id, 6, '0', STR_PAD_LEFT);
        
        return [
            'code128' => "SEUP-{$year}-{$paddedId}",
            'qr_data' => json_encode([
                'predmet_id' => $predmet_id,
                'klasa' => $klasa_data,
                'generated' => date('Y-m-d H:i:s'),
                'system' => 'SEUP'
            ]),
            'qr_url' => self::generateQRUrl($predmet_id)
        ];
    }

    /**
     * Generate QR URL for digital access
     */
    public static function generateQRUrl($predmet_id)
    {
        global $dolibarr_main_url_root;
        $baseUrl = $dolibarr_main_url_root ?: 'https://your-dolibarr.com';
        
        // Add verification hash for security
        $hash = substr(md5($predmet_id . date('Y-m-d') . 'SEUP_SECRET'), 0, 8);
        
        return $baseUrl . '/custom/seup/pages/predmet.php?id=' . $predmet_id . '&verify=' . $hash;
    }

    /**
     * Validate document types for omot inclusion
     */
    public static function isValidDocumentForOmot($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $validExtensions = ['pdf', 'docx', 'xlsx', 'doc', 'xls', 'odt', 'jpg', 'jpeg', 'png'];
        
        // Exclude omot files themselves
        if (strpos($filename, 'omot_spisa_') === 0) {
            return false;
        }
        
        return in_array($extension, $validExtensions);
    }

    /**
     * Calculate optimal font size for text to fit in area
     */
    public static function calculateOptimalFontSize($text, $maxWidth, $maxHeight, $pdf, $minSize = 6, $maxSize = 12)
    {
        for ($size = $maxSize; $size >= $minSize; $size--) {
            $pdf->SetFont(pdf_getPDFFont(null), '', $size);
            
            // Get text dimensions
            $textWidth = $pdf->GetStringWidth($text);
            $textHeight = $size * 0.35; // Approximate height in mm
            
            if ($textWidth <= $maxWidth && $textHeight <= $maxHeight) {
                return $size;
            }
        }
        
        return $minSize;
    }

    /**
     * Split long text into multiple lines for PDF
     */
    public static function splitTextForPDF($text, $maxCharsPerLine = 50)
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';
        
        foreach ($words as $word) {
            if (strlen($currentLine . ' ' . $word) <= $maxCharsPerLine) {
                $currentLine .= ($currentLine ? ' ' : '') . $word;
            } else {
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            }
        }
        
        if ($currentLine) {
            $lines[] = $currentLine;
        }
        
        return $lines;
    }

    /**
     * Get document type icon for PDF
     */
    public static function getDocumentTypeSymbol($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $symbols = [
            'pdf' => 'PDF',
            'docx' => 'DOC',
            'doc' => 'DOC',
            'xlsx' => 'XLS',
            'xls' => 'XLS',
            'pptx' => 'PPT',
            'ppt' => 'PPT',
            'jpg' => 'IMG',
            'jpeg' => 'IMG',
            'png' => 'IMG',
            'odt' => 'ODT',
            'ods' => 'ODS'
        ];
        
        return $symbols[$extension] ?? 'DOK';
    }

    /**
     * Generate omot template variations based on predmet type
     */
    public static function getOmotTemplate($predmetData)
    {
        // Determine template based on klasa_broj
        $klasa = (int)$predmetData->klasa_br;
        
        if ($klasa >= 1 && $klasa <= 99) {
            return 'standard'; // Standard administrative template
        } elseif ($klasa >= 100 && $klasa <= 199) {
            return 'legal'; // Legal documents template
        } elseif ($klasa >= 200 && $klasa <= 299) {
            return 'financial'; // Financial documents template
        } else {
            return 'general'; // General template
        }
    }

    /**
     * Calculate document statistics for omot
     */
    public static function calculateDocumentStats($documents)
    {
        $stats = [
            'total_count' => count($documents),
            'total_size' => 0,
            'by_type' => [],
            'signed_count' => 0,
            'latest_date' => null,
            'oldest_date' => null
        ];

        foreach ($documents as $doc) {
            // Size calculation
            if (isset($doc->size)) {
                $stats['total_size'] += $doc->size;
            }

            // Type counting
            $extension = strtolower(pathinfo($doc->filename, PATHINFO_EXTENSION));
            $stats['by_type'][$extension] = ($stats['by_type'][$extension] ?? 0) + 1;

            // Signature counting
            if (isset($doc->digital_signature) && $doc->digital_signature == 1) {
                $stats['signed_count']++;
            }

            // Date tracking
            $docDate = isset($doc->date_c) ? $doc->date_c : strtotime($doc->last_modified ?? 'now');
            
            if (!$stats['latest_date'] || $docDate > $stats['latest_date']) {
                $stats['latest_date'] = $docDate;
            }
            
            if (!$stats['oldest_date'] || $docDate < $stats['oldest_date']) {
                $stats['oldest_date'] = $docDate;
            }
        }

        return $stats;
    }

    /**
     * Generate omot metadata for tracking
     */
    public static function generateOmotMetadata($predmet_id, $predmetData, $documents)
    {
        $stats = self::calculateDocumentStats($documents);
        
        return [
            'predmet_id' => $predmet_id,
            'klasa' => $predmetData->klasa_br . '-' . $predmetData->sadrzaj . '/' . 
                      $predmetData->godina . '-' . $predmetData->dosje_broj . '/' . 
                      $predmetData->predmet_rbr,
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $predmetData->ime_prezime,
            'document_count' => $stats['total_count'],
            'total_size' => $stats['total_size'],
            'signed_documents' => $stats['signed_count'],
            'template_used' => self::getOmotTemplate($predmetData),
            'version' => '1.0'
        ];
    }

    /**
     * Clean up temporary omot files
     */
    public static function cleanupTempFiles($older_than_hours = 24)
    {
        try {
            $temp_dir = DOL_DATA_ROOT . '/ecm/temp/';
            $cutoff_time = time() - ($older_than_hours * 3600);
            
            $files = glob($temp_dir . 'omot_spisa_*.pdf');
            $deleted_count = 0;
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff_time) {
                    if (unlink($file)) {
                        $deleted_count++;
                        dol_syslog("Cleaned up temp omot file: " . basename($file), LOG_INFO);
                    }
                }
            }
            
            return [
                'success' => true,
                'deleted_count' => $deleted_count,
                'message' => "Cleaned up $deleted_count temporary omot files"
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get omot download statistics
     */
    public static function getDownloadStatistics($db, $days = 30)
    {
        try {
            // This would track downloads if we implement download logging
            // For now, return basic file statistics
            
            $stats = [
                'period_days' => $days,
                'total_downloads' => 0,
                'unique_users' => 0,
                'popular_predmeti' => [],
                'download_trend' => []
            ];

            // Count omot files created in the period
            $sql = "SELECT COUNT(*) as count
                    FROM " . MAIN_DB_PREFIX . "ecm_files ef
                    WHERE ef.filename LIKE 'omot_spisa_%'
                    AND ef.date_c >= DATE_SUB(NOW(), INTERVAL $days DAY)";

            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $stats['total_downloads'] = $obj->count;
            }

            return $stats;

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}