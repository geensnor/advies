<?php

declare(strict_types=1);

namespace Geensnor\Advies;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ApiFout', description: 'Foutmelding bij ongeldige invoer')]
final readonly class ApiFout
{
    public function __construct(
        #[OA\Property(example: 'invalid_input')]
        public string $error,

        #[OA\Property(example: 'Missing latitude or longitude parameters.')]
        public string $message,
    ) {}
}
