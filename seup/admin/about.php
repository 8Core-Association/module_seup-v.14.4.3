<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2025      SuperAdmin
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    seup/admin/about.php
 * \ingroup seup
 * \brief   About page of module SEUP.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/seup.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("errors", "admin", "seup@seup"));

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "SEUPSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-seup page-admin_about');

// Modern design assets
print '<meta name="viewport" content="width=device-width, initial-scale=1">';
print '<link rel="preconnect" href="https://fonts.googleapis.com">';
print '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
print '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
print '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
print '<link href="/custom/seup/css/seup-modern.css" rel="stylesheet">';

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

// Custom header with modern design
print '<div class="seup-admin-header">';
print '<div class="seup-admin-header-content">';
print '<div class="seup-admin-icon"><i class="fas fa-info-circle"></i></div>';
print '<div class="seup-admin-title-section">';
print '<h1 class="seup-admin-title">O SEUP Modulu</h1>';
print '<p class="seup-admin-subtitle">Informacije o modulu, licenci i autorskim pravima</p>';
print '</div>';
print '</div>';
print '<div class="seup-admin-actions">';
print $linkback;
print '</div>';
print '</div>';

// Configuration header
$head = seupAdminPrepareHead();
print '<div class="seup-admin-tabs">';
print dol_get_fiche_head($head, 'about', '', 0, 'seup@seup');
print '</div>';

// About page content
print '<div class="seup-about-container">';

// Module Info Section
print '<div class="seup-about-section">';
print '<div class="seup-section-header">';
print '<div class="seup-section-icon"><i class="fas fa-cube"></i></div>';
print '<div>';
print '<h3 class="seup-section-title">SEUP - Sustav Elektronskog Uredskog Poslovanja</h3>';
print '<p class="seup-section-description">Moderni modul za upravljanje dokumentima i predmetima u javnoj upravi</p>';
print '</div>';
print '</div>';
print '<div class="seup-section-content">';
print '<div class="seup-info-grid">';

print '<div class="seup-info-card">';
print '<div class="seup-info-icon"><i class="fas fa-tag"></i></div>';
print '<div class="seup-info-content">';
print '<h4>Verzija</h4>';
print '<p>14.0.4</p>';
print '</div>';
print '</div>';

print '<div class="seup-info-card">';
print '<div class="seup-info-icon"><i class="fas fa-calendar"></i></div>';
print '<div class="seup-info-content">';
print '<h4>Datum izdanja</h4>';
print '<p>' . date('d.m.Y') . '</p>';
print '</div>';
print '</div>';

print '<div class="seup-info-card">';
print '<div class="seup-info-icon"><i class="fas fa-code"></i></div>';
print '<div class="seup-info-content">';
print '<h4>Kompatibilnost</h4>';
print '<p>Dolibarr 19.0+</p>';
print '</div>';
print '</div>';

print '<div class="seup-info-card">';
print '<div class="seup-info-icon"><i class="fas fa-shield-alt"></i></div>';
print '<div class="seup-info-content">';
print '<h4>Licenca</h4>';
print '<p>Vlasnička</p>';
print '</div>';
print '</div>';

print '</div>'; // seup-info-grid
print '</div>'; // seup-section-content
print '</div>'; // seup-about-section

// Features Section
print '<div class="seup-about-section">';
print '<div class="seup-section-header">';
print '<div class="seup-section-icon"><i class="fas fa-star"></i></div>';
print '<div>';
print '<h3 class="seup-section-title">Značajke Modula</h3>';
print '<p class="seup-section-description">Napredne funkcionalnosti za upravljanje uredskim poslovanjem</p>';
print '</div>';
print '</div>';
print '<div class="seup-section-content">';
print '<div class="seup-features-grid">';

