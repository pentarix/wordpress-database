<?php

/**
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @copyright 2026 Sina Kuhestani (EphpicMan)
 * @link      https://github.com/ephpicman GitHub
 * @link      https://www.linkedin.com/in/sinakuhestani LinkedIn
 * @license   See the LICENSE file in the project root.
 */

declare(strict_types=1);

namespace Pentarix\WordPressDatabase\Model;

use LogicException;
use Pentarix\WordPressDatabase\WordPress\Post;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;
use Pentarix\WordPressDatabase\WordPress\User;

/**
 * Fluent, stateful representation of a registered WordPress post.
 *
 * Subclasses define their post type; persistence and retrieval are delegated
 * to the package's native Post API.
 */
abstract class Model
{
    protected static string $postType = 'post';

    /** @var array<string, mixed> */
    protected array $attributes = [];

    protected ?int $postId = null;

    public function __construct(int|\WP_Post|null $post = null)
    {
        if (null !== $post) {
            $this->load($post);
        }
    }

    public static function make(array $attributes = []): static
    {
        $model = new static();
        $model->fill($attributes);

        return $model;
    }

    public static function postType(): string
    {
        return static::$postType;
    }

    public static function find(int $id): ?static
    {
        $post = Post::get($id);

        if (!$post instanceof \WP_Post || static::$postType !== $post->post_type) {
            return null;
        }

        return new static($post);
    }

    public static function query(PostQueryArg|array $arguments = []): ModelQuery
    {
        return new ModelQuery(static::class, $arguments);
    }

    public static function create(array $attributes): static|\WP_Error
    {
        $model = static::make($attributes);
        $result = $model->save();

        if ($result instanceof \WP_Error) {
            return $result;
        }

        if (true !== $result) {
            throw new LogicException('The model could not be created.');
        }

        return $model;
    }

    /** Retrieve the post author through the native User entity API. */
    public function author(): \WP_User|false
    {
        $authorId = (int) ($this->attributes['post_author'] ?? 0);

        return 0 < $authorId ? User::get($authorId) : false;
    }

    public function id(): ?int
    {
        return $this->postId;
    }

    public function exists(): bool
    {
        return null !== $this->postId;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function set(string $key, mixed $value): static
    {
        if ('' === $key || 'ID' === $key || 'post_type' === $key) {
            throw new \InvalidArgumentException('The model key is empty or managed by the model.');
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('Model attribute keys must be strings.');
            }

            $this->set($key, $value);
        }

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Persist the model through wp_insert_post() or wp_update_post().
     *
     * @return bool|\WP_Error
     */
    public function save(): bool|\WP_Error
    {
        $data = $this->attributes;
        $data['post_type'] = static::$postType;

        if (null === $this->postId) {
            $result = Post::create($data, true);
        } else {
            $data['ID'] = $this->postId;
            $result = Post::update($data, true);
        }

        if ($result instanceof \WP_Error) {
            return $result;
        }

        if (!is_int($result) || $result < 1) {
            return false;
        }

        $this->postId = $result;

        return $this->refresh();
    }

    /** Delete the underlying post using WordPress's native deletion behaviour. */
    public function delete(bool $forceDelete = false): \WP_Post|false|null
    {
        if (null === $this->postId) {
            return false;
        }

        return Post::delete($this->postId, $forceDelete);
    }

    /** Reload the current post and discard unsaved attribute changes. */
    public function refresh(): bool
    {
        if (null === $this->postId) {
            return false;
        }

        $post = Post::get($this->postId);

        if (!$post instanceof \WP_Post || static::$postType !== $post->post_type) {
            return false;
        }

        $this->load($post);

        return true;
    }

    protected function load(int|\WP_Post $post): void
    {
        $record = Post::get($post);

        if (!$record instanceof \WP_Post) {
            throw new LogicException('The supplied post could not be retrieved.');
        }

        if (static::$postType !== $record->post_type) {
            throw new \InvalidArgumentException('The post type does not match this model.');
        }

        $this->postId = (int) $record->ID;
        $this->attributes = get_object_vars($record);
        unset($this->attributes['ID'], $this->attributes['post_type']);
    }
}
