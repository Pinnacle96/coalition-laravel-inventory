<?php

namespace App;

use Closure;
use Illuminate\Support\Str;
use RuntimeException;

final class InventoryStore
{
    private readonly string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? storage_path('app/private/inventory.json');
    }

    public function all(): array
    {
        return $this->withLock(LOCK_SH, fn () => $this->read());
    }

    public function add(array $entry): array
    {
        return $this->withLock(LOCK_EX, function () use ($entry) {
            $entries = $this->read();
            $entries[] = [
                'id' => (string) Str::uuid(),
                ...$entry,
                'submitted_at' => now()->utc()->format('Y-m-d\TH:i:s.u\Z'),
            ];
            $this->write($entries);

            return $entries;
        });
    }

    public function update(string $id, array $entry): ?array
    {
        return $this->withLock(LOCK_EX, function () use ($id, $entry) {
            $entries = $this->read();

            foreach ($entries as $index => $existing) {
                if ($existing['id'] === $id) {
                    $entries[$index] = array_replace($existing, $entry);
                    $this->write($entries);

                    return $entries;
                }
            }

            return null;
        });
    }

    public static function formatAmount(int $minor, bool $groupThousands = true): string
    {
        $whole = (string) intdiv($minor, 100);
        $fraction = str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);

        if ($groupThousands) {
            $whole = preg_replace('/\B(?=(?:\d{3})+(?!\d))/', ',', $whole);
        }

        return $whole.'.'.$fraction;
    }

    private function withLock(int $mode, Closure $operation): mixed
    {
        $lock = fopen($this->path.'.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Unable to open the inventory lock.');
        }

        try {
            if (! flock($lock, $mode)) {
                throw new RuntimeException('Unable to lock the inventory.');
            }

            return $operation();
        } finally {
            fclose($lock);
        }
    }

    private function read(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $json = file_get_contents($this->path);
        if ($json === false) {
            throw new RuntimeException('Unable to read the inventory.');
        }

        $entries = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($entries) || ! array_is_list($entries) || ! str_starts_with(ltrim($json), '[')) {
            throw new RuntimeException('The inventory should contain a JSON list.');
        }

        return $entries;
    }

    private function write(array $entries): void
    {
        $json = json_encode($entries, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
        $temporary = tempnam(dirname($this->path), '.inventory-');

        if ($temporary === false) {
            throw new RuntimeException('Unable to open a temporary inventory file.');
        }

        try {
            if (file_put_contents($temporary, $json) !== strlen($json) || ! rename($temporary, $this->path)) {
                throw new RuntimeException('Unable to save the inventory.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
