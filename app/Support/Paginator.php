<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pagination value object: page maths plus the data for rendering a
 * "Previous / 1 2 3 / Next" control.
 */
final class Paginator
{
    public int $perPage;
    public int $currentPage;
    public int $totalPages;
    public int $offset;

    public function __construct(
        public int $totalItems,
        int $perPage = 24,
        int $requestedPage = 1,
        public string $baseUrl = '',
    ) {
        $this->perPage     = max(1, $perPage);
        $this->totalPages  = max(1, (int) ceil($this->totalItems / $this->perPage));
        $this->currentPage = min(max(1, $requestedPage), $this->totalPages);
        $this->offset      = ($this->currentPage - 1) * $this->perPage;
    }

    public static function fromQuery(int $totalItems, int $perPage, string $baseUrl): self
    {
        return new self($totalItems, $perPage, (int) ($_GET['page'] ?? 1), $baseUrl);
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }

    public function pageUrl(int $page): string
    {
        $page = min(max(1, $page), $this->totalPages);
        $separator = str_contains($this->baseUrl, '?') ? '&' : '?';
        return $page === 1
            ? $this->baseUrl
            : $this->baseUrl . $separator . 'page=' . $page;
    }

    /**
     * A compact window of page numbers around the current page.
     *
     * @return list<int>
     */
    public function window(int $radius = 2): array
    {
        return range(
            max(1, $this->currentPage - $radius),
            min($this->totalPages, $this->currentPage + $radius)
        );
    }

    public function firstItem(): int
    {
        return $this->totalItems === 0 ? 0 : $this->offset + 1;
    }

    public function lastItem(): int
    {
        return min($this->offset + $this->perPage, $this->totalItems);
    }
}