$features = [
    ['icon' => 'fas fa-folder-plus', 'title' => 'Upravljanje predmetima', 'desc' => 'Kreiranje i praćenje predmeta s klasifikacijskim oznakama'],
    ['icon' => 'fas fa-file-upload', 'title' => 'Upravljanje dokumentima', 'desc' => 'Upload, pregled i organizacija dokumenata'],
    ['icon' => 'fas fa-building', 'title' => 'Oznake ustanova', 'desc' => 'Konfiguracija osnovnih podataka ustanove'],
    ['icon' => 'fas fa-users', 'title' => 'Interne oznake korisnika', 'desc' => 'Upravljanje korisničkim oznakama i radnim mjestima'],
    ['icon' => 'fas fa-sitemap', 'title' => 'Plan klasifikacijskih oznaka', 'desc' => 'Hijerarhijski sustav klasifikacije'],
    ['icon' => 'fas fa-tags', 'title' => 'Tagovi', 'desc' => 'Fleksibilno označavanje s color pickerom'],
    ['icon' => 'fas fa-chart-bar', 'title' => 'Statistike', 'desc' => 'Pregled aktivnosti i izvještaji'],
    ['icon' => 'fas fa-cloud', 'title' => 'Nextcloud integracija', 'desc' => 'Sinkronizacija dokumenata s vanjskim sustavima']
];

foreach ($features as $feature) {
    print '<div class="seup-feature-card">';
    print '<div class="seup-feature-icon"><i class="' . $feature['icon'] . '"></i></div>';
    print '<div class="seup-feature-content">';
    print '<h4>' . $feature['title'] . '</h4>';
    print '<p>' . $feature['desc'] . '</p>';
    print '</div>';
    print '</div>';
}

print '</div>'; // seup-features-grid
print '</div>'; // seup-section-content
print '</div>'; // seup-about-section

// License Section
print '<div class="seup-about-section seup-license-section">';
print '<div class="seup-section-header">';
print '<div class="seup-section-icon"><i class="fas fa-certificate"></i></div>';
print '<div>';
print '<h3 class="seup-section-title">Licenca i Autorska Prava</h3>';
print '<p class="seup-section-description">Informacije o vlasništvu i uvjetima korištenja</p>';
print '</div>';
print '</div>';
print '<div class="seup-section-content">';
print '<div class="seup-license-content">';

print '<div class="seup-license-warning">';
print '<div class="seup-warning-icon"><i class="fas fa-exclamation-triangle"></i></div>';
print '<div class="seup-warning-content">';
print '<h4>Plaćena Licenca - Sva Prava Pridržana</h4>';
print '<p>Ovaj softver je vlasnički i zaštićen je autorskim i srodnim pravima.</p>';
print '</div>';
print '</div>';

print '<div class="seup-copyright-info">';
print '<h4><i class="fas fa-copyright me-2"></i>Autorska Prava</h4>';
print '<div class="seup-authors">';
print '<div class="seup-author">';
print '<div class="seup-author-avatar"><i class="fas fa-user"></i></div>';
print '<div class="seup-author-info">';
print '<h5>Tomislav Galić</h5>';
print '<p>Glavni developer</p>';
print '<a href="mailto:tomislav@8core.hr"><i class="fas fa-envelope me-1"></i>tomislav@8core.hr</a>';
print '</div>';
print '</div>';
print '<div class="seup-author">';
print '<div class="seup-author-avatar"><i class="fas fa-user"></i></div>';
print '<div class="seup-author-info">';
print '<h5>Marko Šimunović</h5>';
print '<p>Suradnik</p>';
print '<a href="mailto:marko@8core.hr"><i class="fas fa-envelope me-1"></i>marko@8core.hr</a>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-company-info">';
print '<h4><i class="fas fa-building me-2"></i>8Core Association</h4>';
print '<div class="seup-contact-grid">';
print '<div class="seup-contact-item">';
print '<i class="fas fa-globe"></i>';
print '<a href="https://8core.hr" target="_blank">https://8core.hr</a>';
print '</div>';
print '<div class="seup-contact-item">';
print '<i class="fas fa-envelope"></i>';
print '<a href="mailto:info@8core.hr">info@8core.hr</a>';
print '</div>';
print '<div class="seup-contact-item">';
print '<i class="fas fa-phone"></i>';
print '<a href="tel:+385099851071">+385 099 851 0717</a>';
print '</div>';
print '<div class="seup-contact-item">';
print '<i class="fas fa-calendar"></i>';
print '<span>2014 - ' . date('Y') . '</span>';
print '</div>';
print '</div>';
print '</div>';

