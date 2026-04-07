<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

class ListFooterConfig
{
    public function __construct(
        protected string $countHtml = '',
        protected string $linksHtml = '',
        protected ?string $primaryActionHref = null,
        protected ?string $primaryActionLabel = null
    ) {
    }

    public function countHtml(): string
    {
        return $this->countHtml;
    }

    public function linksHtml(): string
    {
        return $this->linksHtml;
    }

    public function primaryActionHref(): ?string
    {
        return $this->primaryActionHref;
    }

    public function primaryActionLabel(): ?string
    {
        return $this->primaryActionLabel;
    }

    public function hasContent(): bool
    {
        return $this->countHtml !== ''
            || $this->linksHtml !== ''
            || ($this->primaryActionHref !== null && $this->primaryActionLabel !== null);
    }
}
