<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

class ReportViewConfig
{
    public function __construct(
        protected string $summaryHtml = '',
        protected string $emptyStateHtml = '',
        protected string $afterHtml = ''
    ) {
    }

    public function summaryHtml(): string
    {
        return $this->summaryHtml;
    }

    public function emptyStateHtml(): string
    {
        return $this->emptyStateHtml;
    }

    public function afterHtml(): string
    {
        return $this->afterHtml;
    }
}