print '<div class="seup-legal-notice">';
print '<h4><i class="fas fa-gavel me-2"></i>Pravne Napomene</h4>';
print '<div class="seup-legal-content">';
print '<p><strong>Zabranjeno je:</strong></p>';
print '<ul>';
print '<li>Umnožavanje bez pismenog odobrenja</li>';
print '<li>Distribucija ili dijeljenje koda</li>';
print '<li>Mijenjanje ili prerada softvera</li>';
print '<li>Objavljivanje ili komercijalna eksploatacija</li>';
print '</ul>';
print '<p class="seup-legal-reference">';
print '<strong>Pravni okvir:</strong> Zakon o autorskom pravu i srodnim pravima (NN 167/03, 79/07, 80/11, 125/17) ';
print 'i Kazneni zakon (NN 125/11, 144/12, 56/15), članak 228.';
print '</p>';
print '<p class="seup-legal-penalty">';
print '<strong>Kazne:</strong> Prekršitelji se mogu kazniti novčanom kaznom ili zatvorom do jedne godine, ';
print 'uz mogućnost oduzimanja protivpravne imovinske koristi.';
print '</p>';
print '</div>';
print '</div>';

print '</div>'; // seup-license-content
print '</div>'; // seup-section-content
print '</div>'; // seup-license-section

// Support Section
print '<div class="seup-about-section">';
print '<div class="seup-section-header">';
print '<div class="seup-section-icon"><i class="fas fa-life-ring"></i></div>';
print '<div>';
print '<h3 class="seup-section-title">Podrška i Kontakt</h3>';
print '<p class="seup-section-description">Za sva pitanja, zahtjeve za licenciranjem ili tehničku podršku</p>';
print '</div>';
print '</div>';
print '<div class="seup-section-content">';
print '<div class="seup-support-cards">';

print '<div class="seup-support-card">';
print '<div class="seup-support-icon"><i class="fas fa-question-circle"></i></div>';
print '<h4>Tehnička Podrška</h4>';
print '<p>Za tehnička pitanja i probleme s modulom</p>';
print '<a href="mailto:info@8core.hr?subject=SEUP%20Tehnička%20Podrška" class="seup-support-btn">';
print '<i class="fas fa-envelope me-2"></i>Kontaktiraj podršku';
print '</a>';
print '</div>';

print '<div class="seup-support-card">';
print '<div class="seup-support-icon"><i class="fas fa-key"></i></div>';
print '<h4>Licenciranje</h4>';
print '<p>Za zahtjeve za dodatnim licencama</p>';
print '<a href="mailto:info@8core.hr?subject=SEUP%20Licenciranje" class="seup-support-btn">';
print '<i class="fas fa-handshake me-2"></i>Zahtjev za licencu';
print '</a>';
print '</div>';

print '<div class="seup-support-card">';
print '<div class="seup-support-icon"><i class="fas fa-cogs"></i></div>';
print '<h4>Prilagodbe</h4>';
print '<p>Za custom razvoj i prilagodbe</p>';
print '<a href="mailto:info@8core.hr?subject=SEUP%20Custom%20Razvoj" class="seup-support-btn">';
print '<i class="fas fa-code me-2"></i>Zatraži ponudu';
print '</a>';
print '</div>';

print '</div>'; // seup-support-cards
print '</div>'; // seup-section-content
print '</div>'; // seup-about-section

print '</div>'; // seup-about-container

