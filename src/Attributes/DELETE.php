<?php

namespace AOWD\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class DELETE
{
    public function __construct(
        public string $path,
        public private(set) string $method = 'DELETE'
    ) {
    }
}
