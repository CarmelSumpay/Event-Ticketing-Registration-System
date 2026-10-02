<?php
// Show PHP errors while developing (remove when done)
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

// Visit index.php?reset=1 to clear all saved registrations (handy for demos)
if (isset($_GET['reset'])) {
    $_SESSION = [];
    header('Location: index.php');
    exit;
}

require 'includes/data.php';
require 'includes/functions.php';

// Make the uploads folder if it is missing
if (!is_dir('uploads')) {
    mkdir('uploads');
}

// Our saved registrations live in the session
if (!isset($_SESSION['registrations'])) {
    $_SESSION['registrations'] = [];
}

// Sticky form values (?? gives an empty default when nothing was sent)
$name      = $_POST['name'] ?? '';
$email     = $_POST['email'] ?? '';
$age       = $_POST['age'] ?? '';
$event     = $_POST['event'] ?? '';
$tier      = $_POST['tier'] ?? '';
$qty       = $_POST['qty'] ?? '1';
$promo     = $_POST['promo'] ?? '';
$agree     = $_POST['agree'] ?? '';

$errors = [];

// ---------- Form was submitted ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($name);
    $email = trim($email);
    $age   = trim($age);
    $qty   = trim($qty);
    $promo = strtoupper(trim($promo));

    // Name
    if (empty($name)) {
        $errors[] = 'Please enter your name.';
    } elseif (strlen($name) < MIN_NAME_LENGTH) {
        $errors[] = 'Name must be at least ' . MIN_NAME_LENGTH . ' characters.';
    }

    // Email
    if (empty($email)) {
        $errors[] = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'That email is not valid.';
    }

    // Age
    if (empty($age)) {
        $errors[] = 'Please enter your age.';
    } elseif (!filter_var($age, FILTER_VALIDATE_INT, ['options' => ['min_range' => 13, 'max_range' => 120]])) {
        $errors[] = 'Age must be a number from 13 to 120.';
    }

    // Event and tier must be one of our choices
    if (!array_key_exists($event, $events)) {
        $errors[] = 'Please choose an event.';
    }
    if (!array_key_exists($tier, $tiers)) {
        $errors[] = 'Please choose a ticket tier.';
    }

    // Quantity
    if (!filter_var($qty, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]])) {
        $errors[] = 'Tickets must be a number from 1 to 10.';
    }

    // Promo code (optional)
    if (!empty($promo) && !array_key_exists($promo, $promo_codes)) {
        $errors[] = 'That promo code does not exist.';
    }

    // Terms
    if ($agree !== 'yes') {
        $errors[] = 'Please accept the terms.';
    }

    // ---------- File upload checks ----------
    $file = $_FILES['photo'] ?? null;
    $new_file_name = '';
    $mime = '';

    if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please upload a badge photo.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The upload failed. Try a smaller photo.';
    } elseif ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'Photo is too big. Maximum is 2 MB.';
    } else {
        // getimagesize() reads the real image type (false if it is not an image)
        $image_info = @getimagesize($file['tmp_name']);
        $mime = $image_info['mime'] ?? '';

        if (!array_key_exists($mime, $allowed_types)) {
            $errors[] = 'Photo must be a JPG, PNG or WebP image.';
        }
    }

    // ---------- No errors: save everything ----------
    if (empty($errors)) {

        // give the file a safe unique name, then move it to uploads/
        $extension = $allowed_types[$mime];
        $new_file_name = uniqid('badge_') . '.' . $extension;
        move_uploaded_file($file['tmp_name'], 'uploads/' . $new_file_name);

        // do the math
        $promo_rate = $promo_codes[$promo] ?? 0;
        $order = calculate_order($tiers[$tier]['price'], (int) $qty, $promo_rate);

        // build the record and save it
        $record = [
            'id'        => next_ticket_id(count($_SESSION['registrations'])),
            'name'      => $name,
            'email'     => $email,
            'age'       => (int) $age,
            'event'     => $event,
            'tier'      => $tier,
            'qty'       => (int) $qty,
            'photo'     => $new_file_name,
            'subtotal'  => $order['subtotal'],
            'discount_rate' => $order['discount_rate'],
            'discount'  => $order['discount'],
            'fee'       => $order['fee'],
            'tax'       => $order['tax'],
            'total'     => $order['total'],
        ];
        add_registration($_SESSION['registrations'], $record);
        $_SESSION['receipt'] = $record;

        // Post/Redirect/Get: stops the form being sent twice on refresh
        header('Location: index.php');
        exit;
    }
}

// ---------- Prepare data for the page ----------
$receipt = $_SESSION['receipt'] ?? null;
unset($_SESSION['receipt']);   // show the receipt only once

$sort = $_GET['sort'] ?? 'desc';
if ($sort !== 'asc' && $sort !== 'desc') {
    $sort = 'desc';
}
$people = sort_by_total($_SESSION['registrations'], $sort);

$total_people = count($_SESSION['registrations']);
$total_money  = calculate_total($_SESSION['registrations']);

$summary = '';
$summary .= $total_people . ' registered';
$summary .= ' | ' . peso($total_money) . ' collected';

// asort: tiers ordered from cheapest to most expensive
$prices = [];
foreach ($tiers as $key => $t) {
    $prices[$key] = $t['price'];
}
asort($prices);

require 'templates/page.php';