// Enhanced CSS with animations, spacing and centering
print '<style>
:root {
    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-5: 1.25rem;
    --space-6: 1.5rem;
    --space-7: 1.75rem;
    --space-8: 2rem;
    --space-9: 2.5rem;
    --space-10: 3rem;
    
    --radius-sm: 0.125rem;
    --radius-md: 0.25rem;
    --radius-lg: 0.5rem;
    --radius-xl: 0.75rem;
    --radius-2xl: 1rem;
    --radius-3xl: 1.5rem;
    --radius-full: 9999px;
    
    --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    
    --transition-normal: 0.3s;
    --transition-slow: 0.5s;
    
    --primary-50: #f0f9ff;
    --primary-100: #e0f2fe;
    --primary-200: #bae6fd;
    --primary-300: #7dd3fc;
    --primary-400: #38bdf8;
    --primary-500: #0ea5e9;
    --primary-600: #0284c7;
    --primary-700: #0369a1;
    --primary-800: #075985;
    --primary-900: #0c4a6e;
    
    --neutral-50: #f9fafb;
    --neutral-100: #f3f4f6;
    --neutral-200: #e5e7eb;
    --neutral-300: #d1d5db;
    --neutral-400: #9ca3af;
    --neutral-500: #6b7280;
    --neutral-600: #4b5563;
    --neutral-700: #374151;
    --neutral-800: #1f2937;
    --neutral-900: #111827;
    
    --error-50: #fef2f2;
    --error-100: #fee2e2;
    --error-200: #fecaca;
    --error-300: #fca5a5;
    --error-400: #f87171;
    --error-500: #ef4444;
    --error-600: #dc2626;
    --error-700: #b91c1c;
    --error-800: #991b1b;
    --error-900: #7f1d1d;
    
    --success-50: #f0fdf4;
    --success-100: #dcfce7;
    --success-200: #bbf7d0;
    --success-300: #86efac;
    --success-400: #4ade80;
    --success-500: #22c55e;
    --success-600: #16a34a;
    --success-700: #15803d;
    --success-800: #166534;
    --success-900: #14532d;
    
    --warning-50: #fffbeb;
    --warning-100: #fef3c7;
    --warning-200: #fde68a;
    --warning-300: #fcd34d;
    --warning-400: #fbbf24;
    --warning-500: #f59e0b;
    --warning-600: #d97706;
    --warning-700: #b45309;
    --warning-800: #92400e;
    --warning-900: #78350f;
    
    --accent-500: #8b5cf6;
    --accent-600: #7c3aed;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.seup-about-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--space-10) 0;
}

.seup-about-section {
    background: white;
    border-radius: var(--radius-2xl);
    box-shadow: var(--shadow-lg);
    margin-bottom: var(--space-8);
    overflow: hidden;
    border: 1px solid var(--neutral-200);
    animation: slideUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
    opacity: 0;
    transform: translateY(20px);
}

.seup-about-section:nth-child(1) { animation-delay: 0.1s; }
.seup-about-section:nth-child(2) { animation-delay: 0.3s; }
.seup-about-section:nth-child(3) { animation-delay: 0.5s; }
.seup-about-section:nth-child(4) { animation-delay: 0.7s; }

.seup-admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-6) var(--space-8);
    background: linear-gradient(135deg, var(--primary-500), var(--primary-700));
    color: white;
    border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    margin-bottom: var(--space-6);
    animation: fadeIn 0.8s ease-out;
}

.seup-admin-header-content {
    display: flex;
    align-items: center;
    gap: var(--space-4);
}

.seup-admin-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    animation: pulse 2s infinite;
}

.seup-admin-title {
    margin: 0;
    font-size: 1.75rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.seup-admin-subtitle {
    margin: var(--space-1) 0 0 0;
    opacity: 0.9;
    font-weight: 300;
}

.seup-admin-actions a {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    background: rgba(255,255,255,0.15);
    color: white;
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-lg);
    text-decoration: none;
    transition: all var(--transition-normal);
}

.seup-admin-actions a:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

.seup-section-header {
    padding: var(--space-6);
    display: flex;
    align-items: center;
    gap: var(--space-4);
    background: var(--neutral-50);
    border-bottom: 1px solid var(--neutral-200);
}

.seup-section-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.seup-section-title {
    margin: 0;
    font-size: 1.5rem;
    color: var(--neutral-900);
}

.seup-section-description {
    margin: var(--space-1) 0 0 0;
    color: var(--neutral-600);
    font-size: 1rem;
}

.seup-section-content {
    padding: var(--space-6);
}

.seup-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: var(--space-5);
    place-items: center;
}

.seup-info-card {
    background: var(--neutral-50);
    border: 1px solid var(--neutral-200);
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    display: flex;
    align-items: center;
    gap: var(--space-4);
    transition: all var(--transition-normal);
    width: 100%;
    max-width: 300px;
    animation: fadeIn 0.5s ease-out;
}

