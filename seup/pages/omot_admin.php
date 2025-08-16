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
 *	\file       seup/omot_admin.php
 *	\ingroup    seup
 *	\brief      Admin page for omot spisa management
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

// Local classes
require_once __DIR__ . '/../class/omot_spisa_generator.class.php';
require_once __DIR__ . '/../class/omot_auto_updater.class.php';
require_once __DIR__ . '/../class/omot_helper.class.php';
require_once __DIR__ . '/../class/omot_request_handler.class.php';

// Load translation files
$langs->loadLangs(array("seup@seup"));

// Security check - admin only
if (!$user->admin) {
    accessforbidden();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean output buffer for all AJAX requests
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $action = GETPOST('action', 'alpha');
    
    switch ($action) {
        case 'batch_generate':
            Omot_Request_Handler::handleBatchGenerate($db, $conf, $user, $langs);
            break;
        case 'get_statistics':
            Omot_Request_Handler::handleOmotStatistics($db, $conf);
            break;
        case 'cleanup_omoti':
            Omot_Request_Handler::handleCleanupOmoti($db, $conf);
            break;
        case 'update_auto_settings':
            Omot_Request_Handler::handleUpdateAutoSettings($db, $conf);
            break;
        case 'validate_layout':
            Omot_Request_Handler::handleValidateLayout();
            break;
    }
}

// Get current statistics
$stats = Omot_Spisa_Generator::getOmotStatistics($db, $conf);
$queueStatus = Omot_Auto_Updater::getQueueStatus($db, $conf);

$form = new Form($db);
llxHeader("", "Omot Spisa - Administracija", '', '', 0, 0, '', '', '', 'mod-seup page-omot-admin');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';
print '<link href="/custom/seup/css/omot-spisa.css" rel="stylesheet">';

// Main hero section
print '<main class="seup-settings-hero">';

// Copyright footer
print '<footer class="seup-footer">';
print '<div class="seup-footer-content">';
print '<div class="seup-footer-left">';
print '<p>Sva prava pridržana © <a href="https://8core.hr" target="_blank" rel="noopener">8Core Association</a> 2014 - ' . date('Y') . '</p>';
print '</div>';
print '<div class="seup-footer-right">';
print '<p class="seup-version">SEUP v.14.0.4</p>';
print '</div>';
print '</div>';
print '</footer>';

// Floating background elements
print '<div class="seup-floating-elements">';
for ($i = 1; $i <= 5; $i++) {
    print '<div class="seup-floating-element"></div>';
}
print '</div>';

print '<div class="seup-settings-content">';

// Header section
print '<div class="seup-settings-header">';
print '<h1 class="seup-settings-title">Omot Spisa - Administracija</h1>';
print '<p class="seup-settings-subtitle">Upravljanje sustavom automatskog generiranja omota spisa</p>';
print '</div>';

// Main content
print '<div class="seup-omot-container">';

// Statistics section
print '<div class="seup-settings-card seup-card-wide animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-chart-bar"></i></div>';
print '<div class="seup-card-header-content">';
print '<h3 class="seup-card-title">Statistike Omota</h3>';
print '<p class="seup-card-description">Pregled generiranih omota i status sustava</p>';
print '</div>';
print '<div class="seup-card-actions">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="refreshStatsBtn">';
print '<i class="fas fa-sync"></i> Osvježi';
print '</button>';
print '</div>';
print '</div>';

print '<div class="seup-omot-stats">';

print '<div class="seup-stat-card">';
print '<div class="seup-stat-icon"><i class="fas fa-folder"></i></div>';
print '<div class="seup-stat-number">' . ($stats['total_predmeti'] ?? 0) . '</div>';
print '<div class="seup-stat-label">Ukupno Predmeta</div>';
print '</div>';

print '<div class="seup-stat-card">';
print '<div class="seup-stat-icon"><i class="fas fa-file-pdf"></i></div>';
print '<div class="seup-stat-number">' . ($stats['generated_omoti'] ?? 0) . '</div>';
print '<div class="seup-stat-label">Generiranih Omota</div>';
print '</div>';

