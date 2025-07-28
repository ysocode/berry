<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use Psr\Container\ContainerInterface;

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

        foreach ($response->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if ($response->body !== null && $response->body !== '') {
            echo $response->body;
        }
    }
}
