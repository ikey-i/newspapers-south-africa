<?php
/** @var string $heading */
/** @var string $body */
/** @var array{href:string,label:string}|null $link */
?>
<div class="auth-wrap">
    <h1><?= e($heading) ?></h1>
    <p><?= e($body) ?></p>
    <?php if (!empty($link)): ?>
        <p class="auth-alt"><a class="btn btn--primary" href="<?= e($link['href']) ?>"><?= e($link['label']) ?></a></p>
    <?php endif ?>
</div>
