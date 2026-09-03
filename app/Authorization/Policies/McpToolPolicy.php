<?php

namespace App\Authorization\Policies;

use App\Authorization\DTOs\McpAuthorizationContext;
use App\Authorization\Services\CanExecuteMcpTool;
use Illuminate\Auth\Access\Response;

class McpToolPolicy
{
    public function __construct(
        private readonly CanExecuteMcpTool $canExecuteMcpTool,
    ) {}

    public function execute(McpAuthorizationContext $context): Response
    {
        return $this->canExecuteMcpTool
            ->inspect($context)
            ->toLaravelResponse();
    }
}
