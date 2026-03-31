<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Provides a dynamic `app_version` Twig global.
 *
 * - Production: reads APP_VERSION env var (injected at Docker build time by Semantic Release).
 * - Development: uses `git describe` to show the current tag + commit offset + short hash.
 */
final class VersionExtension extends AbstractExtension implements GlobalsInterface
{
    private ?string $resolved = null;

    public function __construct(
        private readonly string $appEnv,
        private readonly string $staticVersion,
        private readonly string $projectDir,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'app_version' => $this->resolve(),
        ];
    }

    private function resolve(): string
    {
        if (null !== $this->resolved) {
            return $this->resolved;
        }

        // In production, prefer the build-time env var (set by CI/CD).
        $envVersion = $_ENV['APP_VERSION'] ?? $_SERVER['APP_VERSION'] ?? null;
        if ($envVersion && '' !== $envVersion) {
            return $this->resolved = $envVersion;
        }

        // In dev, try git describe for a rich version string.
        if ('dev' === $this->appEnv) {
            $version = $this->gitDescribe();
            if (null !== $version) {
                return $this->resolved = $version;
            }
        }

        // Fallback to static parameter.
        return $this->resolved = $this->staticVersion;
    }

    private function gitDescribe(): ?string
    {
        $gitDir = $this->projectDir . '/.git';
        if (!is_dir($gitDir)) {
            return null;
        }

        // Try full describe first (e.g. "v0.3.0-5-gabcdef").
        $result = @exec('git -C ' . escapeshellarg($this->projectDir) . ' describe --tags --always --dirty 2>/dev/null', $output, $exitCode);

        if (0 !== $exitCode || '' === $result) {
            return null;
        }

        // Strip leading "v" prefix if present (we add it in the template).
        return ltrim($result, 'v');
    }
}
