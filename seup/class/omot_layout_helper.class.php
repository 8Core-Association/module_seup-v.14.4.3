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
 * Omot Layout Helper Class for SEUP Module
 * Handles precise positioning and layout calculations for A3 cover sheets
 */
class Omot_Layout_Helper
{
    // A3 and A4 dimensions in mm
    const A3_WIDTH = 420;
    const A3_HEIGHT = 297;
    const A4_WIDTH = 210;
    const A4_HEIGHT = 297;
    
    // Zone dimensions (A3 divided into 4 A4 zones)
    const ZONE_WIDTH = 210;   // A3_WIDTH / 2
    const ZONE_HEIGHT = 148.5; // A3_HEIGHT / 2
    
    // Standard margins
    const MARGIN = 10;
    const INNER_MARGIN = 5;
    
    // Font sizes
    const FONT_TITLE = 14;
    const FONT_SUBTITLE = 12;
    const FONT_NORMAL = 10;
    const FONT_SMALL = 8;
    const FONT_TINY = 7;

    /**
     * Get zone coordinates for A3 layout
     */
    public static function getZoneCoordinates($zone_number)
    {
        switch ($zone_number) {
            case 1: // Prednja strana (top-left)
                return [
                    'x' => self::MARGIN,
                    'y' => self::MARGIN,
                    'width' => self::ZONE_WIDTH - (2 * self::MARGIN),
                    'height' => self::ZONE_HEIGHT - (2 * self::MARGIN)
                ];
                
            case 2: // Unutrašnja lijeva (bottom-left)
                return [
                    'x' => self::MARGIN,
                    'y' => self::ZONE_HEIGHT + self::MARGIN,
                    'width' => self::ZONE_WIDTH - (2 * self::MARGIN),
                    'height' => self::ZONE_HEIGHT - (2 * self::MARGIN)
                ];
                
            case 3: // Unutrašnja desna (bottom-right)
                return [
                    'x' => self::ZONE_WIDTH + self::MARGIN,
                    'y' => self::ZONE_HEIGHT + self::MARGIN,
                    'width' => self::ZONE_WIDTH - (2 * self::MARGIN),
                    'height' => self::ZONE_HEIGHT - (2 * self::MARGIN)
                ];
                
            case 4: // Zadnja strana (top-right)
                return [
                    'x' => self::ZONE_WIDTH + self::MARGIN,
                    'y' => self::MARGIN,
                    'width' => self::ZONE_WIDTH - (2 * self::MARGIN),
                    'height' => self::ZONE_HEIGHT - (2 * self::MARGIN)
                ];
                
            default:
                throw new InvalidArgumentException("Invalid zone number: $zone_number");
        }
    }

    /**
     * Calculate table layout for document lists
     */
    public static function calculateTableLayout($available_width, $column_count = 5)
    {
        // Standard column widths for document table
        $column_ratios = [
            0.08,  // R.br. (8%)
            0.42,  // Naziv dokumenta (42%)
            0.15,  // Datum (15%)
            0.15,  // Veličina (15%)
            0.20   // Digitalni potpis (20%)
        ];
        
        $widths = [];
        foreach ($column_ratios as $ratio) {
            $widths[] = $available_width * $ratio;
        }
        
        return [
            'widths' => $widths,
            'total_width' => $available_width,
            'row_height' => 5,
            'header_height' => 6
        ];
    }

    /**
     * Calculate how many documents fit in each zone
     */
    public static function calculateDocumentDistribution($total_documents, $available_height_per_zone)
    {
        $row_height = 5;
        $header_height = 15; // Space for headers and titles
        
        $usable_height = $available_height_per_zone - $header_height;
        $rows_per_zone = floor($usable_height / $row_height);
        
        // Zone 2 and 3 are for documents
        $total_rows_available = $rows_per_zone * 2;
        
        if ($total_documents <= $total_rows_available) {
            // All documents fit
            $zone2_docs = min($total_documents, $rows_per_zone);
            $zone3_docs = max(0, $total_documents - $rows_per_zone);
        } else {
            // Need to truncate
            $zone2_docs = $rows_per_zone;
            $zone3_docs = $rows_per_zone;
        }
        
        return [
            'zone2_count' => $zone2_docs,
            'zone3_count' => $zone3_docs,
            'total_displayed' => $zone2_docs + $zone3_docs,
            'overflow_count' => max(0, $total_documents - ($zone2_docs + $zone3_docs)),
            'rows_per_zone' => $rows_per_zone
        ];
    }

    /**
     * Get barcode positioning according to Croatian standards
     */
    public static function getBarcodePosition($zone_coords)
    {
        // Position in top-right corner of zone 1 (prednja strana)
        // According to Croatian archival standards
        
        return [
            'code128' => [
                'x' => $zone_coords['x'] + $zone_coords['width'] - 60,
                'y' => $zone_coords['y'] + 5,
                'width' => 55,
                'height' => 12
            ],
            'qr' => [
                'x' => $zone_coords['x'] + $zone_coords['width'] - 30,
                'y' => $zone_coords['y'] + 20,
                'size' => 25
            ]
        ];
    }

