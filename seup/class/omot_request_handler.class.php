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
 * Omot Request Handler Class for SEUP Module
 * Handles AJAX requests related to cover sheet operations
 */
class Omot_Request_Handler
{
    /**
     * Handle omot generation request
     */
    public static function handleGenerateOmot($db, $conf, $user, $langs)
    {
        header('Content-Type: application/json');
        ob_end_clean();
        
        try {
            $predmet_id = GETPOST('predmet_id', 'int');
            $output_type = GETPOST('output_type', 'alpha') ?: 'F';
            
            if (!$predmet_id) {
                throw new Exception('Missing predmet ID');
            }
            
            require_once __DIR__ . '/omot_spisa_generator.class.php';
            $generator = new Omot_Spisa_Generator($db, $conf, $user, $langs);
            
            $result = $generator->generateOmotSpisa($predmet_id, $output_type);
            
            if ($result) {
                if ($output_type === 'F') {
                    // File saved, create download URL
                    $filename = basename($result);
                    $download_url = DOL_URL_ROOT . '/custom/seup/class/download_temp_pdf.php?file=' . urlencode($filename);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Omot spisa uspješno generiran',
                        'filepath' => $result,
                        'filename' => $filename,
                        'download_url' => $download_url
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Omot spisa uspješno generiran'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Greška pri generiranju omota'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Handle batch omot generation
     */
    public static function handleBatchGenerate($db, $conf, $user, $langs)
    {
        header('Content-Type: application/json');
        ob_end_clean();
        
        try {
            $predmet_ids = GETPOST('predmet_ids', 'array');
            $limit = GETPOST('limit', 'int') ?: 10;
            
            if (empty($predmet_ids)) {
                // Get all active predmeti if none specified
                $sql = "SELECT ID_predmeta FROM " . MAIN_DB_PREFIX . "a_predmet 
                        WHERE ID_predmeta NOT IN (
                            SELECT ID_predmeta FROM " . MAIN_DB_PREFIX . "a_arhiva 
                            WHERE status_arhive = 'active'
                        )
                        LIMIT " . (int)$limit;
                
                $resql = $db->query($sql);
                $predmet_ids = [];
                if ($resql) {
                    while ($obj = $db->fetch_object($resql)) {
                        $predmet_ids[] = $obj->ID_predmeta;
                    }
                }
            }
            
            if (empty($predmet_ids)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No predmeti found for batch generation'
                ]);
                exit;
            }
            
            require_once __DIR__ . '/omot_spisa_generator.class.php';
            $results = Omot_Spisa_Generator::batchGenerateOmoti($db, $conf, $user, $langs, $predmet_ids);
            
            $successCount = count(array_filter($results, function($r) { return $r['success']; }));
            $errorCount = count($results) - $successCount;
            
            echo json_encode([
                'success' => true,
                'processed' => count($results),
                'generated' => $successCount,
                'errors' => $errorCount,
                'message' => "Batch generation completed: $successCount generated, $errorCount errors",
                'details' => $results
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Handle omot preview request
     */
    public static function handlePreviewOmot($db, $conf, $user, $langs)
    {
        header('Content-Type: application/json');
        ob_end_clean();
        
        try {
            $predmet_id = GETPOST('predmet_id', 'int');
            
            if (!$predmet_id) {
                throw new Exception('Missing predmet ID');
            }
            
            require_once __DIR__ . '/omot_spisa_generator.class.php';
            $generator = new Omot_Spisa_Generator($db, $conf, $user, $langs);
            
            // Generate preview (inline browser display)
            $result = $generator->generateOmotSpisa($predmet_id, 'I');
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Preview generated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to generate preview'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Handle omot statistics request
     */
    public static function handleOmotStatistics($db, $conf)
    {
        // Clean all output buffers first
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        
        try {
            require_once __DIR__ . '/omot_spisa_generator.class.php';
            require_once __DIR__ . '/omot_auto_updater.class.php';
            
            $stats = Omot_Spisa_Generator::getOmotStatistics($db, $conf);
            $queueStatus = Omot_Auto_Updater::getQueueStatus($db, $conf);
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats,
                'queue_status' => $queueStatus
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Handle cleanup request
     */
    public static function handleCleanupOmoti($db, $conf)
    {
        header('Content-Type: application/json');
        ob_end_clean();
        
        try {
            require_once __DIR__ . '/omot_helper.class.php';
            require_once __DIR__ . '/omot_auto_updater.class.php';
            
            $hours = GETPOST('hours', 'int') ?: 24;
            
            // Clean temporary files
            $tempResult = Omot_Helper::cleanupTempFiles($hours);
            
            // Clean old versions
            $oldResult = Omot_Auto_Updater::cleanOldOmoti($db, $conf);
            
            echo json_encode([
                'success' => true,
                'temp_cleanup' => $tempResult,
                'old_cleanup' => $oldResult,
                'message' => 'Cleanup completed successfully'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Handle auto-generation settings update
     */
    public static function handleUpdateAutoSettings($db, $conf)
    {
        header('Content-Type: application/json');
        ob_end_clean();
        
        try {
            $auto_generate = GETPOST('auto_generate', 'int') ? '1' : '0';
            $auto_archive = GETPOST('auto_archive', 'int') ? '1' : '0';
            $cleanup_enabled = GETPOST('cleanup_enabled', 'int') ? '1' : '0';
            
            // Update configuration
            $result1 = dolibarr_set_const($db, 'SEUP_OMOT_AUTO_GENERATE', $auto_generate, 'chaine', 0, '', $conf->entity);
            $result2 = dolibarr_set_const($db, 'SEUP_OMOT_AUTO_ARCHIVE', $auto_archive, 'chaine', 0, '', $conf->entity);
            $result3 = dolibarr_set_const($db, 'SEUP_OMOT_CLEANUP_ENABLED', $cleanup_enabled, 'chaine', 0, '', $conf->entity);
            
            if ($result1 && $result2 && $result3) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Auto-generation settings updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update settings'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Handle omot validation request
     */
    public static function handleValidateOmot($db, $conf)
    {
        header('Content-Type: application/json');
        ob_end_clean();
        
        try {
            $filepath = GETPOST('filepath', 'alpha');
            
            if (empty($filepath)) {
                throw new Exception('Missing filepath');
            }
            
            require_once __DIR__ . '/omot_auto_updater.class.php';
            $validation = Omot_Auto_Updater::validateOmotFile($filepath);
            
            echo json_encode([
                'success' => true,
                'validation' => $validation
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Handle layout validation request
     */
    public static function handleValidateLayout()
    {
        header('Content-Type: application/json');
        ob_end_clean();
        
        try {
            require_once __DIR__ . '/omot_layout_helper.class.php';
            $validation = Omot_Layout_Helper::validateLayout();
            
            echo json_encode([
                'success' => true,
                'validation' => $validation
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}