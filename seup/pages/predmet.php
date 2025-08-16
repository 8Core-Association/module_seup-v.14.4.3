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
 *	\file       seup/predmet.php
 *	\ingroup    seup
 *	\brief      Individual case view with documents and cover sheet generation
 */

// Učitaj Dolibarr okruženje
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

// Local classes
require_once __DIR__ . '/../class/predmet_helper.class.php';
require_once __DIR__ . '/../class/request_handler.class.php';
require_once __DIR__ . '/../class/omot_spisa_generator.class.php';
require_once __DIR__ . '/../class/omot_auto_updater.class.php';
require_once __DIR__ . '/../class/omot_helper.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Get predmet ID
$caseId = GETPOST('id', 'int');
if (!$caseId) {
    header('Location: predmeti.php');
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = GETPOST('action', 'alpha');
    
    // Handle document upload
    if ($action === 'upload_document') {
        Request_Handler::handleUploadDocument($db, '', $langs, $conf, $user);
        
        // Auto-generate omot after upload
        $omotResult = Omot_Auto_Updater::onDocumentUpload($db, $conf, $user, $langs, $caseId, []);
        if ($omotResult['success']) {
            setEventMessages('Dokument uploadovan i omot ažuriran', null, 'mesgs');
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $caseId);
        exit;
    }
    
    // Handle omot generation
    if ($action === 'generate_omot') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        try {
            $generator = new Omot_Spisa_Generator($db, $conf, $user, $langs);
            $filepath = $generator->generateOmotSpisa($caseId, 'F');
            
            if ($filepath) {
                // Create download URL
                $filename = basename($filepath);
                $download_url = DOL_URL_ROOT . '/custom/seup/class/download_temp_pdf.php?file=' . urlencode($filename);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Omot spisa uspješno generiran',
                    'download_url' => $download_url,
                    'filename' => $filename
                ]);
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
    
    // Handle document deletion
    if ($action === 'delete_document') {
        header('Content-Type: application/json');
        ob_end_clean();
        
        $filename = GETPOST('filename', 'alpha');
        $filepath = GETPOST('filepath', 'alpha');
        
        if (empty($filename) || empty($filepath)) {
            echo json_encode(['success' => false, 'error' => 'Missing filename or filepath']);
            exit;
        }
        
        try {
            // Delete from filesystem
            $full_path = DOL_DATA_ROOT . '/ecm/' . rtrim($filepath, '/') . '/' . $filename;
            $file_deleted = false;
            
            if (file_exists($full_path)) {
                $file_deleted = unlink($full_path);
            }
            
            // Delete from ECM database
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ecm_files 
                    WHERE filepath = '" . $db->escape(rtrim($filepath, '/')) . "'
                    AND filename = '" . $db->escape($filename) . "'
                    AND entity = " . $conf->entity;
            
            $db_deleted = $db->query($sql);
            
            if ($file_deleted && $db_deleted) {
                // Auto-regenerate omot after deletion
                $omotResult = Omot_Auto_Updater::onDocumentUpload($db, $conf, $user, $langs, $caseId, []);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Dokument uspješno obrisan',
                    'omot_updated' => $omotResult['success']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Greška pri brisanju dokumenta'
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
}

// Fetch predmet details
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
            ko.opis_klasifikacijske_oznake
        FROM " . MAIN_DB_PREFIX . "a_predmet p
        LEFT JOIN " . MAIN_DB_PREFIX . "a_oznaka_ustanove u ON p.ID_ustanove = u.ID_ustanove
        LEFT JOIN " . MAIN_DB_PREFIX . "a_interna_oznaka_korisnika k ON p.ID_interna_oznaka_korisnika = k.ID
        LEFT JOIN " . MAIN_DB_PREFIX . "a_klasifikacijska_oznaka ko ON p.ID_klasifikacijske_oznake = ko.ID_klasifikacijske_oznake
        WHERE p.ID_predmeta = " . (int)$caseId;

$resql = $db->query($sql);
$predmet = null;
if ($resql && $obj = $db->fetch_object($resql)) {
    $predmet = $obj;
} else {
    header('Location: predmeti.php');
    exit;
}

// Build klasa string
$klasa = $predmet->klasa_br . '-' . $predmet->sadrzaj . '/' .
         $predmet->godina . '-' . $predmet->dosje_broj . '/' .
         $predmet->predmet_rbr;

// Fetch uploaded documents
$documentTableHTML = '';
Predmet_helper::fetchUploadedDocuments($db, $conf, $documentTableHTML, $langs, $caseId);

// Check if omot exists and needs regeneration
$latestOmot = Omot_Auto_Updater::getLatestOmotForPredmet($db, $caseId);
$needsRegeneration = Omot_Auto_Updater::needsRegeneration($db, $caseId);

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", "Predmet: " . $klasa, '', '', 0, 0, '', '', '', 'mod-seup page-predmet');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
print '<link href="/custom/seup/css/predmet.css" rel="stylesheet">';
print '<link href="/custom/seup/css/prilozi.css" rel="stylesheet">';
print '<link href="/custom/seup/css/omot-spisa.css" rel="stylesheet">';

// Main container
print '<div class="seup-predmet-container">';

// Case details header
print '<div class="seup-case-details">';
print '<div class="seup-case-header">';
print '<div class="seup-case-icon"><i class="fas fa-folder-open"></i></div>';
print '<div class="seup-case-title">';
print '<h4>' . htmlspecialchars($predmet->naziv_predmeta) . '</h4>';
print '<div class="seup-case-klasa">' . $klasa . '</div>';
print '</div>';
print '</div>';

print '<div class="seup-case-grid">';
print '<div class="seup-case-field">';
print '<div class="seup-case-field-label"><i class="fas fa-building"></i>Ustanova</div>';
print '<div class="seup-case-field-value">' . ($predmet->name_ustanova ?: 'N/A') . '</div>';
print '</div>';

print '<div class="seup-case-field">';
print '<div class="seup-case-field-label"><i class="fas fa-user"></i>Zaposlenik</div>';
print '<div class="seup-case-field-value">' . ($predmet->ime_prezime ?: 'N/A') . '</div>';
print '</div>';

print '<div class="seup-case-field">';
print '<div class="seup-case-field-label"><i class="fas fa-calendar"></i>Datum otvaranja</div>';
print '<div class="seup-case-field-value">' . date('d.m.Y', strtotime($predmet->tstamp_created)) . '</div>';
print '</div>';

print '<div class="seup-case-field">';
print '<div class="seup-case-field-label"><i class="fas fa-clock"></i>Vrijeme čuvanja</div>';
print '<div class="seup-case-field-value">' . ($predmet->vrijeme_cuvanja == 0 ? 'Trajno' : $predmet->vrijeme_cuvanja . ' godina') . '</div>';
print '</div>';
print '</div>';
print '</div>';

// Tab navigation
print '<div class="seup-tabs">';
print '<button class="seup-tab active" data-tab="documents"><i class="fas fa-file-alt"></i>Dokumenti</button>';
print '<button class="seup-tab" data-tab="omot"><i class="fas fa-file-pdf"></i>Omot Spisa</button>';
print '<button class="seup-tab" data-tab="preview"><i class="fas fa-eye"></i>Pregled</button>';
print '<button class="seup-tab" data-tab="stats"><i class="fas fa-chart-bar"></i>Statistike</button>';
print '</div>';

// Tab content
print '<div class="seup-tab-content">';

// Documents tab
print '<div class="seup-tab-pane active" id="documents-tab">';
print '<div class="seup-documents-header">';
print '<h3 class="seup-documents-title"><i class="fas fa-file-alt"></i>Dokumenti u Prilozima</h3>';
print '<div class="seup-action-buttons">';
print '<button type="button" class="seup-action-btn seup-btn-primary" id="uploadBtn">';
print '<i class="fas fa-upload"></i> Upload Dokument';
print '</button>';
print '</div>';
print '</div>';

// Upload section
print '<div class="seup-upload-section" id="uploadSection" style="display: none;">';
print '<div class="seup-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>';
print '<div class="seup-upload-text">Odaberite dokument za upload</div>';
print '<form method="post" enctype="multipart/form-data" id="uploadForm">';
print '<input type="hidden" name="action" value="upload_document">';
print '<input type="hidden" name="case_id" value="' . $caseId . '">';
print '<input type="file" name="document" id="documentFile" accept=".pdf,.docx,.xlsx,.doc,.xls,.jpg,.jpeg,.png,.odt" required>';
print '<button type="submit" class="seup-btn seup-btn-success">Upload</button>';
print '</form>';
print '</div>';

// Documents table
print '<div class="seup-documents-list">';
print $documentTableHTML;
print '</div>';
print '</div>';

// Omot Spisa tab
print '<div class="seup-tab-pane" id="omot-tab">';
print '<div class="seup-omot-generator">';
print '<div class="seup-omot-header">';
print '<div class="seup-omot-header-content">';
print '<div class="seup-omot-icon"><i class="fas fa-file-pdf"></i></div>';
print '<div>';
print '<h3 class="seup-omot-title">Omot Spisa</h3>';
print '<p class="seup-omot-subtitle">A3 format s preklapanjem u 4 A4 sekcije</p>';
print '</div>';
print '</div>';
print '<div class="seup-omot-actions">';

if ($latestOmot) {
    print '<a href="' . $latestOmot['download_url'] . '" class="seup-btn seup-btn-secondary" target="_blank">';
    print '<i class="fas fa-download"></i> Preuzmi Postojeći';
    print '</a>';
}

if ($needsRegeneration) {
    print '<div class="seup-sync-alert">';
    print '<div class="seup-sync-content">';
    print '<i class="fas fa-exclamation-triangle seup-sync-icon"></i>';
    print '<div class="seup-sync-text">Omot nije ažuran - dodani su novi dokumenti</div>';
    print '</div>';
    print '</div>';
}

print '<button type="button" class="seup-btn seup-btn-generate" id="generateOmotBtn">';
print '<i class="fas fa-magic"></i> Generiraj Omot';
print '</button>';
print '</div>';
print '</div>';

// A3 Preview
print '<div class="seup-omot-preview">';
print '<div class="seup-a3-preview responsive">';

// Zone 1 - Prednja strana
print '<div class="seup-zone seup-zone-1">';
print '<div class="seup-zone-label">1. PREDNJA</div>';
print '<div class="seup-zone-content">';
print '<strong>' . htmlspecialchars($predmet->name_ustanova) . '</strong><br>';
print 'KLASA: ' . $klasa . '<br>';
print '<small>' . dol_trunc($predmet->naziv_predmeta, 30) . '</small><br>';
print '<div style="position: absolute; top: 5px; right: 5px; font-size: 8px;">BARKOD</div>';
print '</div>';
print '</div>';

// Zone 2 - Unutrašnja lijeva
print '<div class="seup-zone seup-zone-2">';
print '<div class="seup-zone-label">2. DOKUMENTI (1/2)</div>';
print '<div class="seup-zone-content">';
print '<strong>POPIS DOKUMENATA</strong><br>';
print '<small>R.br. | Naziv | Datum | Veličina</small><br>';
print '<small>1. | Dokument1.pdf | 15.01.25</small><br>';
print '<small>2. | Dokument2.docx | 16.01.25</small><br>';
print '<small>...</small>';
print '</div>';
print '</div>';

// Zone 3 - Unutrašnja desna
print '<div class="seup-zone seup-zone-3">';
print '<div class="seup-zone-label">3. DOKUMENTI (2/2)</div>';
print '<div class="seup-zone-content">';
print '<strong>POPIS (nastavak)</strong><br>';
print '<small>R.br. | Naziv | Datum | Potpis</small><br>';
print '<small>5. | Dokument5.pdf | POTPISAN</small><br>';
print '<small>6. | Dokument6.docx | NIJE PDF</small><br>';
print '<small>...</small>';
print '</div>';
print '</div>';

// Zone 4 - Zadnja strana
print '<div class="seup-zone seup-zone-4">';
print '<div class="seup-zone-label">4. ZADNJA</div>';
print '<div class="seup-zone-content">';
print '<strong>NAPOMENE</strong><br>';
print '<small>_________________</small><br><br>';
print '<strong>POTPISI</strong><br>';
print '<small>Kreirao: ' . htmlspecialchars($predmet->ime_prezime) . '</small><br>';
print '<small>Odobrio: _________</small>';
print '</div>';
print '</div>';

// Fold lines
print '<div class="seup-fold-line-v"></div>';
print '<div class="seup-fold-line-h"></div>';

print '</div>'; // seup-a3-preview

// Print instructions
print '<div class="seup-print-instructions">';
print '<h4 class="seup-instructions-title"><i class="fas fa-print"></i>Upute za ispis</h4>';
print '<ul class="seup-instructions-list">';
print '<li>Postavite pisač na A3 format papira</li>';
print '<li>Odaberite landscape (vodoravnu) orijentaciju</li>';
print '<li>Postavite margine na minimum (5mm)</li>';
print '<li>Preklopite po isprekidanim linijama nakon ispisa</li>';
print '</ul>';
print '</div>';

print '</div>'; // seup-omot-preview

// Generation progress
print '<div class="seup-generation-progress" id="generationProgress">';
print '<div class="seup-progress-header">';
print '<div class="seup-progress-icon"><i class="fas fa-cog fa-spin"></i></div>';
print '<div class="seup-progress-text">';
print '<h5 class="seup-progress-title">Generiranje omota u tijeku...</h5>';
print '<p class="seup-progress-description">Molimo pričekajte dok se kreira PDF dokument</p>';
print '</div>';
print '</div>';
print '<div class="seup-progress-bar">';
print '<div class="seup-progress-fill" id="progressFill"></div>';
print '</div>';
print '</div>';

// Generation result
print '<div class="seup-generation-result" id="generationResult">';
print '<span class="seup-result-icon"></span>';
print '<span class="seup-result-message"></span>';
print '</div>';

print '</div>'; // omot-tab

// Preview tab
print '<div class="seup-tab-pane" id="preview-tab">';
print '<div class="seup-preview-container">';
print '<div class="seup-preview-icon"><i class="fas fa-eye"></i></div>';
print '<h3 class="seup-preview-title">Pregled Predmeta</h3>';
print '<p class="seup-preview-description">Ovdje će biti implementiran pregled omota spisa i dokumenata</p>';
print '<button type="button" class="seup-btn seup-btn-primary">';
print '<i class="fas fa-expand"></i> Otvori u novom prozoru';
print '</button>';
print '</div>';
print '</div>';

// Stats tab
print '<div class="seup-tab-pane" id="stats-tab">';
print '<div class="seup-stats-container">';
print '<h3><i class="fas fa-chart-bar"></i> Statistike Predmeta</h3>';
print '<div class="seup-stats-grid">';

// Calculate real stats
$documents = Predmet_helper::getCombinedDocuments($db, $conf, $caseId);
$docStats = Omot_Helper::calculateDocumentStats($documents);

print '<div class="seup-stat-card">';
print '<div class="seup-stat-icon"><i class="fas fa-file-alt"></i></div>';
print '<div class="seup-stat-number">' . $docStats['total_count'] . '</div>';
print '<div class="seup-stat-label">Ukupno Dokumenata</div>';
print '</div>';

print '<div class="seup-stat-card">';
print '<div class="seup-stat-icon"><i class="fas fa-certificate"></i></div>';
print '<div class="seup-stat-number">' . $docStats['signed_count'] . '</div>';
print '<div class="seup-stat-label">Digitalno Potpisanih</div>';
print '</div>';

print '<div class="seup-stat-card">';
print '<div class="seup-stat-icon"><i class="fas fa-hdd"></i></div>';
print '<div class="seup-stat-number">' . Predmet_helper::formatFileSize($docStats['total_size']) . '</div>';
print '<div class="seup-stat-label">Ukupna Veličina</div>';
print '</div>';

print '<div class="seup-stat-card">';
print '<div class="seup-stat-icon"><i class="fas fa-calendar"></i></div>';
print '<div class="seup-stat-number">' . count($docStats['by_type']) . '</div>';
print '<div class="seup-stat-label">Tipova Datoteka</div>';
print '</div>';

print '</div>'; // seup-stats-grid
print '</div>'; // seup-stats-container
print '</div>'; // stats-tab

print '</div>'; // seup-tab-content
print '</div>'; // seup-predmet-container

// Delete Document Modal
print '<div class="seup-modal" id="deleteDocumentModal">';
print '<div class="seup-modal-content">';
print '<div class="seup-modal-header">';
print '<h5 class="seup-modal-title"><i class="fas fa-trash"></i> Brisanje Dokumenta</h5>';
print '<button type="button" class="seup-modal-close" id="closeDeleteModal">&times;</button>';
print '</div>';
print '<div class="seup-modal-body">';
print '<div class="seup-delete-doc-info">';
print '<div class="seup-delete-doc-icon"><i class="fas fa-file-alt"></i></div>';
print '<div class="seup-delete-doc-details">';
print '<div class="seup-delete-doc-name" id="deleteDocName">document.pdf</div>';
print '<div class="seup-delete-doc-warning">';
print '<i class="fas fa-exclamation-triangle"></i>';
print 'Dokument će biti trajno obrisan iz sustava. Ova akcija se ne može poništiti.';
print '</div>';
print '</div>';
print '</div>';
print '</div>';
print '<div class="seup-modal-footer">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="cancelDeleteBtn">Odustani</button>';
print '<button type="button" class="seup-btn seup-btn-danger" id="confirmDeleteBtn">';
print '<i class="fas fa-trash"></i> Obriši Dokument';
print '</button>';
print '</div>';
print '</div>';
print '</div>';

?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Tab functionality
    const tabs = document.querySelectorAll('.seup-tab');
    const tabPanes = document.querySelectorAll('.seup-tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Remove active class from all tabs and panes
            tabs.forEach(t => t.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding pane
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });

    // Upload functionality
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadSection = document.getElementById('uploadSection');
    const uploadForm = document.getElementById('uploadForm');

    if (uploadBtn && uploadSection) {
        uploadBtn.addEventListener('click', function() {
            uploadSection.style.display = uploadSection.style.display === 'none' ? 'block' : 'none';
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', function() {
            uploadBtn.classList.add('seup-loading');
        });
    }

    // Omot generation
    const generateOmotBtn = document.getElementById('generateOmotBtn');
    const generationProgress = document.getElementById('generationProgress');
    const generationResult = document.getElementById('generationResult');
    const progressFill = document.getElementById('progressFill');

    if (generateOmotBtn) {
        generateOmotBtn.addEventListener('click', function() {
            // Show progress
            generationProgress.style.display = 'block';
            generationResult.style.display = 'none';
            this.classList.add('seup-generating');
            
            // Simulate progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 20;
                if (progress > 90) progress = 90;
                progressFill.style.width = progress + '%';
            }, 200);

            // Generate omot
            const formData = new FormData();
            formData.append('action', 'generate_omot');
            formData.append('predmet_id', '<?php echo $caseId; ?>');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progressFill.style.width = '100%';
                
                setTimeout(() => {
                    generationProgress.style.display = 'none';
                    generationResult.style.display = 'block';
                    
                    if (data.success) {
                        generationResult.className = 'seup-generation-result success';
                        generationResult.innerHTML = `
                            <i class="fas fa-check-circle seup-result-icon"></i>
                            <span class="seup-result-message">${data.message}</span>
                            <a href="${data.download_url}" class="seup-download-link" target="_blank">
                                <i class="fas fa-download"></i> Preuzmi Omot (${data.filename})
                            </a>
                        `;
                    } else {
                        generationResult.className = 'seup-generation-result error';
                        generationResult.innerHTML = `
                            <i class="fas fa-exclamation-triangle seup-result-icon"></i>
                            <span class="seup-result-message">Greška: ${data.error}</span>
                        `;
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                generationProgress.style.display = 'none';
                generationResult.style.display = 'block';
                generationResult.className = 'seup-generation-result error';
                generationResult.innerHTML = `
                    <i class="fas fa-exclamation-triangle seup-result-icon"></i>
                    <span class="seup-result-message">Greška: ${error.message}</span>
                `;
            })
            .finally(() => {
                this.classList.remove('seup-generating');
            });
        });
    }

    // Document deletion
    let currentDeleteData = null;

    function openDeleteModal(filename, filepath) {
        currentDeleteData = { filename, filepath };
        document.getElementById('deleteDocName').textContent = filename;
        document.getElementById('deleteDocumentModal').classList.add('show');
    }

    function closeDeleteModal() {
        document.getElementById('deleteDocumentModal').classList.remove('show');
        currentDeleteData = null;
    }

    function confirmDelete() {
        if (!currentDeleteData) return;
        
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.classList.add('seup-loading');
        
        const formData = new FormData();
        formData.append('action', 'delete_document');
        formData.append('filename', currentDeleteData.filename);
        formData.append('filepath', currentDeleteData.filepath);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                closeDeleteModal();
                // Reload page to refresh document list
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showMessage('Greška: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showMessage('Greška: ' + error.message, 'error');
        })
        .finally(() => {
            confirmBtn.classList.remove('seup-loading');
        });
    }

    // Event listeners for delete buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-document-btn')) {
            const btn = e.target.closest('.delete-document-btn');
            const filename = btn.dataset.filename;
            const filepath = btn.dataset.filepath;
            openDeleteModal(filename, filepath);
        }
    });

    // Modal event listeners
    document.getElementById('closeDeleteModal').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDeleteBtn').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

    // Close modal when clicking outside
    document.getElementById('deleteDocumentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Zone hover effects
    document.querySelectorAll('.seup-zone').forEach(zone => {
        zone.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
            this.style.zIndex = '10';
        });
        
        zone.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.zIndex = '1';
        });
    });

    // Toast message function
    window.showMessage = function(message, type = 'success', duration = 5000) {
        let messageEl = document.querySelector('.seup-message-toast');
        if (!messageEl) {
            messageEl = document.createElement('div');
            messageEl.className = 'seup-message-toast';
            document.body.appendChild(messageEl);
        }

        messageEl.className = `seup-message-toast seup-message-${type} show`;
        messageEl.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
        `;

        setTimeout(() => {
            messageEl.classList.remove('show');
        }, duration);
    };
});
</script>

<?php
llxFooter();
$db->close();
?>