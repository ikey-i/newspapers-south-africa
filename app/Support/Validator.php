<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Small rule-based validator.
 *
 *   $v = new Validator($_POST);
 *   $v->required('name')->max('name', 160);
 *   $v->required('email')->email('email');
 *   $v->optional('website')->url('website');
 *   if ($v->fails()) { $errors = $v->errors(); }
 *   $clean = $v->validated();   // trimmed values for every field touched
 *
 * The first failing rule for a field wins; later rules on that field are skipped.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, string> */
    private array $data = [];

    /** @var array<string, mixed> */
    private array $input;

    private ?string $label = null;

    /**
     * @param array<string, mixed> $input
     */
    public function __construct(array $input)
    {
        $this->input = $input;
    }

    private function value(string $field): string
    {
        $raw = $this->input[$field] ?? '';
        if (is_array($raw)) {
            return '';
        }
        $value = trim((string) $raw);
        $this->data[$field] = $value;
        return $value;
    }

    private function touched(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /** Give the next rule's field a human label for messages. */
    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    private function name(string $field): string
    {
        $label = $this->label ?? ucfirst(str_replace('_', ' ', $field));
        $this->label = null;
        return $label;
    }

    public function required(string $field): self
    {
        $value = $this->value($field);
        if (!$this->touched($field) && $value === '') {
            $this->errors[$field] = $this->name($field) . ' is required.';
        }
        return $this;
    }

    /** Marks the field as present in output even when empty/optional. */
    public function optional(string $field): self
    {
        $this->value($field);
        return $this;
    }

    public function max(string $field, int $length): self
    {
        $value = $this->value($field);
        if (!$this->touched($field) && $value !== '' && mb_strlen($value) > $length) {
            $this->errors[$field] = $this->name($field) . " must be {$length} characters or fewer.";
        }
        return $this;
    }

    public function min(string $field, int $length): self
    {
        $value = $this->value($field);
        if (!$this->touched($field) && $value !== '' && mb_strlen($value) < $length) {
            $this->errors[$field] = $this->name($field) . " must be at least {$length} characters.";
        }
        return $this;
    }

    public function email(string $field): self
    {
        $value = $this->value($field);
        if (!$this->touched($field) && $value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = 'Enter a valid email address.';
        }
        return $this;
    }

    /** Requires an http(s) URL. */
    public function url(string $field): self
    {
        $value = $this->value($field);
        if ($this->touched($field) || $value === '') {
            return $this;
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)
            || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->errors[$field] = $this->name($field) . ' must be a valid URL starting with http:// or https://.';
        }
        return $this;
    }

    /**
     * @param list<string> $allowed
     */
    public function in(string $field, array $allowed): self
    {
        $value = $this->value($field);
        if (!$this->touched($field) && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = 'Choose one of the available options.';
        }
        return $this;
    }

    /**
     * Record an error from outside the built-in rules (e.g. a cross-field or
     * format check the caller does itself).
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Trimmed values for every field a rule has looked at. Empty string for
     * fields that were optional and not supplied.
     *
     * @return array<string, string>
     */
    public function validated(): array
    {
        return $this->data;
    }
}
