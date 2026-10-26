<?php

declare(strict_types=1);

namespace AegisThread\Tests\Functional\Env;

use AegisThread\Infrastructure\Env\BootEnvGuard;
use AegisThread\Infrastructure\Env\EnvContract;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\Exception\EnvNotFoundException;
use Symfony\Component\HttpKernel\KernelEvents;

use function putenv;

/**
 * Fail-fast boot guard (EnvContract + BootEnvGuard): with a required
 * variable removed from every environment source ($_SERVER, $_ENV, process
 * env), booting the kernel and dispatching its first event must throw
 * Symfony's EnvNotFoundException naming the variable.
 *
 * Bootstrap note (verified in this pass): tests/bootstrap.php runs Dotenv
 * bootEnv() ONCE per PHPUnit process, at bootstrap; a kernel boot never
 * reloads .env files and no dotenv env-var loader is attached to the test
 * container. Removing a variable inside the test therefore sticks — the
 * negative tests below would fail if the variables were resurrected.
 */
final class BootEnvGuardTest extends WebTestCase
{
    /** RouterListener's kernel.request priority; the guard must run earlier. */
    private const ROUTER_LISTENER_PRIORITY = 32;

    /**
     * Saved state of every required variable, restored in tearDown.
     *
     * @var array<string, array{putenv: string|false, _ENV: mixed, _SERVER: mixed, _ENV_present: bool, _SERVER_present: bool}>
     */
    private array $savedVariables = [];

    #[DataProvider('requiredVariableProvider')]
    public function testBootingWithoutRequiredVariableThrowsEnvNotFoundException(string $variable): void
    {
        self::ensureKernelShutdown();
        $this->removeVariable($variable);

        try {
            $client = self::createClient();
            $client->catchExceptions(false);
            $client->request('GET', '/');

            self::fail(\sprintf('Kernel booted although required env var "%s" was removed.', $variable));
        } catch (EnvNotFoundException $exception) {
            self::assertStringContainsString(
                $variable,
                $exception->getMessage(),
                'The EnvNotFoundException must name the missing variable.',
            );
        }
    }

    public function testKernelBootsWhenAllRequiredVariablesAreSet(): void
    {
        $client = self::createClient();

        // Positive control: with every variable present, the guard resolves
        // cleanly — the get() below instantiates it — and the kernel serves
        // requests (health answers 503 in the test environment because no
        // database is reachable — that is the endpoint's contract, not a
        // boot failure).
        self::getContainer()->get(BootEnvGuard::class);

        $client->request('GET', '/api/health');
        self::assertSame(503, $client->getResponse()->getStatusCode());
    }

    public function testGuardIsSubscribedVeryEarlyToKernelRequestAndToConsoleCommand(): void
    {
        self::createClient();

        $subscribed = BootEnvGuard::getSubscribedEvents();

        self::assertSame(['onKernelRequest', 4096], $subscribed[KernelEvents::REQUEST]);
        self::assertArrayHasKey(ConsoleEvents::COMMAND, $subscribed);

        $guard = self::getContainer()->get(BootEnvGuard::class);
        $dispatcher = self::getContainer()->get('event_dispatcher');

        self::assertGreaterThan(
            self::ROUTER_LISTENER_PRIORITY,
            $dispatcher->getListenerPriority(KernelEvents::REQUEST, [$guard, 'onKernelRequest']),
            'The guard must fire before routing on kernel.request.',
        );
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function requiredVariableProvider(): \Generator
    {
        foreach (EnvContract::REQUIRED as $name => $purpose) {
            yield $name => [$name];
        }
    }

    protected function setUp(): void
    {
        $this->savedVariables = [];

        foreach (EnvContract::REQUIRED as $name => $purpose) {
            $this->savedVariables[$name] = [
                'putenv' => \getenv($name),
                '_ENV' => $_ENV[$name] ?? null,
                '_SERVER' => $_SERVER[$name] ?? null,
                '_ENV_present' => \array_key_exists($name, $_ENV),
                '_SERVER_present' => \array_key_exists($name, $_SERVER),
            ];
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();

        foreach ($this->savedVariables as $name => $saved) {
            if (false === $saved['putenv']) {
                \putenv($name);
            } else {
                \putenv($name.'='.$saved['putenv']);
            }

            if ($saved['_ENV_present']) {
                $_ENV[$name] = $saved['_ENV'];
            } else {
                unset($_ENV[$name]);
            }

            if ($saved['_SERVER_present']) {
                $_SERVER[$name] = $saved['_SERVER'];
            } else {
                unset($_SERVER[$name]);
            }
        }

        $this->savedVariables = [];

        parent::tearDown();
    }

    private function removeVariable(string $name): void
    {
        \putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    }
}
