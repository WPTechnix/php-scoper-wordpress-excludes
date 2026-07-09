<?php

declare(strict_types=1);

namespace WPTechnix\PhpScoperWordPressExcludesBuild;

/**
 * Reads/writes symbols/versions.json - the record of which upstream
 * composer package version(s) each generated package's symbols were last
 * produced from. Used to skip re-parsing a package whose upstream
 * version(s) have not changed since the last run.
 */
final class VersionsFile
{
    /** @var array<string, array{packages: array<string, string>, generated_at: string}> */
    private array $data;

    /**
     * @param array<string, array{packages: array<string, string>, generated_at: string}> $data
     */
    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function load(string $path): self
    {
        if (! is_file($path)) {
            return new self([]);
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            return new self([]);
        }

        $decoded = json_decode($contents, true);

        return new self(is_array($decoded) ? $decoded : []);
    }

    /**
     * @param array<string, string> $resolved
     */
    public function isUnchanged(string $package, array $resolved): bool
    {
        $previous = $this->data[$package]['packages'] ?? null;

        if (! is_array($previous)) {
            return false;
        }

        return $this->normalize($previous) === $this->normalize($resolved);
    }

    /**
     * @param array<string, string> $resolved
     */
    public function record(string $package, array $resolved, string $generatedAt): void
    {
        $this->data[$package] = [
            'packages' => $this->normalize($resolved),
            'generated_at' => $generatedAt,
        ];
    }

    public function save(string $path): void
    {
        ksort($this->data);

        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        file_put_contents($path, $json . "\n");
    }

    /**
     * @param array<string, string> $versions
     * @return array<string, string>
     */
    private function normalize(array $versions): array
    {
        ksort($versions);

        return $versions;
    }
}