print '<div class="seup-stat-card">';
print '<div class="seup-stat-icon"><i class="fas fa-clock"></i></div>';
print '<div class="seup-stat-number">' . ($queueStatus['needs_generation'] ?? 0) . '</div>';
print '<div class="seup-stat-label">Čeka Generiranje</div>';
print '</div>';

print '<div class="seup-stat-card">';
print '<div class="seup-stat-icon"><i class="fas fa-percentage"></i></div>';
print '<div class="seup-stat-number">' . ($queueStatus['completion_percentage'] ?? 0) . '%</div>';
print '<div class="seup-stat-label">Završenost</div>';
print '</div>';

print '</div>'; // seup-omot-stats
print '</div>'; // statistics card

// Batch generation section
print '<div class="seup-settings-card animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-layer-group"></i></div>';
print '<h3 class="seup-card-title">Batch Generiranje</h3>';
print '<p class="seup-card-description">Generirajte omote za više predmeta odjednom</p>';
print '</div>';

print '<div class="seup-batch-section">';
print '<div class="seup-batch-header">';
print '<div class="seup-batch-icon"><i class="fas fa-magic"></i></div>';
print '<div>';
print '<h4 class="seup-batch-title">Masovno Generiranje Omota</h4>';
print '<p class="seup-batch-description">Generirajte omote za sve predmete koji ih nemaju ili trebaju ažuriranje</p>';
print '</div>';
print '</div>';

print '<div class="seup-batch-controls">';
print '<label for="batchLimit">Broj predmeta po batch-u:</label>';
print '<input type="number" id="batchLimit" class="seup-batch-input" value="20" min="1" max="100">';
print '<button type="button" class="seup-btn seup-btn-batch" id="batchGenerateBtn">';
print '<i class="fas fa-play"></i> Pokreni Batch';
print '</button>';
print '</div>';

print '<div class="seup-generation-progress" id="batchProgress">';
print '<div class="seup-progress-header">';
print '<div class="seup-progress-icon"><i class="fas fa-cog fa-spin"></i></div>';
print '<div class="seup-progress-text">';
print '<h5 class="seup-progress-title">Batch generiranje u tijeku...</h5>';
print '<p class="seup-progress-description" id="batchProgressText">Priprema...</p>';
print '</div>';
print '</div>';
print '<div class="seup-progress-bar">';
print '<div class="seup-progress-fill" id="batchProgressFill"></div>';
print '</div>';
print '</div>';

print '<div class="seup-generation-result" id="batchResult"></div>';

print '</div>'; // seup-batch-section
print '</div>'; // batch card

// Auto-generation settings
print '<div class="seup-settings-card animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-robot"></i></div>';
print '<h3 class="seup-card-title">Automatsko Generiranje</h3>';
print '<p class="seup-card-description">Konfigurirajte automatsko ažuriranje omota</p>';
print '</div>';

print '<div class="seup-auto-settings">';
print '<h4 class="seup-auto-title"><i class="fas fa-cogs"></i>Postavke Automatizacije</h4>';
print '<div class="seup-auto-options">';

$auto_generate = getDolGlobalString('SEUP_OMOT_AUTO_GENERATE', '1');
$auto_archive = getDolGlobalString('SEUP_OMOT_AUTO_ARCHIVE', '1');
$cleanup_enabled = getDolGlobalString('SEUP_OMOT_CLEANUP_ENABLED', '1');

print '<div class="seup-auto-option">';
print '<input type="checkbox" id="autoGenerate" class="seup-auto-checkbox" ' . ($auto_generate === '1' ? 'checked' : '') . '>';
print '<label for="autoGenerate" class="seup-auto-label">Automatski generiraj omot pri dodavanju dokumenata</label>';
print '</div>';

print '<div class="seup-auto-option">';
print '<input type="checkbox" id="autoArchive" class="seup-auto-checkbox" ' . ($auto_archive === '1' ? 'checked' : '') . '>';
print '<label for="autoArchive" class="seup-auto-label">Generiraj finalni omot pri arhiviranju</label>';
print '</div>';

