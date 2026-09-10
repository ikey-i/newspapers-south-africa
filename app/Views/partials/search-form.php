<?php
/** @var string $query */
$query = $query ?? '';
$variant = $variant ?? 'default';
?>
<form class="search-form search-form--<?= e($variant) ?>" action="<?= e(url('search')) ?>" method="get" role="search">
    <label class="search-form__label" for="q">Search newspapers and stories</label>
    <input
        class="search-form__input"
        type="search"
        id="q"
        name="q"
        value="<?= e($query) ?>"
        placeholder="Newspaper, town or headline…"
        autocomplete="off"
        maxlength="100">
    <button class="search-form__submit" type="submit">Search</button>
</form>
