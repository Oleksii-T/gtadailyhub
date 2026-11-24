<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Game;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Request $request, Category $category)
    {
        $category->load(['parent', 'children' => fn ($query) => $query->orderBy('order')->orderBy('name')]);
        $tagSlug = $request->route('tagSlug');
        $tag = $tagSlug ? Tag::where('slug', $tagSlug)->first() : null;
        $page = $request->route('page') ?? 1;
        $perPage = 10;
        $page = abs((int) filter_var($page, FILTER_SANITIZE_NUMBER_INT));
        $posts = Post::query()
            ->whereIn('category_id', $this->categoryAndDescendantIds($category))
            ->with(['category', 'tags'])
            ->publised()
            ->latest('published_at')
            ->when($request->search, fn ($q, $v) => $q->where('title', 'like', "%$v%"))
            ->when($tagSlug, fn ($q, $v) => $q->whereRelation('tags', 'slug', $v))
            ->when($request->game, fn ($q) => $q->where('game_id', Game::where('slug', request()->game)->value('id')))
            ->paginate($perPage, ['*'], 'page', $page);
        $hasMore = $posts->hasMorePages();
        $game = null;
        if ($request->game) {
            $game = Game::where('slug', $request->game)->first();
        }

        if (! $request->ajax()) {
            $currentPage = $page;
            $page = Page::get('{category}');

            return view('categories.show', compact('category', 'posts', 'game', 'page', 'hasMore', 'currentPage', 'tagSlug', 'tag'));
        }

        return $this->jsonSuccess('', [
            'hasMore' => $hasMore,
            'html' => view('components.post-cards-with-pages', [
                'posts' => $posts,
                'model' => $category,
                'includeQueryParams' => ['game', 'search'],
            ])->render(),
        ]);
    }

    /** @return array<int, int> */
    private function categoryAndDescendantIds(Category $category): array
    {
        $childrenByParent = Category::query()->select(['id', 'parent_id'])->get()->groupBy('parent_id');
        $pending = [$category->id];
        $ids = [];
        while ($pending !== []) {
            $id = array_pop($pending);
            if (in_array($id, $ids, true)) {
                continue;
            }
            $ids[] = $id;
            foreach ($childrenByParent->get($id, collect()) as $child) {
                $pending[] = $child->id;
            }
        }

        return $ids;
    }
}
