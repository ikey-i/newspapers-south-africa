<?php
/**
 * A newspaper's logo, or a coloured placeholder tile with its initials.
 *
 * @var array<string,mixed> $paper
 * @var string $size   sm|md|lg
 */
$size = $size ?? 'md';
$name = (string) ($paper['name'] ?? '');
$logo = trim((string) ($paper['logo_path'] ?? ''));
?>
<?php if ($logo !== ''): ?>
    <img class="masthead masthead--<?= e($size) ?>" src="<?= e(url($logo)) ?>" alt="<?= e($name) ?>" loading="lazy">
<?php else: ?>
    <span class="masthead masthead--<?= e($size) ?> masthead--tile" style="<?= e(brand_tile_style($name)) ?>" aria-hidden="true">
        <?= e(masthead_initials($name)) ?>
    </span>
<?php endif ?>
