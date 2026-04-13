<?php

declare(strict_types=1);

namespace ZeeshanTariq\FilamentStickyColumns\Concerns;

use ZeeshanTariq\FilamentStickyColumns\FilamentStickyColumnsServiceProvider;

trait HasStickyColumn
{
    /**
     * Whether this column is pinned/sticky.
     */
    protected bool $isSticky = false;

    /**
     * Which side to stick to: 'left' or 'right'.
     */
    protected string $stickyPosition = 'left';

    /**
     * Optional manual offset (px). When null, JS auto-computes it.
     */
    protected ?int $stickyOffset = null;

    /**
     * Z-index override.
     */
    protected ?int $stickyZIndex = null;

    // ──────────────────────────────────────────────────────────────────────────
    // Fluent API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Pin this column to the LEFT edge.
     *
     *   TextColumn::make('name')->sticky()
     *   TextColumn::make('name')->sticky(offset: 48)
     */
    public function sticky(bool $condition = true, ?int $offset = null): static
    {
        $this->isSticky       = $condition;
        $this->stickyPosition = 'left';
        $this->stickyOffset   = $offset;

        $this->applyExtraAttributes();

        return $this;
    }

    /**
     * Pin this column to the RIGHT edge.
     *
     *   TextColumn::make('actions')->stickyRight()
     */
    public function stickyRight(bool $condition = true, ?int $offset = null): static
    {
        $this->isSticky       = $condition;
        $this->stickyPosition = 'right';
        $this->stickyOffset   = $offset;

        $this->applyExtraAttributes();

        return $this;
    }

    /**
     * Override the z-index for this sticky column.
     */
    public function stickyZIndex(int $zIndex): static
    {
        $this->stickyZIndex = $zIndex;

        $this->applyExtraAttributes();

        return $this;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────────────────

    public function isSticky(): bool
    {
        return $this->isSticky;
    }

    public function getStickyPosition(): string
    {
        return $this->stickyPosition;
    }

    public function getStickyOffset(): ?int
    {
        return $this->stickyOffset;
    }

    public function getStickyZIndex(): int
    {
        return $this->stickyZIndex
            ?? config('filament-sticky-columns.z_index', 10);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Inject data-* attributes so the JS engine can discover sticky columns.
     * Uses merge: true (required from Filament v4+) so existing attributes
     * are preserved.
     */
    protected function applyExtraAttributes(): void
    {
        if (! $this->isSticky) {
            return;
        }

        $attrs = [
            'data-sticky'         => $this->stickyPosition,
            'data-sticky-z-index' => $this->getStickyZIndex(),
        ];

        if ($this->stickyOffset !== null) {
            $attrs['data-sticky-offset'] = $this->stickyOffset;
        }

        $filamentVersion = FilamentStickyColumnsServiceProvider::filamentMajorVersion();

        if ($filamentVersion >= 4) {
            // v4+ requires the merge parameter to avoid wiping other attributes
            $this->extraAttributes($attrs, merge: true);
        } else {
            // v3 — merge parameter did not exist but the call is still safe
            $this->extraAttributes($attrs);
        }
    }
}
