<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi;

class AdminPageData
{
    public function __construct(
        protected string $templatePath,
        protected array $viewData = []
    ) {
    }

    public function templatePath(): string
    {
        return $this->templatePath;
    }

    public function viewData(): array
    {
        return $this->viewData;
    }
}
