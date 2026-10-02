<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gatepass - Event Ticketing</title>
    <script>
        (() => {
            let theme = '';
            try {
                theme = localStorage.getItem('gatepass-theme') || '';
            } catch (error) {}
            if (theme !== 'light' && theme !== 'dark') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    <link rel="stylesheet" href="assets/style.css?v=6">
</head>
<body>

<header>
    <h1>Gatepass</h1>
    <p><?= e($summary) ?></p>
    <button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode">
        <svg class="sun-icon" width="21" height="21" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path></svg>
        <svg class="moon-icon" width="21" height="21" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"></path></svg>
    </button>
</header>

<main>

    <!-- RECEIPT: shows once after a successful registration -->
    <?php if ($receipt !== null): ?>
    <section class="receipt">
        <img src="uploads/<?= e($receipt['photo']) ?>" alt="Badge photo">

        <div>
            <p class="code"><?= e($receipt['id']) ?></p>
            <h2><?= e($receipt['name']) ?></h2>
            <p><?= e($events[$receipt['event']]['name']) ?></p>
            <p><?= e($events[$receipt['event']]['date']) ?></p>
            <p><?= e(get_level($receipt['total'])) ?> attendee | <?= e(group_type($receipt['qty'])) ?></p>
        </div>

        <table>
            <tr>
                <td><?= e($tiers[$receipt['tier']]['label']) ?> x <?= $receipt['qty'] ?></td>
                <td><?= e(peso($receipt['subtotal'])) ?></td>
            </tr>
            <?php if ($receipt['discount'] > 0): ?>
            <tr>
                <td>Discount (<?= round($receipt['discount_rate'] * 100) ?>%)</td>
                <td>- <?= e(peso($receipt['discount'])) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Service fee</td>
                <td><?= e(peso($receipt['fee'])) ?></td>
            </tr>
            <tr>
                <td>VAT 12%</td>
                <td><?= e(peso($receipt['tax'])) ?></td>
            </tr>
            <tr class="grand">
                <td>Total</td>
                <td><?= e(peso($receipt['total'])) ?></td>
            </tr>
        </table>
    </section>
    <?php endif; ?>


    <!-- REGISTRATION FORM -->
    <section class="box">
        <h2>Register</h2>

        <!-- Error messages -->
        <?php if (!empty($errors)): ?>
        <div class="errors">
            <strong>Please fix these:</strong>
            <ul>
                <?php foreach ($errors as $message): ?>
                <li><?= e($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" action="index.php" enctype="multipart/form-data">

            <label>Full name
                <input type="text" name="name" value="<?= e($name) ?>">
            </label>

            <label>Email
                <input type="text" name="email" value="<?= e($email) ?>">
            </label>

            <label>Age
                <input type="text" name="age" value="<?= e($age) ?>">
            </label>

            <!-- Event dropdown -->
            <label>Event
                <select name="event">
                    <option value="">Choose an event</option>
                    <?php foreach ($events as $key => $ev): ?>
                    <option value="<?= e($key) ?>" <?= $event === $key ? 'selected' : '' ?>>
                        <?= e($ev['name']) ?> (<?= e($ev['date']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- Ticket tiers (cheapest first) -->
            <p class="label">Ticket tier</p>
            <div class="tiers">
                <?php foreach ($prices as $key => $price): ?>
                <label class="tier">
                    <input type="radio" name="tier" value="<?= e($key) ?>" <?= $tier === $key ? 'checked' : '' ?>>
                    <span>
                        <b><?= e($tiers[$key]['label']) ?></b> - <?= e(peso($price)) ?><br>
                        <small><?= e($tiers[$key]['perks']) ?></small>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>

            <label>Number of tickets
                <input type="text" name="qty" value="<?= e($qty) ?>">
            </label>

            <label>Promo code (optional)
                <input type="text" name="promo" value="<?= e($promo) ?>" placeholder="STUDENT">
            </label>

            <!-- Interests checkboxes -->
            <p class="label">Interests</p>
            <div class="chips">
                <?php foreach ($interest_options as $option): ?>
                <label>
                    <input type="checkbox" name="interests[]" value="<?= e($option) ?>" <?= in_array($option, $interests) ? 'checked' : '' ?>>
                    <?= e($option) ?>
                </label>
                <?php endforeach; ?>
            </div>

            <label>Badge photo (JPG, PNG or WebP, max 2 MB)
                <input type="file" name="photo">
            </label>

            <label class="agree">
                <input type="checkbox" name="agree" value="yes" <?= $agree === 'yes' ? 'checked' : '' ?>>
                I accept the event terms.
            </label>

            <button type="submit">Get my ticket</button>
        </form>
    </section>


    <!-- ATTENDEE LIST -->
    <section class="box">
        <h2>Attendees</h2>

        <p class="sort">
            Sort:
            <a href="?sort=desc">Highest total</a>
            <a href="?sort=asc">Lowest total</a>
        </p>

        <?php if (empty($people)): ?>
            <p>No one has registered yet.</p>
        <?php else: ?>
            <?php foreach ($people as $number => $person): ?>
            <div class="person">
                <?php if (!empty($person['photo'])): ?>
                <img src="uploads/<?= e($person['photo']) ?>" alt="Badge photo">
                <?php endif; ?>

                <div>
                    <b><?= $number + 1 ?>. <?= e($person['name']) ?></b><br>
                    <small>
                        <?= e($events[$person['event']]['name']) ?> |
                        <?= e($tiers[$person['tier']]['label']) ?> x <?= $person['qty'] ?> |
                        <?= e($person['id']) ?>
                    </small><br>

                    <!-- interests were saved with implode(), so we split them with explode() -->
                    <?php if ($person['interests'] !== ''): ?>
                        <?php foreach (explode(', ', $person['interests']) as $tag): ?>
                        <span class="tag"><?= e($tag) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="money">
                    <b><?= e(peso($person['total'])) ?></b><br>
                    <small><?= e(get_level($person['total'])) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

</main>
<script>
    const themeToggle = document.querySelector('.theme-toggle');
    const setTheme = (theme) => {
        document.documentElement.dataset.theme = theme;
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        themeToggle.setAttribute('aria-label', `Switch to ${nextTheme} mode`);
        themeToggle.setAttribute('title', `Switch to ${nextTheme} mode`);
        try {
            localStorage.setItem('gatepass-theme', theme);
        } catch (error) {}
    };

    const initialTheme = document.documentElement.dataset.theme;
    const initialNextTheme = initialTheme === 'dark' ? 'light' : 'dark';
    themeToggle.setAttribute('aria-label', `Switch to ${initialNextTheme} mode`);
    themeToggle.setAttribute('title', `Switch to ${initialNextTheme} mode`);
    themeToggle.addEventListener('click', () => {
        setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
    });
</script>
</body>
</html>