.seup-info-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-300);
}

.seup-info-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    flex-shrink: 0;
    transition: transform var(--transition-slow);
}

.seup-info-card:hover .seup-info-icon {
    transform: scale(1.1);
    animation: pulse 1.5s infinite;
}

.seup-info-content {
    text-align: left;
}

.seup-info-content h4 {
    margin: 0 0 var(--space-1) 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--secondary-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.seup-info-content p {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--secondary-900);
}

.seup-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-5);
    place-items: center;
}

.seup-feature-card {
    background: var(--neutral-50);
    border: 1px solid var(--neutral-200);
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    transition: all var(--transition-normal);
    text-align: center;
    max-width: 320px;
    animation: fadeIn 0.5s ease-out;
}

.seup-feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
    border-color: var(--accent-500);
}

.seup-feature-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--accent-500), var(--accent-600));
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    margin: 0 auto var(--space-4) auto;
    transition: transform var(--transition-slow);
}

.seup-feature-card:hover .seup-feature-icon {
    transform: scale(1.1) rotate(5deg);
    animation: pulse 1.5s infinite;
}

.seup-feature-content h4 {
    margin: 0 0 var(--space-2) 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--neutral-900);
}

.seup-feature-content p {
    margin: 0;
    color: var(--neutral-600);
    line-height: 1.5;
}

.seup-license-section .seup-section-header {
    background: linear-gradient(135deg, var(--error-50), var(--error-100));
    border-bottom-color: var(--error-200);
}

.seup-license-section .seup-section-icon {
    background: linear-gradient(135deg, var(--error-500), var(--error-600));
}

.seup-license-warning {
    background: linear-gradient(135deg, var(--error-50), var(--error-100));
    border: 1px solid var(--error-200);
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    margin-bottom: var(--space-6);
    display: flex;
    align-items: center;
    gap: var(--space-4);
    animation: pulse 3s infinite;
}

.seup-warning-icon {
    width: 56px;
    height: 56px;
    background: var(--error-500);
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.seup-warning-content h4 {
    margin: 0 0 var(--space-2) 0;
    color: var(--error-800);
    font-size: 1.125rem;
    font-weight: 700;
}

.seup-warning-content p {
    margin: 0;
    color: var(--error-700);
    font-weight: 500;
}

.seup-copyright-info {
    background: var(--neutral-50);
    border: 1px solid var(--neutral-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    margin-bottom: var(--space-5);
}

.seup-copyright-info h4 {
    margin: 0 0 var(--space-4) 0;
    color: var(--neutral-900);
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.seup-authors {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-5);
}

.seup-author {
    background: white;
    border: 1px solid var(--neutral-200);
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    display: flex;
    align-items: center;
    gap: var(--space-4);
    transition: all var(--transition-normal);
}

.seup-author:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary-300);
}

.seup-author-avatar {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
    flex-shrink: 0;
    transition: transform var(--transition-slow);
}

.seup-author:hover .seup-author-avatar {
    transform: scale(1.05);
}

.seup-author-info h5 {
    margin: 0 0 var(--space-1) 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--neutral-900);
}

.seup-author-info p {
    margin: 0 0 var(--space-2) 0;
    font-size: 0.875rem;
    color: var(--neutral-600);
}

.seup-author-info a {
    color: var(--primary-600);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: var(--space-1);
    transition: color var(--transition-normal);
}

.seup-author-info a:hover {
    color: var(--primary-700);
    text-decoration: underline;
}

.seup-company-info {
    background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
    border: 1px solid var(--primary-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    margin-bottom: var(--space-5);
}

.seup-company-info h4 {
    margin: 0 0 var(--space-4) 0;
    color: var(--primary-800);
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.seup-contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-4);
}

.seup-contact-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    color: var(--primary-700);
    font-weight: 500;
    transition: all var(--transition-normal);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-md);
}

.seup-contact-item:hover {
    background: rgba(255,255,255,0.5);
    transform: translateX(5px);
}

