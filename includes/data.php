<?php
// ---------- SETTINGS (easy to change) ----------
const MIN_NAME_LENGTH = 4;                // change 4 to 8 for the live challenge
const MAX_FILE_SIZE   = 2 * 1024 * 1024;  // 2 MB
const VAT_RATE        = 0.12;             // 12% tax
const SERVICE_FEE     = 0.05;             // 5% service fee

// ---------- DATA (multidimensional associative arrays) ----------
$events = [
    'techfest' => ['name' => 'Bicol TechFest',    'date' => 'Nov 14, 2026', 'venue' => 'Naga Convention Hall'],
    'sound'    => ['name' => 'Kabsat Sound Fest', 'date' => 'Dec 05, 2026', 'venue' => 'Penafrancia Grounds'],
    'artwalk'  => ['name' => 'Art and Food Walk', 'date' => 'Dec 19, 2026', 'venue' => 'Magsaysay Avenue'],
];

$tiers = [
    'general'   => ['label' => 'General',   'price' => 1500, 'perks' => 'Standing area, wristband'],
    'vip'       => ['label' => 'VIP',       'price' => 4500, 'perks' => 'Reserved seat, free drink'],
    'backstage' => ['label' => 'Backstage', 'price' => 9000, 'perks' => 'Meet the speakers, front row'],
];

$promo_codes = [
    'STUDENT'   => 0.15,
    'EARLYBIRD' => 0.10,
];

$interest_options = ['Music', 'Technology', 'Art', 'Food'];

// allowed image types and the file extension we save them with
$allowed_types = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];