    /**
     * Get signature box layout for zone 4
     */
    public static function getSignatureLayout($zone_coords)
    {
        $signature_area_height = 40;
        $signature_y = $zone_coords['y'] + $zone_coords['height'] - $signature_area_height - 10;
        
        return [
            'area_y' => $signature_y,
            'area_height' => $signature_area_height,
            'boxes' => [
                'kreirao' => [
                    'x' => $zone_coords['x'] + self::INNER_MARGIN,
                    'y' => $signature_y,
                    'width' => ($zone_coords['width'] / 2) - self::INNER_MARGIN,
                    'height' => 20
                ],
                'odobrio' => [
                    'x' => $zone_coords['x'] + ($zone_coords['width'] / 2),
                    'y' => $signature_y,
                    'width' => ($zone_coords['width'] / 2) - self::INNER_MARGIN,
                    'height' => 20
                ],
                'arhivirao' => [
                    'x' => $zone_coords['x'] + self::INNER_MARGIN,
                    'y' => $signature_y + 22,
                    'width' => $zone_coords['width'] - (2 * self::INNER_MARGIN),
                    'height' => 15
                ]
            ]
        ];
    }

    /**
     * Validate A3 layout dimensions
     */
    public static function validateLayout()
    {
        $errors = [];
        
        // Check if zones fit within A3
        if ((self::ZONE_WIDTH * 2) > self::A3_WIDTH) {
            $errors[] = "Zone width exceeds A3 width";
        }
        
        if ((self::ZONE_HEIGHT * 2) > self::A3_HEIGHT) {
            $errors[] = "Zone height exceeds A3 height";
        }
        
        // Check margins
        if (self::MARGIN * 4 > self::ZONE_WIDTH) {
            $errors[] = "Margins too large for zone width";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'dimensions' => [
                'a3' => ['width' => self::A3_WIDTH, 'height' => self::A3_HEIGHT],
                'zone' => ['width' => self::ZONE_WIDTH, 'height' => self::ZONE_HEIGHT],
                'margins' => self::MARGIN
            ]
        ];
    }

    /**
     * Generate fold marks for printing
     */
    public static function getFoldMarks()
    {
        return [
            'vertical' => [
                'x' => self::ZONE_WIDTH,
                'y1' => 0,
                'y2' => self::A3_HEIGHT,
                'style' => 'dashed'
            ],
            'horizontal' => [
                'x1' => 0,
                'x2' => self::A3_WIDTH,
                'y' => self::ZONE_HEIGHT,
                'style' => 'dashed'
            ]
        ];
    }

    /**
     * Calculate optimal text positioning
     */
    public static function calculateTextPosition($zone_coords, $section, $line_number = 0)
    {
        $positions = [
            'title' => [
                'x' => $zone_coords['x'] + self::INNER_MARGIN,
                'y' => $zone_coords['y'] + self::INNER_MARGIN,
                'align' => 'C'
            ],
            'subtitle' => [
                'x' => $zone_coords['x'] + self::INNER_MARGIN,
                'y' => $zone_coords['y'] + self::INNER_MARGIN + 12,
                'align' => 'C'
            ],
            'content' => [
                'x' => $zone_coords['x'] + self::INNER_MARGIN,
                'y' => $zone_coords['y'] + self::INNER_MARGIN + 25 + ($line_number * 6),
                'align' => 'L'
            ],
            'footer' => [
                'x' => $zone_coords['x'] + self::INNER_MARGIN,
                'y' => $zone_coords['y'] + $zone_coords['height'] - 15,
                'align' => 'C'
            ]
        ];
        
        return $positions[$section] ?? $positions['content'];
    }

    /**
     * Generate print instructions for users
     */
    public static function getPrintInstructions()
    {
        return [
            'hr' => [
                'title' => 'Upute za ispis omota spisa',
                'steps' => [
                    '1. Postavite pisač na A3 format papira',
                    '2. Odaberite landscape (vodoravnu) orijentaciju',
                    '3. Postavite margine na minimum (5mm)',
                    '4. Ispisujte u stvarnoj veličini (100%)',
                    '5. Nakon ispisa, preklopite po isprekidanim linijama',
                    '6. Rezultat: A4 omot s 4 stranice'
                ],
                'notes' => [
                    'Koristite kvalitetan papir (min. 80g/m²)',
                    'Provjerite da su barkodovi čitljivi',
                    'Čuvajte originalnu PDF verziju u ECM sustavu'
                ]
            ],
            'en' => [
                'title' => 'Cover Sheet Printing Instructions',
                'steps' => [
                    '1. Set printer to A3 paper format',
                    '2. Select landscape orientation',
                    '3. Set margins to minimum (5mm)',
                    '4. Print at actual size (100%)',
                    '5. After printing, fold along dashed lines',
                    '6. Result: A4 cover with 4 pages'
                ]
            ]
        ];
    }
}