.seup-contact-item i {
    width: 24px;
    text-align: center;
    color: var(--primary-600);
    font-size: 1.125rem;
}

.seup-contact-item a {
    color: var(--primary-700);
    text-decoration: none;
    transition: color var(--transition-normal);
}

.seup-contact-item a:hover {
    color: var(--primary-800);
    text-decoration: underline;
}

.seup-legal-notice {
    background: var(--warning-50);
    border: 1px solid var(--warning-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    transition: all var(--transition-slow);
}

.seup-legal-notice:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.seup-legal-notice h4 {
    margin: 0 0 var(--space-4) 0;
    color: var(--warning-800);
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.seup-legal-content p {
    margin: 0 0 var(--space-3) 0;
    color: var(--warning-800);
    line-height: 1.6;
}

.seup-legal-content ul {
    margin: 0 0 var(--space-4) var(--space-4);
    color: var(--warning-800);
    padding-left: var(--space-3);
}

.seup-legal-content li {
    margin-bottom: var(--space-2);
    position: relative;
}

.seup-legal-content li::before {
    content: "•";
    color: var(--warning-600);
    font-weight: bold;
    display: inline-block;
    width: 1em;
    margin-left: -1em;
    position: absolute;
    left: -1rem;
}

.seup-legal-reference {
    font-size: 0.875rem;
    font-style: italic;
    background: var(--warning-100);
    padding: var(--space-4);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--warning-500);
    margin: var(--space-4) 0;
}

.seup-legal-penalty {
    font-size: 0.875rem;
    font-weight: 600;
    background: var(--error-100);
    padding: var(--space-4);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--error-500);
    color: var(--error-800);
}

.seup-support-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-6);
    place-items: center;
}

.seup-support-card {
    background: var(--neutral-50);
    border: 1px solid var(--neutral-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    text-align: center;
    transition: all var(--transition-normal);
    max-width: 340px;
    animation: fadeIn 0.5s ease-out;
}

.seup-support-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-xl);
    border-color: var(--success-400);
}

.seup-support-icon {
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, var(--success-500), var(--success-600));
    border-radius: var(--radius-2xl);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    margin: 0 auto var(--space-4) auto;
    transition: transform var(--transition-slow);
}

.seup-support-card:hover .seup-support-icon {
    transform: scale(1.1);
    animation: pulse 1.5s infinite;
}

.seup-support-card h4 {
    margin: 0 0 var(--space-2) 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--neutral-900);
}

.seup-support-card p {
    margin: 0 0 var(--space-4) 0;
    color: var(--neutral-600);
    line-height: 1.5;
}

.seup-support-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-3) var(--space-6);
    background: linear-gradient(135deg, var(--success-500), var(--success-600));
    color: white;
    text-decoration: none;
    border-radius: var(--radius-lg);
    font-weight: 500;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    box-shadow: var(--shadow-md);
    border: none;
    cursor: pointer;
    gap: var(--space-2);
    min-width: 200px;
    margin: 0 auto;
}

.seup-support-btn:hover {
    background: linear-gradient(135deg, var(--success-600), var(--success-700));
    transform: translateY(-4px) scale(1.03);
    box-shadow: var(--shadow-lg);
    color: white;
    text-decoration: none;
}

/* Responsive design */
@media (max-width: 992px) {
    .seup-admin-header {
        flex-direction: column;
        text-align: center;
        gap: var(--space-4);
    }
    
    .seup-admin-actions {
        margin-top: var(--space-4);
    }
    
    .seup-section-header {
        flex-direction: column;
        text-align: center;
        gap: var(--space-4);
    }
    
    .seup-section-icon {
        margin: 0 auto;
    }
}

@media (max-width: 768px) {
    .seup-about-container {
        padding: var(--space-6) var(--space-4);
    }
    
    .seup-info-grid,
    .seup-features-grid,
    .seup-authors,
    .seup-support-cards {
        grid-template-columns: 1fr;
    }
    
    .seup-contact-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .seup-admin-title {
        font-size: 1.5rem;
    }
    
    .seup-section-title {
        font-size: 1.25rem;
    }
    
    .seup-section-content {
        padding: var(--space-4);
    }
}
</style>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();