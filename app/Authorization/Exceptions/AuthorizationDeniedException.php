<?php

namespace App\Authorization\Exceptions;

use App\Authorization\DTOs\AuthorizationDecision;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Debug\ShouldntReport;

class AuthorizationDeniedException extends AuthorizationException implements ShouldntReport
{
    public function __construct(
        public readonly AuthorizationDecision $decision,
    ) {
        parent::__construct('This action is unauthorized.', $decision->reason);

        $this->setResponse($decision->toLaravelResponse());
        $this->withStatus($decision->status);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'reason' => $this->decision->reason,
            'action' => $this->decision->action->value,
            'permission' => $this->decision->permission instanceof \UnitEnum
                ? $this->decision->permission->value
                : $this->decision->permission,
            'status' => $this->decision->status,
        ];
    }
}