print '<div class="seup-auto-option">';
print '<input type="checkbox" id="cleanupEnabled" class="seup-auto-checkbox" ' . ($cleanup_enabled === '1' ? 'checked' : '') . '>';
print '<label for="cleanupEnabled" class="seup-auto-label">Automatsko čišćenje starih verzija</label>';
print '</div>';

print '</div>'; // seup-auto-options

print '<div style="margin-top: var(--space-4);">';
print '<button type="button" class="seup-btn seup-btn-primary" id="saveSettingsBtn">';
print '<i class="fas fa-save"></i> Spremi Postavke';
print '</button>';
print '</div>';

print '</div>'; // seup-auto-settings
print '</div>'; // auto settings card

// Maintenance section
print '<div class="seup-settings-card animate-fade-in-up">';
print '<div class="seup-card-header">';
print '<div class="seup-card-icon"><i class="fas fa-tools"></i></div>';
print '<h3 class="seup-card-title">Održavanje Sustava</h3>';
print '<p class="seup-card-description">Alati za održavanje i optimizaciju omota</p>';
print '</div>';

print '<div class="seup-form">';
print '<div class="seup-form-actions">';
print '<button type="button" class="seup-btn seup-btn-secondary" id="cleanupBtn">';
print '<i class="fas fa-broom"></i> Očisti Stare Datoteke';
print '</button>';
print '<button type="button" class="seup-btn seup-btn-secondary" id="validateLayoutBtn">';
print '<i class="fas fa-check-circle"></i> Validiraj Layout';
print '</button>';
print '<button type="button" class="seup-btn seup-btn-secondary" id="testGenerationBtn">';
print '<i class="fas fa-flask"></i> Test Generiranje';
print '</button>';
print '</div>';
print '</div>';

print '</div>'; // maintenance card

print '</div>'; // seup-omot-container
print '</div>'; // seup-settings-content
print '</main>';

