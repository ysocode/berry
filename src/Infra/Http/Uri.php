<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use YSOCode\Berry\Domain\ValueObjects\Fragment;
use YSOCode\Berry\Domain\ValueObjects\Host;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Port;
use YSOCode\Berry\Domain\ValueObjects\Query;
use YSOCode\Berry\Domain\ValueObjects\Scheme;
use YSOCode\Berry\Domain\ValueObjects\UserInfo;

final class Uri
{
    public function __construct(
        private(set) Scheme $scheme,
        private(set) Host $host,
        private(set) Port $port,
        private(set) Path $path,
        private(set) ?UserInfo $userInfo = null,
        private(set) ?Query $query = null,
        private(set) ?Fragment $fragment = null,
    ) {}

    public function getAuthority(): string
    {
        if (! $this->userInfo instanceof UserInfo) {
            return sprintf('%s:%s', $this->host, $this->port->value);
        }

        return sprintf('%s@%s:%s', $this->userInfo, $this->host, $this->port->value);
    }
}
