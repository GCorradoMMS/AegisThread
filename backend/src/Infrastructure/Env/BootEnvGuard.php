<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Env;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Fail-fast environment guard.
 *
 * Two enforcement layers, both failing at the very first kernel.request
 * (highest priority, before routing) or console.command event:
 *
 *  1. Container binding: the required values are constructor arguments
 *     resolved through %env()%. A truly missing variable throws Symfony's
 *     EnvNotFoundException naming the variable during argument resolution.
 *  2. Presence assertion (constructor body): FrameworkBundle's secrets
 *     vault loader synthesizes a DERIVED APP_SECRET value when the variable
 *     is absent from the environment, so %env(APP_SECRET)% resolves to ''
 *     instead of throwing. The guard therefore asserts presence on the real
 *     environment sources itself and throws the same EnvNotFoundException.
 *     Vault-synthesized placeholders do not satisfy the environment
 *     contract (vault-based secret deployment is out of scope here).
 *
 * Guard body: a presence assertion only. The values are never logged or
 * stored beyond the (discarded) constructor arguments.
 */
final class BootEnvGuard implements EventSubscriberInterface
{
    /**
     * Very early kernel.request priority: runs before routing (32), session
     * (128), and every standard FrameworkBundle listener.
     */
    private const KERNEL_REQUEST_PRIORITY = 4096;

    // Both values are presence bindings only: the container fail-fasts on
    // them during env resolution, and the guard never reads or stores them.
    // @phpstan-ignore constructor.unusedParameter, constructor.unusedParameter (presence bindings only: enforcement lives in resolution + assertPresence, values are never read)
    public function __construct(
        string $databaseUrl,
        string $appSecret,
    ) {
        // What the container forgives (the vault's derived APP_SECRET) is
        // caught by the presence assertion below.
        $this->assertPresence();
    }

    public function onKernelRequest(): void
    {
        // no-op — presence enforced at construction
    }

    public function onConsoleCommand(): void
    {
        // no-op — presence enforced at construction
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', self::KERNEL_REQUEST_PRIORITY],
            ConsoleEvents::COMMAND => ['onConsoleCommand', 0],
        ];
    }

    private function assertPresence(): void
    {
        foreach (EnvContract::REQUIRED as $name => $purpose) {
            if (!isset($_ENV[$name])
                && !isset($_SERVER[$name])
                && false === getenv($name)
            ) {
                // Same exception and message shape as the container's own
                // %env()% resolution failure.
                throw new EnvNotFoundException(\sprintf('Environment variable not found: "%s".', $name));
            }
        }
    }
}
