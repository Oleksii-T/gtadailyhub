<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\EditorialApiEvent;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Route::get('/{category}', fn () => '')->name('categories.show');
        \Illuminate\Support\Facades\Route::get('/{post:slug}', fn () => '')->name('posts.show');
        \Illuminate\Support\Facades\Route::get('/author/{author}', fn () => '')->name('authors.show');
        \Illuminate\Support\Facades\Route::get('/game/{game}', fn () => '')->name('games.show');
        foreach (['index', 'about-us', 'contact-us', 'privacy', 'terms', 'cookiePolicy', 'reviewPolicy'] as $name) {
            \Illuminate\Support\Facades\Route::get('/test-'.$name, fn () => '')->name($name);
        }
        \Illuminate\Support\Facades\Route::get('/test-news/{tagSlug}', fn () => '')->name('categories.show.tag');
        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();
    }

    private function headers(): array
    {
        config()->set('auth.api_bearer', 'test-token');

        return ['Authorization' => 'Bearer test-token'];
    }

    public function test_editorial_api_requires_a_real_configured_bearer_token(): void
    {
        config()->set('auth.api_bearer', '');
        $this->getJson('/api/editorial/posts')->assertForbidden();
        config()->set('auth.api_bearer', 'test-token');
        $this->getJson('/api/editorial/posts')->assertForbidden();
    }

    public function test_category_and_page_upserts_are_idempotent_and_audited(): void
    {
        $headers = $this->headers();
        $category = [
            'request_id' => 'category-1', 'name' => 'GTA Guides', 'slug' => 'gta-guides',
            'description' => '<p>Useful guides</p><script>alert(1)</script>',
            'meta_title' => 'GTA Guides', 'meta_description' => 'GTA guides',
            'in_menu' => true,
        ];
        $response = $this->putJson('/api/editorial/categories/by-key/guides', $category, $headers)->assertOk()->assertJsonPath('action', 'created');
        $this->putJson('/api/editorial/categories/by-key/guides', $category, $headers)->assertOk()->assertJsonPath('replayed', true);
        $this->assertEquals(1, Category::query()->where('editorial_key', 'guides')->count());
        $this->assertStringNotContainsString('script', Category::query()->firstOrFail()->description);
        $this->putJson('/api/editorial/categories/by-key/guides', [...$category, 'name' => 'Changed'], $headers)->assertStatus(409);
        $this->getJson('/api/editorial/categories', $headers)->assertOk()->assertJsonFragment(['editorial_key' => 'guides']);
        $this->putJson('/api/editorial/categories/by-key/subguides', [...$category, 'request_id' => 'category-2', 'name' => 'Subguides', 'slug' => 'gta-subguides', 'parent_id' => $response->json('id')], $headers)->assertOk();
        $this->putJson('/api/editorial/categories/by-key/guides', [...$category, 'request_id' => 'category-3', 'parent_id' => Category::query()->where('editorial_key', 'subguides')->value('id')], $headers)->assertUnprocessable();
        $this->putJson('/api/editorial/categories/by-key/guides', [...$category, 'request_id' => 'category-4', 'name' => 'All GTA Guides'], $headers)->assertOk()->assertJsonPath('action', 'updated');

        $page = [
            'request_id' => 'page-1', 'title' => 'GTA Topic Hub', 'link' => 'gta-topic-hub',
            'content' => '<h2>Topic</h2><p><a href="javascript:alert(1)">bad</a><a href="https://example.com">good</a></p><img src="x" onerror="alert(1)">',
            'meta_title' => 'GTA Topic Hub', 'meta_description' => 'Topic links', 'status' => 'published',
        ];
        $firstPage = $this->putJson('/api/editorial/pages/by-key/topic-hub', $page, $headers)->assertOk()->assertJsonPath('action', 'created')->assertJsonPath('revision', 1);
        $this->assertNotNull($firstPage->json('event_id'));
        $this->putJson('/api/editorial/pages/by-key/topic-hub', $page, $headers)->assertOk()->assertJsonPath('replayed', true)->assertJsonPath('revision', 1)->assertJsonPath('event_id', $firstPage->json('event_id'));
        $saved = Page::query()->where('editorial_key', 'topic-hub')->firstOrFail();
        $this->assertStringNotContainsString('javascript:', $saved->content);
        $this->assertStringNotContainsString('onerror', $saved->content);
        $this->assertStringContainsString('https://example.com', $saved->content);
        $this->getJson('/api/editorial/pages', $headers)->assertOk()->assertJsonFragment(['editorial_key' => 'topic-hub']);
        $this->putJson('/api/editorial/pages/by-key/topic-hub', [...$page, 'request_id' => 'page-2', 'title' => 'Updated GTA Topic Hub'], $headers)->assertOk()->assertJsonPath('action', 'updated')->assertJsonPath('revision', 2);
        $this->assertEquals(5, EditorialApiEvent::query()->count());
        $this->assertNull(EditorialApiEvent::query()->where('entity_type', 'page')->firstOrFail()->before_summary);
        $this->assertStringNotContainsString('Topic</h2>', json_encode(EditorialApiEvent::query()->where('entity_type', 'page')->firstOrFail()->after_summary));
        $this->assertNotEmpty($response->json('url'));
    }

    public function test_category_page_links_to_parent_and_children(): void
    {
        $parent = Category::query()->create(['name' => 'GTA Guides', 'description' => 'Guide collection', 'slug' => 'gta-guides', 'meta_title' => 'GTA Guides', 'meta_description' => 'Guides', 'order' => 1, 'in_menu' => true]);
        $child = Category::query()->create(['name' => 'Vehicles', 'description' => 'Vehicle guides', 'slug' => 'vehicles', 'meta_title' => 'Vehicles', 'meta_description' => 'Vehicles', 'parent_id' => $parent->id, 'order' => 2]);
        Post::query()->create(['title' => 'Child GTA story', 'slug' => 'child-gta-story', 'meta_title' => 'Child GTA story', 'meta_description' => 'Story', 'category_id' => $child->id, 'status' => \App\Enums\PostStatus::PUBLISHED, 'tc_style' => \App\Enums\PostTCStyle::R_SIDEBAR, 'published_at' => now()]);
        \Illuminate\Support\Facades\Route::get('/preview-category/{category}', fn (string $slug) => app(\App\Http\Controllers\CategoryController::class)->show(request(), Category::query()->where('slug', $slug)->firstOrFail()));
        $startingBufferLevel = ob_get_level();

        try {
            $this->get('/preview-category/gta-guides')->assertOk()->assertSee('Category navigation')->assertSee('Vehicles')->assertSee('Child GTA story');
            $this->get('/preview-category/vehicles')->assertOk()->assertSee('Category navigation')->assertSee('GTA Guides');
            $this->assertEquals(['gta-guides'], Category::forHeader()->pluck('slug')->all());
        } finally {
            while (ob_get_level() > $startingBufferLevel) {
                ob_end_clean();
            }
        }
    }

    public function test_existing_post_can_be_claimed_once_for_revision(): void
    {
        $headers = $this->headers();
        $category = Category::query()->create(['name' => 'News', 'description' => 'News', 'slug' => 'news', 'meta_title' => 'News', 'meta_description' => 'News', 'order' => 1]);
        $post = Post::query()->create(['title' => 'Existing post', 'slug' => 'existing-post', 'meta_title' => 'Existing post', 'meta_description' => 'Existing', 'category_id' => $category->id, 'status' => \App\Enums\PostStatus::DRAFT, 'tc_style' => \App\Enums\PostTCStyle::R_SIDEBAR]);
        $payload = ['request_id' => 'claim-1', 'target_post_id' => $post->id, 'title' => 'Revised post', 'meta_title' => 'Revised post', 'meta_description' => 'Revised', 'body' => '<p>Revised facts.</p>', 'category_slug' => 'news', 'status' => 'draft'];
        $this->putJson('/api/editorial/posts/by-key/claimed-post', $payload, $headers)->assertOk()->assertJsonPath('id', $post->id)->assertJsonPath('action', 'updated');
        $this->assertEquals('claimed-post', $post->fresh()->editorial_key);
        $this->putJson('/api/editorial/posts/by-key/another-key', [...$payload, 'request_id' => 'claim-2'], $headers)->assertUnprocessable();
        $this->assertEquals(1, Post::query()->count());
    }

    public function test_post_can_be_created_revised_and_found_in_inventory(): void
    {
        $headers = $this->headers();
        Category::query()->create(['name' => 'News', 'description' => 'News', 'slug' => 'news', 'meta_title' => 'News', 'meta_description' => 'News', 'order' => 1]);
        Category::query()->create(['name' => 'Guides', 'description' => 'Guides', 'slug' => 'guides', 'meta_title' => 'Guides', 'meta_description' => 'Guides', 'order' => 2]);
        $payload = [
            'request_id' => 'post-1', 'title' => 'GTA update explained', 'meta_title' => 'GTA update explained',
            'meta_description' => 'What changed', 'body' => '<p>Original facts.</p>',
            'category_slug' => 'news', 'status' => 'draft', 'related_ids' => [],
        ];
        $created = $this->putJson('/api/editorial/posts/by-key/update-explained', $payload, $headers)->assertOk()->assertJsonPath('action', 'created');
        $id = $created->json('id');
        $this->assertNotEmpty($created->json('url'));
        $this->putJson('/api/editorial/posts/by-key/update-explained', $payload, $headers)->assertOk()->assertJsonPath('replayed', true);
        $this->assertEquals(1, Post::query()->count());
        $revision = [...$payload, 'request_id' => 'post-2', 'body' => '<p>Updated facts.</p>', 'category_slug' => 'guides', 'status' => 'published', 'related_ids' => [$id]];
        $this->putJson('/api/editorial/posts/by-key/update-explained', $revision, $headers)->assertOk()->assertJsonPath('action', 'updated')->assertJsonPath('revision', 2);
        $post = Post::query()->findOrFail($id);
        $this->assertEquals('guides', $post->category->slug);
        $this->assertEquals([$id], $post->related);
        $this->assertNotNull($post->published_at);
        $this->getJson('/api/editorial/posts', $headers)->assertOk()->assertJsonFragment(['editorial_key' => 'update-explained']);
        $this->getJson('/api/editorial/posts/'.$id, $headers)->assertOk()->assertSee('Updated facts.');
        $this->getJson('/api/editorial/events', $headers)->assertOk()->assertJsonFragment(['request_id' => 'post-2']);
        $canonicalRevision = $revision;
        ksort($canonicalRevision);
        $this->getJson('/api/editorial/events/by-request/post-2', $headers)
            ->assertOk()->assertJsonPath('entity_type', 'post')->assertJsonPath('editorial_key', 'update-explained')
            ->assertJsonPath('receipt.revision', 2)
            ->assertJsonPath('receipt.payload_fingerprint', hash('sha256', json_encode($canonicalRevision, JSON_THROW_ON_ERROR)));
        $this->getJson('/api/editorial/events/by-request/unknown', $headers)->assertOk()->assertJsonPath('found', false);
        $this->getJson('/api/editorial/events/by-request/post-2')->assertForbidden();
    }

    public function test_editorial_redirect_consolidates_published_posts_and_replays_safely(): void
    {
        $headers = $this->headers();
        $category = Category::query()->create([
            'name' => 'News', 'description' => 'News', 'slug' => 'news',
            'meta_title' => 'News', 'meta_description' => 'News', 'order' => 1,
        ]);
        $makePost = fn (string $slug): Post => Post::query()->create([
            'title' => $slug, 'slug' => $slug, 'meta_title' => $slug,
            'meta_description' => $slug, 'category_id' => $category->id,
            'status' => \App\Enums\PostStatus::PUBLISHED,
            'tc_style' => \App\Enums\PostTCStyle::R_SIDEBAR, 'published_at' => now(),
        ]);
        $source = $makePost('duplicate-gta-story');
        $target = $makePost('canonical-gta-story');
        $otherTarget = $makePost('other-gta-story');
        $payload = ['request_id' => 'redirect-1', 'source_post_id' => $source->id, 'target_post_id' => $target->id];

        $first = $this->putJson('/api/editorial/redirects/by-key/story-consolidation', $payload, $headers)
            ->assertOk()->assertJsonPath('action', 'created')->assertJsonPath('revision', 1);
        $this->assertSame(1, Redirect::query()->count());
        $this->assertSame('duplicate-gta-story', Redirect::query()->firstOrFail()->from);
        $this->assertSame('/canonical-gta-story/', Redirect::query()->firstOrFail()->to);
        $this->assertSame(\App\Enums\PostStatus::HIDDEN, $source->fresh()->status);
        $this->getJson('/api/editorial/redirects', $headers)->assertOk()->assertJsonFragment(['editorial_key' => 'story-consolidation']);
        file_put_contents(public_path('sitemap.xml'), '<urlset><loc>stale-duplicate-gta-story</loc></urlset>');
        $this->putJson('/api/editorial/redirects/by-key/story-consolidation', $payload, $headers)
            ->assertOk()->assertJsonPath('replayed', true)->assertJsonPath('event_id', $first->json('event_id'));
        $this->assertStringNotContainsString('stale-duplicate-gta-story', file_get_contents(public_path('sitemap.xml')));
        $this->putJson('/api/editorial/redirects/by-key/story-consolidation', [
            ...$payload, 'target_post_id' => $otherTarget->id,
        ], $headers)->assertStatus(409);
        $this->putJson('/api/editorial/redirects/by-key/another-key', [
            ...$payload, 'request_id' => 'redirect-2', 'target_post_id' => $otherTarget->id,
        ], $headers)->assertUnprocessable();
        $this->putJson('/api/editorial/redirects/by-key/redirect-chain', [
            'request_id' => 'redirect-3', 'source_post_id' => $otherTarget->id,
            'target_post_id' => $source->id,
        ], $headers)->assertUnprocessable();
        $this->getJson('/api/editorial/redirects')->assertForbidden();

        \Illuminate\Support\Facades\Route::setRoutes(new \Illuminate\Routing\RouteCollection);
        require base_path('routes/web.php');
        $this->get('/duplicate-gta-story/')->assertStatus(301)->assertRedirect('/canonical-gta-story/');
        $this->get('/duplicate-gta-story/?ref=old')->assertStatus(301)->assertRedirect('/canonical-gta-story/?ref=old');
        $this->get('/duplicate-gta-story/')->assertStatus(301)->assertRedirect('/canonical-gta-story/');
        $this->assertStringNotContainsString('duplicate-gta-story', file_get_contents(public_path('sitemap.xml')));
    }
}
