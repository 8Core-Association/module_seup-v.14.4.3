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
 * Omot Auto Updater Class for SEUP Module
 * Handles automatic regeneration of cover sheets when predmet data changes
 */
class Omot_Auto_Updater
{
    /**
     * Auto-generate omot when document is uploaded
     */
    public static function onDocumentUpload($db, $conf, $user, $langs, $predmet_id, $document_info)
    {
        try {
            // Check if auto-generation is enabled
            if (!getDolGlobalString('SEUP_OMOT_AUTO_GENERATE', '1')) {
                return ['success' => true, 'message' => 'Auto-generation disabled'];
            }

            dol_syslog("Auto-generating omot for predmet $predmet_id after document upload", LOG_INFO);
            
            require_once __DIR__ . '/omot_spisa_generator.class.php';
            $generator = new Omot_Spisa_Generator($db, $conf, $user, $langs);
            
            $filepath = $generator->generateOmotSpisa($predmet_id, 'F');
            
            if ($filepath) {
                // Store omot in ECM
                $result = self::storeOmotInECM($db, $conf, $user, $predmet_id, $filepath);
                
                return [
                    'success' => true,
                    'message' => 'Omot automatically regenerated',
                    'filepath' => $filepath,
                    'ecm_stored' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to generate omot'
                ];
            }

        } catch (Exception $e) {
            dol_syslog("Error in auto omot generation: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Auto-generate omot when predmet is archived
     */
    public static function onPredmetArchive($db, $conf, $user, $langs, $predmet_id, $archive_reason = '')
    {
        try {
            dol_syslog("Generating final omot for archived predmet $predmet_id", LOG_INFO);
            
            require_once __DIR__ . '/omot_spisa_generator.class.php';
            $generator = new Omot_Spisa_Generator($db, $conf, $user, $langs);
            
            // Generate final version with archive info
            $filepath = $generator->generateOmotSpisa($predmet_id, 'F');
            
            if ($filepath) {
                // Move to archive folder
                $archiveFilepath = self::moveOmotToArchive($predmet_id, $filepath, $archive_reason);
                
                return [
                    'success' => true,
                    'message' => 'Final omot generated for archive',
                    'archive_filepath' => $archiveFilepath
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to generate final omot'
            ];

        } catch (Exception $e) {
            dol_syslog("Error generating archive omot: " . $e->getMessage(), LOG_ERR);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Store generated omot in ECM system
     */
    private static function storeOmotInECM($db, $conf, $user, $predmet_id, $filepath)
    {
        try {
            require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
            require_once __DIR__ . '/predmet_helper.class.php';
            
            $filename = basename($filepath);
            $relative_path = Predmet_helper::getPredmetFolderPath($predmet_id, $db);
            
            // Create ECM record
            $ecmfile = new EcmFiles($db);
            $ecmfile->filepath = rtrim($relative_path, '/');
            $ecmfile->filename = $filename;
            $ecmfile->label = 'Omot spisa - ' . date('d.m.Y H:i');
            $ecmfile->entity = $conf->entity;
            $ecmfile->gen_or_uploaded = 'generated';
            $ecmfile->description = 'Automatski generirani omot spisa';
            $ecmfile->fk_user_c = $user->id;
            $ecmfile->fk_user_m = $user->id;
            $ecmfile->date_c = dol_now();
            $ecmfile->date_m = dol_now();
            
            // Move file to ECM directory
            $ecm_filepath = DOL_DATA_ROOT . '/ecm/' . $relative_path . $filename;
            if (!is_dir(dirname($ecm_filepath))) {
                dol_mkdir(dirname($ecm_filepath));
            }
            
            if (rename($filepath, $ecm_filepath)) {
                $result = $ecmfile->create($user);
                if ($result > 0) {
                    dol_syslog("Omot stored in ECM: $filename", LOG_INFO);
                    return true;
                } else {
                    dol_syslog("Failed to create ECM record: " . $ecmfile->error, LOG_ERR);
                }
            } else {
                dol_syslog("Failed to move omot to ECM directory", LOG_ERR);
            }

            return false;

        } catch (Exception $e) {
            dol_syslog("Error storing omot in ECM: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }

    /**
     * Move omot to archive folder
     */
    private static function moveOmotToArchive($predmet_id, $filepath, $archive_reason)
    {
        try {
            $filename = basename($filepath);
            $archive_dir = DOL_DATA_ROOT . '/ecm/SEUP/Arhiva/Omoti/' . date('Y') . '/';
            
            if (!is_dir($archive_dir)) {
                dol_mkdir($archive_dir);
            }
            
            // Add archive suffix to filename
            $pathInfo = pathinfo($filename);
            $archiveFilename = $pathInfo['filename'] . '_ARHIVIRAN_' . date('Ymd_His') . '.' . $pathInfo['extension'];
            $archiveFilepath = $archive_dir . $archiveFilename;
            
            if (rename($filepath, $archiveFilepath)) {
                dol_syslog("Omot moved to archive: $archiveFilename", LOG_INFO);
                return $archiveFilepath;
            } else {
                dol_syslog("Failed to move omot to archive", LOG_ERR);
                return false;
            }

        } catch (Exception $e) {
            dol_syslog("Error moving omot to archive: " . $e->getMessage(), LOG_ERR);
            return false;
        }
    }

    /**
     * Clean old omot versions (keep only latest 5)
     */
    public static function cleanOldOmoti($db, $conf, $predmet_id = null)
    {
        try {
            $temp_dir = DOL_DATA_ROOT . '/ecm/temp/';
            $pattern = $predmet_id ? "omot_spisa_*{$predmet_id}*.pdf" : "omot_spisa_*.pdf";
            
            $files = glob($temp_dir . $pattern);
            
            if (count($files) > 5) {
                // Sort by modification time (newest first)
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                // Keep only latest 5, delete the rest
                $filesToDelete = array_slice($files, 5);
                $deletedCount = 0;
                
                foreach ($filesToDelete as $file) {
                    if (unlink($file)) {
                        $deletedCount++;
                        dol_syslog("Deleted old omot: " . basename($file), LOG_INFO);
                    }
                }
                
                return [
                    'success' => true,
                    'deleted_count' => $deletedCount,
                    'message' => "Cleaned $deletedCount old omot files"
                ];
            }
            
            return [
                'success' => true,
                'deleted_count' => 0,
                'message' => 'No cleanup needed'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Schedule omot regeneration for all predmeti (background job)
     */
    public static function scheduleOmotRegeneration($db, $conf, $user, $langs, $limit = 10)
    {
        try {
            // Get predmeti that need omot regeneration
            $sql = "SELECT p.ID_predmeta 
                    FROM " . MAIN_DB_PREFIX . "a_predmet p
                    WHERE p.ID_predmeta NOT IN (
                        SELECT ID_predmeta FROM " . MAIN_DB_PREFIX . "a_arhiva WHERE status_arhive = 'active'
                    )
                    AND p.ID_predmeta NOT IN (
                        SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(ef.filename, '_', -2), '_', 1) 
                        FROM " . MAIN_DB_PREFIX . "ecm_files ef 
                        WHERE ef.filename LIKE 'omot_spisa_%'
                        AND ef.date_c > DATE_SUB(NOW(), INTERVAL 1 DAY)
                    )
                    LIMIT " . (int)$limit;

            $resql = $db->query($sql);
            $predmeti = [];
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    $predmeti[] = $obj->ID_predmeta;
                }
            }

            $generated = 0;
            $errors = [];

            foreach ($predmeti as $predmet_id) {
                $result = self::onDocumentUpload($db, $conf, $user, $langs, $predmet_id, []);
                if ($result['success']) {
                    $generated++;
                } else {
                    $errors[] = "Predmet $predmet_id: " . $result['error'];
                }
                
                // Small delay to prevent server overload
                usleep(200000); // 0.2 seconds
            }

            return [
                'success' => true,
                'processed' => count($predmeti),
                'generated' => $generated,
                'errors' => $errors,
                'message' => "Processed " . count($predmeti) . " predmeti, generated $generated omoti"
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get omot file for predmet (latest version)
     */
    public static function getLatestOmotForPredmet($db, $predmet_id)
    {
        try {
            require_once __DIR__ . '/predmet_helper.class.php';
            $relative_path = Predmet_helper::getPredmetFolderPath($predmet_id, $db);
            
            // Search in ECM
            $sql = "SELECT ef.filename, ef.filepath, ef.date_c
                    FROM " . MAIN_DB_PREFIX . "ecm_files ef
                    WHERE ef.filepath = '" . $db->escape(rtrim($relative_path, '/')) . "'
                    AND ef.filename LIKE 'omot_spisa_%'
                    ORDER BY ef.date_c DESC
                    LIMIT 1";

            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                $full_path = DOL_DATA_ROOT . '/ecm/' . $obj->filepath . '/' . $obj->filename;
                if (file_exists($full_path)) {
                    return [
                        'filename' => $obj->filename,
                        'filepath' => $full_path,
                        'date_created' => $obj->date_c,
                        'download_url' => DOL_URL_ROOT . '/document.php?modulepart=ecm&file=' . 
                                        urlencode($relative_path . $obj->filename)
                    ];
                }
            }

            return null;

        } catch (Exception $e) {
            dol_syslog("Error getting latest omot: " . $e->getMessage(), LOG_ERR);
            return null;
        }
    }

    /**
     * Check if omot needs regeneration
     */
    public static function needsRegeneration($db, $predmet_id)
    {
        try {
            $latestOmot = self::getLatestOmotForPredmet($db, $predmet_id);
            
            if (!$latestOmot) {
                return true; // No omot exists
            }

            // Check if any documents were added after omot generation
            require_once __DIR__ . '/predmet_helper.class.php';
            $relative_path = Predmet_helper::getPredmetFolderPath($predmet_id, $db);
            
            $sql = "SELECT COUNT(*) as count
                    FROM " . MAIN_DB_PREFIX . "ecm_files ef
                    WHERE ef.filepath = '" . $db->escape(rtrim($relative_path, '/')) . "'
                    AND ef.filename NOT LIKE 'omot_spisa_%'
                    AND ef.date_c > '" . $db->escape(date('Y-m-d H:i:s', $latestOmot['date_created'])) . "'";

            $resql = $db->query($sql);
            if ($resql && $obj = $db->fetch_object($resql)) {
                return $obj->count > 0;
            }

            return false;

        } catch (Exception $e) {
            dol_syslog("Error checking omot regeneration need: " . $e->getMessage(), LOG_ERR);
            return true; // Assume regeneration needed on error
        }
    }

    /**
     * Bulk regenerate omoti for all predmeti that need it
     */
    public static function bulkRegenerateOmoti($db, $conf, $user, $langs, $limit = 20)
    {
        try {
            // Get all active predmeti
            $sql = "SELECT ID_predmeta FROM " . MAIN_DB_PREFIX . "a_predmet 
                    WHERE ID_predmeta NOT IN (
                        SELECT ID_predmeta FROM " . MAIN_DB_PREFIX . "a_arhiva 
                        WHERE status_arhive = 'active'
                    )
                    LIMIT " . (int)$limit;

            $resql = $db->query($sql);
            $predmeti = [];
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    if (self::needsRegeneration($db, $obj->ID_predmeta)) {
                        $predmeti[] = $obj->ID_predmeta;
                    }
                }
            }

            if (empty($predmeti)) {
                return [
                    'success' => true,
                    'message' => 'No omoti need regeneration',
                    'processed' => 0
                ];
            }

            // Use batch generator
            require_once __DIR__ . '/omot_spisa_generator.class.php';
            $results = Omot_Spisa_Generator::batchGenerateOmoti($db, $conf, $user, $langs, $predmeti);
            
            $successCount = count(array_filter($results, function($r) { return $r['success']; }));
            $errorCount = count($results) - $successCount;

            return [
                'success' => true,
                'processed' => count($results),
                'generated' => $successCount,
                'errors' => $errorCount,
                'message' => "Bulk regeneration: $successCount generated, $errorCount errors",
                'details' => $results
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Set up automatic omot generation hooks
     */
    public static function setupHooks($db, $conf)
    {
        try {
            // This would integrate with Dolibarr's hook system
            // For now, we'll use direct calls from upload handlers
            
            dol_syslog("Omot auto-updater hooks configured", LOG_INFO);
            
            return [
                'success' => true,
                'message' => 'Hooks configured for automatic omot generation'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get omot generation queue status
     */
    public static function getQueueStatus($db, $conf)
    {
        try {
            // Count predmeti that need omot regeneration
            $sql = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "a_predmet p
                    WHERE p.ID_predmeta NOT IN (
                        SELECT ID_predmeta FROM " . MAIN_DB_PREFIX . "a_arhiva 
                        WHERE status_arhive = 'active'
                    )";

            $resql = $db->query($sql);
            $totalPredmeti = 0;
            if ($resql && $obj = $db->fetch_object($resql)) {
                $totalPredmeti = $obj->count;
            }

            // Count existing omoti
            $sql = "SELECT COUNT(DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(ef.filename, '_', 4), '_', -1)) as count
                    FROM " . MAIN_DB_PREFIX . "ecm_files ef
                    WHERE ef.filename LIKE 'omot_spisa_%'
                    AND ef.filepath LIKE 'SEUP/Predmeti/%'";

            $resql = $db->query($sql);
            $existingOmoti = 0;
            if ($resql && $obj = $db->fetch_object($resql)) {
                $existingOmoti = $obj->count;
            }

            $needsGeneration = $totalPredmeti - $existingOmoti;

            return [
                'total_predmeti' => $totalPredmeti,
                'existing_omoti' => $existingOmoti,
                'needs_generation' => max(0, $needsGeneration),
                'completion_percentage' => $totalPredmeti > 0 ? round(($existingOmoti / $totalPredmeti) * 100, 1) : 0
            ];

        } catch (Exception $e) {
            dol_syslog("Error getting queue status: " . $e->getMessage(), LOG_ERR);
            return [
                'total_predmeti' => 0,
                'existing_omoti' => 0,
                'needs_generation' => 0,
                'completion_percentage' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate omot file integrity
     */
    public static function validateOmotFile($filepath)
    {
        try {
            if (!file_exists($filepath)) {
                return ['valid' => false, 'error' => 'File does not exist'];
            }

            // Check if it's a valid PDF
            $handle = fopen($filepath, 'rb');
            $header = fread($handle, 8);
            fclose($handle);

            if (strpos($header, '%PDF-') !== 0) {
                return ['valid' => false, 'error' => 'Not a valid PDF file'];
            }

            // Check file size (should be reasonable for omot)
            $filesize = filesize($filepath);
            if ($filesize < 1024 || $filesize > 10 * 1024 * 1024) { // 1KB - 10MB
                return ['valid' => false, 'error' => 'Invalid file size'];
            }

            return [
                'valid' => true,
                'filesize' => $filesize,
                'created' => date('Y-m-d H:i:s', filemtime($filepath))
            ];

        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}