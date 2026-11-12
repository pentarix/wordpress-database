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

use InvalidArgumentException;
use LogicException;
use Pentarix\WordPressDatabase\WordPress\Metadata;
use Pentarix\WordPressDatabase\WordPress\Post;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;
use Pentarix\WordPressDatabase\WordPress\User;

/**
 * Fluent, stateful representation of a registered WordPress post.
 *
 * Post-table fields and post metadata share one attribute interface. The
 * model routes each attribute to the appropriate native WordPress API.
 */
abstract class Model
{
    protected static string $postType = 'post';

    /**
     * Fields stored directly in the wp_posts table.
     *
     * ID and post_type are managed separately because they identify and
     * constrain the model rather than being freely assignable attributes.
     *
     * @var array<int, string>
     */
    private const POST_FIELDS = [
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_password',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_mime_type',
        'comment_count',
    ];

    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<string, mixed> */
    private array $metaAttributes = [];

    /** @var array<string, true> */
    private array $dirtyMetaAttributes = [];

    /** @var array<string, true> */
    private array $deletedMetaAttributes = [];

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
        $authorId = (int) $this->get('post_author', 0);

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

    /**
     * Retrieve a post field or metadata value through one attribute interface.
     *
     * Metadata is loaded lazily and cached for the lifetime of this instance.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ('ID' === $key) {
            return $this->postId ?? $default;
        }

        if ('post_type' === $key) {
            return static::$postType;
        }

        if (in_array($key, self::POST_FIELDS, true)) {
            return array_key_exists($key, $this->attributes)
                ? $this->attributes[$key]
                : $default;
        }

        if (array_key_exists($key, $this->metaAttributes)) {
            return $this->metaAttributes[$key];
        }

        if (isset($this->deletedMetaAttributes[$key])) {
            return $default;
        }

        if (null === $this->postId || !Metadata::exists('post', $this->postId, $key)) {
            return $default;
        }

        $value = Metadata::get('post', $this->postId, $key, true);
        $this->metaAttributes[$key] = $value;

        return $value;
    }

    public function has(string $key): bool
    {
        if ('ID' === $key) {
            return null !== $this->postId;
        }

        if ('post_type' === $key) {
            return true;
        }

        if (in_array($key, self::POST_FIELDS, true)) {
            return array_key_exists($key, $this->attributes);
        }

        if (array_key_exists($key, $this->metaAttributes)) {
            return true;
        }

        if (isset($this->deletedMetaAttributes[$key])) {
            return false;
        }

        return null !== $this->postId && Metadata::exists('post', $this->postId, $key);
    }

    /**
     * Set a post-table field or metadata value without exposing its storage.
     */
    public function set(string $key, mixed $value): static
    {
        if ('' === $key || 'ID' === $key || 'post_type' === $key) {
            throw new InvalidArgumentException('The model key is empty or managed by the model.');
        }

        if (in_array($key, self::POST_FIELDS, true)) {
            $this->attributes[$key] = $value;

            return $this;
        }

        $this->metaAttributes[$key] = $value;
        $this->dirtyMetaAttributes[$key] = true;
        unset($this->deletedMetaAttributes[$key]);

        return $this;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Model attribute keys must be strings.');
            }

            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Remove a metadata attribute when the model is next saved.
     *
     * Core post-table fields cannot be removed through this method.
     */
    public function removeAttribute(string $key): static
    {
        if ('' === $key || in_array($key, self::POST_FIELDS, true) || 'ID' === $key || 'post_type' === $key) {
            throw new InvalidArgumentException('Only metadata attributes can be removed.');
        }

        unset($this->metaAttributes[$key], $this->dirtyMetaAttributes[$key]);

        if (null !== $this->postId) {
            $this->deletedMetaAttributes[$key] = true;
        } else {
            unset($this->deletedMetaAttributes[$key]);
        }

        return $this;
    }

    /**
     * Return core fields and metadata values already loaded or assigned.
     *
     * This method does not fetch every metadata row; metadata remains lazy.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'ID' => $this->postId,
                'post_type' => static::$postType,
            ],
            $this->attributes,
            $this->metaAttributes
        );
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function __isset(string $key): bool
    {
        return null !== $this->get($key);
    }

    public function __unset(string $key): void
    {
        $this->removeAttribute($key);
    }

    /**
     * Persist core fields and metadata through their respective Core APIs.
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
        $knownMetaKeys = array_keys($this->metaAttributes);

        foreach ($this->dirtyMetaAttributes as $key => $_) {
            Metadata::update('post', $this->postId, $key, $this->metaAttributes[$key]);
        }

        foreach ($this->deletedMetaAttributes as $key => $_) {
            Metadata::delete('post', $this->postId, $key);
        }

        $deletedMetaKeys = array_keys($this->deletedMetaAttributes);
        $this->dirtyMetaAttributes = [];
        $this->deletedMetaAttributes = [];

        if (!$this->refresh()) {
            return false;
        }

        foreach (array_diff($knownMetaKeys, $deletedMetaKeys) as $key) {
            $this->get($key);
        }

        return true;
    }

    /** Delete the underlying post using WordPress's native deletion behaviour. */
    public function delete(bool $forceDelete = false): \WP_Post|false|null
    {
        if (null === $this->postId) {
            return false;
        }

        return Post::delete($this->postId, $forceDelete);
    }

    /** Reload the post and discard unsaved changes to both fields and metadata. */
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
        $record = $post instanceof \WP_Post ? $post : Post::get($post);

        if (!$record instanceof \WP_Post) {
            throw new LogicException('The supplied post could not be retrieved.');
        }

        if (static::$postType !== $record->post_type) {
            throw new InvalidArgumentException('The post type does not match this model.');
        }

        $this->postId = (int) $record->ID;
        $this->attributes = array_intersect_key(
            get_object_vars($record),
            array_fill_keys(self::POST_FIELDS, true)
        );

        $this->metaAttributes = [];
        $this->dirtyMetaAttributes = [];
        $this->deletedMetaAttributes = [];
    }
}
