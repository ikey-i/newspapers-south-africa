<?php
/** @var int $code */
/** @var string $title */
/** @var string $message */
?>
<section class="error-page">
    <p class="error-page__code"><?= e((string) $code) ?></p>
    <h1><?= e($title) ?></h1>
    <?php if (!empty($message)): ?>
        <p><?= e($message) ?></p>
    <?php endif ?>
    <p><a href="<?= e(url()) ?>">Back to the home page</a></p>
</section>
