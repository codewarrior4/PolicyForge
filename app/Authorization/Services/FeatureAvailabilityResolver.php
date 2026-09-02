<?php

namespace App\Authorization\Services;

use App\Authorization\DTOs\AuthorizationContext;

class FeatureAvailabilityResolver
{
    public function enabled(AuthorizationContext $context): bool
    {
        if ($context->feature === null) {
            return true;
        }

        if (array_key_exists('feature_enabled', $context->metadata)) {
            return $context->metadata['feature_enabled'] === true;
        }

        $features = $context->metadata['features'] ?? [];

        if (is_array($features) && array_key_exists($context->feature, $features)) {
            return $features[$context->feature] === true;
        }

        return true;
    }
}
