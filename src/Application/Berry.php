<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Psr\Container\ContainerInterface;
use YSOCode\Berry\Infra\Request;

final readonly class Berry
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ContainerInterface $container
    ) {}

    public function run(Request $request): void
    {
        $response = $this->dispatcher->dispatch($request, $this->container);

        http_response_code($response->status->value);

        foreach ($response->headers as $name => $header) {
            $valueAsString = implode(', ', $header->value);
            header("{$name}: {$valueAsString}");
        }

        echo $response->body;
    }
}
