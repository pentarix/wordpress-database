<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/ephpicman LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\WordPressDatabase\WordPress;

trait SupportsQueryArguments
{
    private array $originalQueryArgs = [];

    private array $additionalQueryArgs = [];

    protected function initialiseQueryArguments(array $arguments): void
    {
        $this->originalQueryArgs = $arguments;
    }

    /**
     * Set or override a WordPress query argument.
     */
    public function set(string $key, mixed $value): static
    {
        if ('' === $key) {
            throw new \InvalidArgumentException('Query argument keys cannot be empty.');
        }

        $this->additionalQueryArgs[$key] = $value;

        return $this;
    }

    /**
     * Set or override multiple WordPress query arguments.
     */
    public function setMany(array $arguments): static
    {
        foreach ($arguments as $key => $value) {
            if (!\is_string($key) || '' === $key) {
                throw new \InvalidArgumentException('Query argument keys must be non-empty strings.');
            }

            $this->additionalQueryArgs[$key] = $value;
        }

        return $this;
    }

    protected function mergeQueryArguments(array $arguments): array
    {
        return \array_replace(
            $this->originalQueryArgs,
            $arguments,
            $this->additionalQueryArgs
        );
    }
}