?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Batch generation
    const batchGenerateBtn = document.getElementById('batchGenerateBtn');
    const batchProgress = document.getElementById('batchProgress');
    const batchResult = document.getElementById('batchResult');
    const batchProgressFill = document.getElementById('batchProgressFill');
    const batchProgressText = document.getElementById('batchProgressText');

    if (batchGenerateBtn) {
        batchGenerateBtn.addEventListener('click', function() {
            const limit = document.getElementById('batchLimit').value;
            
            // Show progress
            batchProgress.style.display = 'block';
            batchResult.style.display = 'none';
            this.classList.add('seup-loading');
            
            batchProgressText.textContent = 'Priprema batch generiranja...';
            
            // Simulate progress
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 85) progress = 85;
                batchProgressFill.style.width = progress + '%';
                
                if (progress > 20) batchProgressText.textContent = 'Generiranje omota u tijeku...';
                if (progress > 50) batchProgressText.textContent = 'Spremanje datoteka...';
                if (progress > 70) batchProgressText.textContent = 'Finaliziranje...';
            }, 300);

            const formData = new FormData();
            formData.append('action', 'batch_generate');
            formData.append('limit', limit);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                batchProgressFill.style.width = '100%';
                
                setTimeout(() => {
                    batchProgress.style.display = 'none';
                    batchResult.style.display = 'block';
                    
                    if (data.success) {
                        batchResult.className = 'seup-generation-result success';
                        batchResult.innerHTML = `
                            <i class="fas fa-check-circle seup-result-icon"></i>
                            <span class="seup-result-message">${data.message}</span>
                        `;
                        
                        // Refresh statistics
                        refreshStatistics();
                    } else {
                        batchResult.className = 'seup-generation-result error';
                        batchResult.innerHTML = `
                            <i class="fas fa-exclamation-triangle seup-result-icon"></i>
                            <span class="seup-result-message">Greška: ${data.error}</span>
                        `;
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                batchProgress.style.display = 'none';
                batchResult.style.display = 'block';
                batchResult.className = 'seup-generation-result error';
                batchResult.innerHTML = `
                    <i class="fas fa-exclamation-triangle seup-result-icon"></i>
                    <span class="seup-result-message">Greška: ${error.message}</span>
                `;
            })
            .finally(() => {
                this.classList.remove('seup-loading');
            });
        });
    }

    // Settings save
    const saveSettingsBtn = document.getElementById('saveSettingsBtn');
    if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', function() {
            this.classList.add('seup-loading');
            
            const formData = new FormData();
            formData.append('action', 'update_auto_settings');
            formData.append('auto_generate', document.getElementById('autoGenerate').checked ? '1' : '0');
            formData.append('auto_archive', document.getElementById('autoArchive').checked ? '1' : '0');
            formData.append('cleanup_enabled', document.getElementById('cleanupEnabled').checked ? '1' : '0');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                } else {
                    showMessage('Greška: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('Greška: ' + error.message, 'error');
            })
            .finally(() => {
                this.classList.remove('seup-loading');
            });
        });
    }

    // Cleanup
    const cleanupBtn = document.getElementById('cleanupBtn');
    if (cleanupBtn) {
        cleanupBtn.addEventListener('click', function() {
            if (confirm('Želite li pokrenuti čišćenje starih omot datoteka?')) {
                this.classList.add('seup-loading');
                
                const formData = new FormData();
                formData.append('action', 'cleanup_omoti');
                formData.append('hours', '24');

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        refreshStatistics();
                    } else {
                        showMessage('Greška: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Greška: ' + error.message, 'error');
                })
                .finally(() => {
                    this.classList.remove('seup-loading');
                });
            }
        });
    }

    // Validate layout
    const validateLayoutBtn = document.getElementById('validateLayoutBtn');
    if (validateLayoutBtn) {
        validateLayoutBtn.addEventListener('click', function() {
            this.classList.add('seup-loading');
            
            const formData = new FormData();
            formData.append('action', 'validate_layout');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const validation = data.validation;
                    if (validation.valid) {
                        showMessage('Layout validacija uspješna', 'success');
                    } else {
                        showMessage('Layout greške: ' + validation.errors.join(', '), 'error');
                    }
                } else {
                    showMessage('Greška: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('Greška: ' + error.message, 'error');
            })
            .finally(() => {
                this.classList.remove('seup-loading');
            });
        });
    }

    // Test generation
    const testGenerationBtn = document.getElementById('testGenerationBtn');
    if (testGenerationBtn) {
        testGenerationBtn.addEventListener('click', function() {
            // Find first predmet for testing
            const testPredmetId = 1; // You can make this dynamic
            
            this.classList.add('seup-loading');
            
            const formData = new FormData();
            formData.append('action', 'generate_omot');
            formData.append('predmet_id', testPredmetId);

            fetch('/custom/seup/pages/predmet.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Test generiranje uspješno', 'success');
                } else {
                    showMessage('Test greška: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showMessage('Test greška: ' + error.message, 'error');
            })
            .finally(() => {
                this.classList.remove('seup-loading');
            });
        });
    }

    // Refresh statistics
    function refreshStatistics() {
        const formData = new FormData();
        formData.append('action', 'get_statistics');

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update statistics display
                const stats = data.statistics;
                const queue = data.queue_status;
                
                document.querySelector('.seup-stat-card:nth-child(1) .seup-stat-number').textContent = stats.total_predmeti || 0;
                document.querySelector('.seup-stat-card:nth-child(2) .seup-stat-number').textContent = stats.generated_omoti || 0;
                document.querySelector('.seup-stat-card:nth-child(3) .seup-stat-number').textContent = queue.needs_generation || 0;
                document.querySelector('.seup-stat-card:nth-child(4) .seup-stat-number').textContent = (queue.completion_percentage || 0) + '%';
            }
        })
        .catch(error => {
            console.error('Error refreshing statistics:', error);
        });
    }

    // Refresh stats button
    const refreshStatsBtn = document.getElementById('refreshStatsBtn');
    if (refreshStatsBtn) {
        refreshStatsBtn.addEventListener('click', function() {
            this.classList.add('seup-loading');
            refreshStatistics();
            setTimeout(() => {
                this.classList.remove('seup-loading');
                showMessage('Statistike osvježene', 'success');
            }, 1000);
        });
    }

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