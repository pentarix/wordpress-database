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
use Pentarix\WordPressDatabase\WordPress\PostQuery;
use Pentarix\WordPressDatabase\WordPress\PostQueryArg;

/**
 * Model-aware fluent query adapter over the native PostQuery implementation.
 */
final class ModelQuery
{
    private string $modelClass;

    private PostQueryArg $arguments;

    public function __construct(string $modelClass, PostQueryArg|array $arguments = [])
    {
        if (!is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException('The query model must extend ' . Model::class . '.');
        }

        $this->modelClass = $modelClass;
        $this->arguments = $arguments instanceof PostQueryArg
            ? $arguments
            : PostQueryArg::make($arguments);

        $this->arguments->set('post_type', $modelClass::postType());
        $this->arguments->set('fields', 'all');
    }

    public function set(string $key, mixed $value): self
    {
        if ('post_type' === $key || 'fields' === $key) {
            throw new InvalidArgumentException('The model query manages post_type and fields.');
        }

        $this->arguments->set($key, $value);

        return $this;
    }

    public function setMany(array $arguments): self
    {
        foreach ($arguments as $key => $value) {
            $this->set((string) $key, $value);
        }

        return $this;
    }

    public function author(int|\WP_User $author): self
    {
        $userId = $author instanceof \WP_User ? (int) $author->ID : $author;
        $this->arguments->authorsIn($userId);

        return $this;
    }

    public function status(string|array $statuses): self
    {
        $this->arguments->postStatus($statuses);

        return $this;
    }

    public function where(string $key, mixed $value): self
    {
        return $this->set($key, $value);
    }

    public function orderBy(string|array $column): self
    {
        $this->arguments->orderBy($column);

        return $this;
    }

    public function order(string $direction): self
    {
        $this->arguments->order($direction);

        return $this;
    }

    /** @return array<int, Model> */
    public function get(): array
    {
        $posts = PostQuery::make($this->arguments)->get();

        return array_map(fn (\WP_Post $post): Model => new $this->modelClass($post), $posts);
    }

    public function first(): ?Model
    {
        $post = PostQuery::make($this->arguments)->first();

        return $post instanceof \WP_Post ? new $this->modelClass($post) : null;
    }

    public function count(): int
    {
        return PostQuery::make($this->arguments)->count();
    }

    public function paginate(int $perPage, int $page = 1): ModelPaginator
    {
        $query = PostQuery::make($this->arguments)->paginate($perPage, $page);
        $models = array_map(
            fn (\WP_Post $post): Model => new $this->modelClass($post),
            $query->posts
        );

        return new ModelPaginator(
            $models,
            (int) $query->found_posts,
            $perPage,
            $page,
            (int) $query->max_num_pages
        );
    }
}
