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

class CommentQueryArg implements QueryArg
{
    private IncludeExcludeQueryArg $commentIds;
    private IncludeExcludeQueryArg $postIds;
    private IncludeExcludeQueryArg $parentIds;
    private IncludeExcludeQueryArg $userIds;
    private ?int $postId = null;
    private ?int $parent = null;
    private ?int $author = null;
    private ?string $authorEmail = null;
    private ?string $authorUrl = null;
    private ?string $authorIp = null;
    private ?string $search = null;
    private ?string $type = null;
    private ?string $status = null;
    private ?int $number = null;
    private ?int $offset = null;
    private string|array|null $orderby = null;
    private ?string $order = null;
    private ?string $fields = null;
    private ?bool $hierarchical = null;
    private ?bool $count = null;
    private ?bool $noFoundRows = null;
    private ?bool $updateCommentMetaCache = null;
    private ?bool $updateCommentPostCache = null;

    public function __construct(array $queryArray = [])
    {
        $this->commentIds = IncludeExcludeQueryArg::make(['comment__in' => $queryArray['comment__in'] ?? [], 'comment__not_in' => $queryArray['comment__not_in'] ?? []], ['includeKey' => 'comment__in', 'excludeKey' => 'comment__not_in']);
        $this->postIds = IncludeExcludeQueryArg::make(['post__in' => $queryArray['post__in'] ?? [], 'post__not_in' => $queryArray['post__not_in'] ?? []], ['includeKey' => 'post__in', 'excludeKey' => 'post__not_in']);
        $this->parentIds = IncludeExcludeQueryArg::make(['parent__in' => $queryArray['parent__in'] ?? [], 'parent__not_in' => $queryArray['parent__not_in'] ?? []], ['includeKey' => 'parent__in', 'excludeKey' => 'parent__not_in']);
        $this->userIds = IncludeExcludeQueryArg::make(['user__in' => $queryArray['user__in'] ?? [], 'user__not_in' => $queryArray['user__not_in'] ?? []], ['includeKey' => 'user__in', 'excludeKey' => 'user__not_in']);
        if (array_key_exists('post_id', $queryArray)) {
            $this->postId = (int) $queryArray['post_id'];
        }
        if (array_key_exists('parent', $queryArray)) {
            $this->parent = (int) $queryArray['parent'];
        }
        if (array_key_exists('user_id', $queryArray)) {
            $this->author = (int) $queryArray['user_id'];
        }
        if (array_key_exists('author_email', $queryArray)) {
            $this->authorEmail = (string) $queryArray['author_email'];
        }
        if (array_key_exists('author_url', $queryArray)) {
            $this->authorUrl = (string) $queryArray['author_url'];
        }
        if (array_key_exists('author_ip', $queryArray)) {
            $this->authorIp = (string) $queryArray['author_ip'];
        }
        if (array_key_exists('search', $queryArray)) {
            $this->search = (string) $queryArray['search'];
        }
        if (array_key_exists('type', $queryArray)) {
            $this->type = (string) $queryArray['type'];
        }
        if (array_key_exists('status', $queryArray)) {
            $this->status = (string) $queryArray['status'];
        }
        if (array_key_exists('number', $queryArray)) {
            $this->number = (int) $queryArray['number'];
        }
        if (array_key_exists('offset', $queryArray)) {
            $this->offset = (int) $queryArray['offset'];
        }
        if (array_key_exists('orderby', $queryArray)) {
            $this->orderby = $queryArray['orderby'];
        }
        if (array_key_exists('order', $queryArray)) {
            $this->order = (string) $queryArray['order'];
        }
        if (array_key_exists('fields', $queryArray)) {
            $this->fields = (string) $queryArray['fields'];
        }
        if (array_key_exists('hierarchical', $queryArray)) {
            $this->hierarchical = (bool) $queryArray['hierarchical'];
        }
        if (array_key_exists('count', $queryArray)) {
            $this->count = (bool) $queryArray['count'];
        }
        if (array_key_exists('no_found_rows', $queryArray)) {
            $this->noFoundRows = (bool) $queryArray['no_found_rows'];
        }
        if (array_key_exists('update_comment_meta_cache', $queryArray)) {
            $this->updateCommentMetaCache = (bool) $queryArray['update_comment_meta_cache'];
        }
        if (array_key_exists('update_comment_post_cache', $queryArray)) {
            $this->updateCommentPostCache = (bool) $queryArray['update_comment_post_cache'];
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray);
    }

    public function includeComments(int|array $ids): self
    {
        $this->commentIds->include($ids);

        return $this;
    }

    public function excludeComments(int|array $ids): self
    {
        $this->commentIds->exclude($ids);

        return $this;
    }

    public function postId(int $id): self
    {
        $this->postId = $id;

        return $this;
    }

    public function search(string $value): self
    {
        $this->search = $value;

        return $this;
    }

    public function toArray(): array
    {
        $args = [];
        $args += $this->commentIds->toArray();
        $args += $this->postIds->toArray();
        $args += $this->parentIds->toArray();
        $args += $this->userIds->toArray();
        $values = [
            'post_id' => $this->postId,
            'parent' => $this->parent,
            'user_id' => $this->author,
            'author_email' => $this->authorEmail,
            'author_url' => $this->authorUrl,
            'author_ip' => $this->authorIp,
            'search' => $this->search,
            'type' => $this->type,
            'status' => $this->status,
            'number' => $this->number,
            'offset' => $this->offset,
            'orderby' => $this->orderby,
            'order' => $this->order,
            'fields' => $this->fields,
            'hierarchical' => $this->hierarchical,
            'count' => $this->count,
            'no_found_rows' => $this->noFoundRows,
            'update_comment_meta_cache' => $this->updateCommentMetaCache,
            'update_comment_post_cache' => $this->updateCommentPostCache,
        ];
        foreach ($values as $key => $value) {
            if (null !== $value) {
                $args[$key] = $value;
            }
        }

        return $args;
    }
}
