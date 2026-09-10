<?php
/**
 * A labelled form control with inline error text.
 *
 * @var string $name
 * @var string $label
 * @var string $type       text|email|url|textarea|select   (default: text)
 * @var bool   $required
 * @var string $value
 * @var string $error
 * @var string $hint
 * @var array<string,string>|list<string> $options   for type=select
 * @var string $placeholder
 */
$name = $name ?? '';
$label = $label ?? '';
$type = $type ?? 'text';
$required = $required ?? false;
$value = $value ?? old($name);
$error = $error ?? '';
$hint = $hint ?? '';
$placeholder = $placeholder ?? '';
$id = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
$describedby = array_filter([
    $hint !== '' ? $id . '-hint' : null,
    $error !== '' ? $id . '-error' : null,
]);
?>
<div class="field<?= $error !== '' ? ' field--error' : '' ?>">
    <label class="field__label" for="<?= e($id) ?>">
        <?= e($label) ?><?php if ($required): ?> <span class="field__req" aria-hidden="true">*</span><?php endif ?>
    </label>

    <?php if ($hint !== ''): ?>
        <p class="field__hint" id="<?= e($id) ?>-hint"><?= e($hint) ?></p>
    <?php endif ?>

    <?php if ($type === 'textarea'): ?>
        <textarea
            class="field__control" id="<?= e($id) ?>" name="<?= e($name) ?>"
            rows="<?= (int) ($rows ?? 5) ?>"
            <?= $required ? 'required' : '' ?>
            <?= $describedby ? 'aria-describedby="' . e(implode(' ', $describedby)) . '"' : '' ?>
        ><?= e($value) ?></textarea>

    <?php elseif ($type === 'select'): ?>
        <select
            class="field__control" id="<?= e($id) ?>" name="<?= e($name) ?>"
            <?= $required ? 'required' : '' ?>
            <?= $describedby ? 'aria-describedby="' . e(implode(' ', $describedby)) . '"' : '' ?>
        >
            <option value=""><?= e($placeholder ?: 'Choose…') ?></option>
            <?php foreach (($options ?? []) as $optValue => $optLabel): ?>
                <?php $ov = is_int($optValue) ? $optLabel : $optValue; ?>
                <option value="<?= e($ov) ?>"<?= $value === (string) $ov ? ' selected' : '' ?>><?= e($optLabel) ?></option>
            <?php endforeach ?>
        </select>

    <?php else: ?>
        <input
            class="field__control" type="<?= e($type) ?>" id="<?= e($id) ?>" name="<?= e($name) ?>"
            value="<?= e($value) ?>"
            <?= $placeholder !== '' ? 'placeholder="' . e($placeholder) . '"' : '' ?>
            <?= $required ? 'required' : '' ?>
            <?= $describedby ? 'aria-describedby="' . e(implode(' ', $describedby)) . '"' : '' ?>
        >
    <?php endif ?>

    <?php if ($error !== ''): ?>
        <p class="field__error" id="<?= e($id) ?>-error"><?= e($error) ?></p>
    <?php endif ?>
</div>
