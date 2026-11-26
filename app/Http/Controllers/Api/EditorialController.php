<?php

namespace App\Http\Controllers\Api;

use App\Actions\GenerateSitemap;
use App\Actions\SaveContentBlocks;
use App\Enums\PageStatus;
use App\Enums\PostStatus;
use App\Enums\PostTCStyle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EditorialCategoryRequest;
use App\Http\Requests\Api\EditorialPageRequest;
use App\Http\Requests\Api\EditorialPostRequest;
use App\Http\Requests\Api\EditorialRedirectRequest;
use App\Models\Author;
use App\Models\Category;
use App\Models\EditorialApiEvent;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Services\EditorialApiService;
use App\Services\EditorialHtmlSanitizer;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class EditorialController extends Controller
{
    public function __construct(
        private readonly EditorialApiService $events,
        private readonly EditorialHtmlSanitizer $sanitizer,
        private readonly PostService $posts,
    ) {}

    public function posts(Request $request): JsonResponse
    {
        $filters = $request->validate(['updated_after' => ['nullable', 'date'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);
        $query = Post::query()->with(['category:id,name,slug,parent_id', 'tags:id,name'])->orderByDesc('updated_at')->orderByDesc('id');
        if (isset($filters['updated_after'])) {
            $query->where('posts.updated_at', '>=', $filters['updated_after']);
        }

        return response()->json($query->paginate($filters['per_page'] ?? 50)->through(fn (Post $post): array => $this->postInventory($post)));
    }

    public function post(int $id): JsonResponse
    {
        $post = Post::query()->with(['category:id,name,slug,parent_id', 'tags:id,name', 'blocks.items', 'info'])->findOrFail($id);

        return response()->json([...$this->postInventory($post),
            'body_blocks' => $post->blocks->sortBy('order')->map(fn ($block): array => [
                'id' => $block->id,
                'ident' => $block->ident,
                'name' => $block->name,
                'order' => $block->order,
                'items' => $block->items->sortBy('order')->map(fn ($item): array => ['id' => $item->id, 'type' => $item->type->value, 'value' => $item->value])->values()->all(),
            ])->values()->all(),
            'source_url' => $post->info?->source,
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json(Category::query()->orderBy('id')->get()->map(fn (Category $category): array => [
            'id' => $category->id, 'editorial_key' => $category->editorial_key, 'parent_id' => $category->parent_id,
            'name' => $category->name, 'slug' => $category->slug, 'description' => $category->description,
            'meta_title' => $category->meta_title, 'meta_description' => $category->meta_description,
            'in_menu' => (bool) $category->in_menu, 'order' => $category->order,
            'url' => route('categories.show', $category), 'updated_at' => $category->updated_at?->toIso8601String(),
        ]));
    }

    public function pages(): JsonResponse
    {
        return response()->json(Page::query()->orderBy('id')->get()->map(fn (Page $page): array => [
            'id' => $page->id, 'editorial_key' => $page->editorial_key, 'title' => $page->title,
            'link' => $page->link, 'content' => $page->content, 'status' => strtolower($page->status->name),
            'meta_title' => $page->meta_title, 'meta_description' => $page->meta_description,
            'url' => url($page->link), 'updated_at' => $page->updated_at?->toIso8601String(),
        ]));
    }

    public function events(Request $request): JsonResponse
    {
        $filters = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]);

        return response()->json(EditorialApiEvent::query()->orderByDesc('id')->paginate($filters['per_page'] ?? 50));
    }

    public function eventByRequest(string $requestId): JsonResponse
    {
        $event = EditorialApiEvent::query()->where('request_id', $requestId)->first();
        if ($event === null) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'request_id' => $event->request_id, 'editorial_key' => $event->editorial_key,
            'entity_type' => $event->entity_type, 'receipt' => $event->result,
        ]);
    }

    public function redirects(): JsonResponse
    {
        return response()->json(Redirect::query()->whereNotNull('editorial_key')->orderByDesc('id')->get()
            ->map(fn (Redirect $redirect): array => [
                'id' => $redirect->id, 'editorial_key' => $redirect->editorial_key,
                'from' => $redirect->from, 'to' => $redirect->to, 'code' => (int) $redirect->code,
                'is_active' => (bool) $redirect->is_active, 'url' => url($redirect->from),
                'updated_at' => $redirect->updated_at?->toIso8601String(),
            ]));
    }

    public function upsertRedirect(EditorialRedirectRequest $request, string $editorialKey): JsonResponse
    {
        $data = $request->validated();
        $result = $this->events->mutate('redirect', $editorialKey, $data['request_id'], $data, function () use ($data, $editorialKey): array {
            $source = Post::query()->lockForUpdate()->findOrFail($data['source_post_id']);
            $target = Post::query()->findOrFail($data['target_post_id']);
            $redirect = Redirect::query()->where('editorial_key', $editorialKey)->lockForUpdate()->first();

            if ($target->status !== PostStatus::PUBLISHED || Redirect::query()->where('from', $target->slug)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['target_post_id' => 'The canonical target must be a published post without an active redirect.']);
            }
            if ($source->status !== PostStatus::PUBLISHED && ! ($source->status === PostStatus::HIDDEN && $redirect !== null)) {
                throw ValidationException::withMessages(['source_post_id' => 'The source must be a published post or the already consolidated post.']);
            }
            if (Redirect::query()->where('from', $source->slug)->when($redirect, fn ($query) => $query->whereKeyNot($redirect->id))->exists()) {
                throw ValidationException::withMessages(['source_post_id' => 'The source URL already has a redirect.']);
            }
            if ($redirect !== null && $redirect->from !== $source->slug) {
                throw ValidationException::withMessages(['source_post_id' => 'This editorial key belongs to another source URL.']);
            }

            $before = $redirect === null ? null : ['from' => $redirect->from, 'to' => $redirect->to, 'code' => (int) $redirect->code];
            $values = ['editorial_key' => $editorialKey, 'from' => $source->slug,
                'to' => '/'.$target->slug.'/', 'code' => '301', 'is_active' => true];
            $action = $redirect === null ? 'created' : 'updated';
            $redirect = $redirect === null ? Redirect::query()->create($values) : tap($redirect)->update($values);
            $source->update(['status' => PostStatus::HIDDEN]);
            $response = ['id' => $redirect->id, 'editorial_key' => $editorialKey, 'action' => $action,
                'url' => url($redirect->from), 'target_url' => route('posts.show', $target),
                'updated_at' => $redirect->updated_at?->toIso8601String()];

            return [$redirect->id, $action, $before, ['from' => $redirect->from, 'to' => $redirect->to, 'code' => 301], $response];
        });
        Redirect::getAll(true);
        Post::getAllSlugs(true);
        GenerateSitemap::run();

        return response()->json($result);
    }

    public function upsertPost(EditorialPostRequest $request, string $editorialKey): JsonResponse
    {
        $data = $request->validated();
        $result = $this->events->mutate('post', $editorialKey, $data['request_id'], $data, function () use ($data, $editorialKey): array {
            $post = Post::query()->where('editorial_key', $editorialKey)->lockForUpdate()->first();
            if ($post === null && isset($data['target_post_id'])) {
                $post = Post::query()->lockForUpdate()->findOrFail($data['target_post_id']);
                if ($post->editorial_key !== null) {
                    throw ValidationException::withMessages(['target_post_id' => 'This post belongs to another editorial key.']);
                }
            }
            $before = $post ? $this->postSummary($post) : null;
            $category = Category::query()->where('slug', $data['category_slug'])->firstOrFail();
            $status = match ($data['status']) {
                'published' => PostStatus::PUBLISHED,
                'hidden' => PostStatus::HIDDEN,
                default => PostStatus::DRAFT,
            };
            $values = [
                'editorial_key' => $editorialKey, 'title' => $data['title'], 'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'], 'category_id' => $category->id,
                'status' => $status, 'related' => $data['related_ids'] ?? $post?->related ?? [],
            ];
            if (array_key_exists('author_id', $data)) {
                $values['author_id'] = $data['author_id'];
            }
            if ($post === null) {
                $values['slug'] = makeSlug($data['title'], Post::query()->pluck('slug')->all());
                $values['tc_style'] = PostTCStyle::R_SIDEBAR;
                $values['published_at'] = $status === PostStatus::PUBLISHED ? now() : null;
                $values['author_id'] ??= Author::query()->orderBy('id')->value('id');
                $post = Post::query()->create($values);
                $action = 'created';
            } else {
                if ($status === PostStatus::PUBLISHED && $post->published_at === null) {
                    $values['published_at'] = now();
                }
                $post->update($values);
                $action = 'updated';
            }
            $body = $this->sanitizer->sanitize($data['body']);
            if (trim(strip_tags($body)) === '') {
                throw ValidationException::withMessages(['body' => 'Content must contain readable text.']);
            }
            SaveContentBlocks::run($post, ['group_blocks' => [1], 'blocks' => [[
                'ident' => 'content', 'name' => 'Content', 'order' => 1,
                'items' => [['type' => 'text', 'order' => 1, 'value' => ['value' => $body]]],
            ]]]);
            if (array_key_exists('tags', $data)) {
                $post->tags()->detach();
                $this->posts->attachTags($post, $data['tags']);
            }
            if (array_key_exists('source_url', $data)) {
                $post->info()->updateOrCreate([], ['source' => $data['source_url']]);
            }
            $post->refresh();
            $response = ['id' => $post->id, 'editorial_key' => $editorialKey, 'action' => $action,
                'url' => route('posts.show', $post), 'updated_at' => $post->updated_at?->toIso8601String()];

            return [$post->id, $action, $before, $this->postSummary($post, $body), $response];
        });
        Post::getAllSlugs(true);
        GenerateSitemap::run();

        return response()->json($result);
    }

    public function upsertCategory(EditorialCategoryRequest $request, string $editorialKey): JsonResponse
    {
        $data = $request->validated();
        $result = $this->events->mutate('category', $editorialKey, $data['request_id'], $data, function () use ($data, $editorialKey): array {
            $category = Category::query()->where('editorial_key', $editorialKey)->lockForUpdate()->first();
            $before = $category ? $this->categorySummary($category) : null;
            if ($this->reservedSlug($data['slug'])
                || Category::query()->where('slug', $data['slug'])->when($category, fn ($query) => $query->whereKeyNot($category->id))->exists()
                || Page::query()->where('link', $data['slug'])->exists()
                || Post::query()->where('slug', $data['slug'])->exists()) {
                throw ValidationException::withMessages(['slug' => 'This URL path is already in use.']);
            }
            $parentId = $data['parent_id'] ?? $category?->parent_id;
            $ancestor = $parentId ? Category::query()->find($parentId) : null;
            while ($ancestor !== null) {
                if ($category !== null && $ancestor->id === $category->id) {
                    throw ValidationException::withMessages(['parent_id' => 'Category hierarchy cannot contain a cycle.']);
                }
                $ancestor = $ancestor->parent;
            }
            $values = ['editorial_key' => $editorialKey, 'name' => $data['name'], 'slug' => $data['slug'],
                'description' => $this->sanitizer->sanitize($data['description']), 'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'], 'parent_id' => $parentId,
                'in_menu' => $data['in_menu'] ?? $category?->in_menu ?? false, 'order' => $data['order'] ?? $category?->order ?? (Category::query()->max('order') + 1)];
            $action = $category === null ? 'created' : 'updated';
            $category = $category === null ? Category::query()->create($values) : tap($category)->update($values);
            $response = ['id' => $category->id, 'editorial_key' => $editorialKey, 'action' => $action,
                'url' => route('categories.show', $category), 'updated_at' => $category->updated_at?->toIso8601String()];

            return [$category->id, $action, $before, $this->categorySummary($category), $response];
        });
        Category::getAllSlugs(true);
        Cache::forget('headerCategories');
        GenerateSitemap::run();

        return response()->json($result);
    }

    public function upsertPage(EditorialPageRequest $request, string $editorialKey): JsonResponse
    {
        $data = $request->validated();
        $result = $this->events->mutate('page', $editorialKey, $data['request_id'], $data, function () use ($data, $editorialKey): array {
            $page = Page::query()->where('editorial_key', $editorialKey)->lockForUpdate()->first();
            $before = $page ? $this->pageSummary($page) : null;
            if ($this->reservedSlug($data['link'])
                || Page::query()->where('link', $data['link'])->when($page, fn ($query) => $query->whereKeyNot($page->id))->exists()
                || Category::query()->where('slug', $data['link'])->exists()
                || Post::query()->where('slug', $data['link'])->exists()) {
                throw ValidationException::withMessages(['link' => 'This URL path is already in use.']);
            }
            $content = $this->sanitizer->sanitize($data['content']);
            if (trim(strip_tags($content)) === '') {
                throw ValidationException::withMessages(['content' => 'Content must contain readable text.']);
            }
            $values = ['editorial_key' => $editorialKey, 'title' => $data['title'], 'link' => $data['link'],
                'content' => $content, 'meta_title' => $data['meta_title'] ?? $page?->meta_title,
                'meta_description' => $data['meta_description'] ?? $page?->meta_description,
                'status' => $data['status'] === 'published' ? PageStatus::PUBLISHED : PageStatus::DRAFT];
            $action = $page === null ? 'created' : 'updated';
            $page = $page === null ? Page::query()->create($values) : tap($page)->update($values);
            $response = ['id' => $page->id, 'editorial_key' => $editorialKey, 'action' => $action,
                'url' => url($page->link), 'updated_at' => $page->updated_at?->toIso8601String()];

            return [$page->id, $action, $before, $this->pageSummary($page), $response];
        });
        Page::getAllSlugs(true);
        GenerateSitemap::run();

        return response()->json($result);
    }

    private function reservedSlug(string $slug): bool
    {
        return in_array($slug, [
            'api', 'admin', 'posts', 'dev', 'contact', 'contact-us', 'about-us',
            'privacy-policy', 'terms-of-service', 'cookie-policy', 'review-policy',
            'new-front-page-test',
        ], true);
    }

    /** @return array<string, mixed> */
    private function postInventory(Post $post): array
    {
        return [
            'id' => $post->id, 'editorial_key' => $post->editorial_key, 'title' => $post->title,
            'slug' => $post->slug, 'status' => strtolower($post->status->name),
            'meta_title' => $post->meta_title, 'meta_description' => $post->meta_description,
            'category_id' => $post->category_id, 'category_slug' => $post->category?->slug,
            'author_id' => $post->author_id, 'related_ids' => $post->related ?? [],
            'tags' => $post->tags->pluck('name')->all(), 'published_at' => $post->published_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(), 'url' => route('posts.show', $post),
        ];
    }

    /** @return array<string, mixed> */
    private function postSummary(Post $post, ?string $body = null): array
    {
        return ['title' => $post->title, 'status' => $post->status->name, 'category_id' => $post->category_id,
            'related_count' => count($post->related ?? []), 'body_hash' => $body === null ? null : hash('sha256', $body)];
    }

    /** @return array<string, mixed> */
    private function categorySummary(Category $category): array
    {
        return ['name' => $category->name, 'slug' => $category->slug, 'parent_id' => $category->parent_id, 'in_menu' => (bool) $category->in_menu];
    }

    /** @return array<string, mixed> */
    private function pageSummary(Page $page): array
    {
        return ['title' => $page->title, 'link' => $page->link, 'status' => $page->status->name, 'content_hash' => hash('sha256', $page->content ?? '')];
    }
}
