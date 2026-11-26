<?php

namespace Tests\Feature;

use App\Http\Controllers\PageController;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        Category::create([
            'name' => 'News', 'slug' => 'news', 'in_menu' => true, 'order' => 0,
            'description' => '', 'meta_title' => 'News', 'meta_description' => 'News',
        ]);
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/{category}', fn () => '')->name('categories.show');
        Route::get('/{category}/{tagSlug}', fn () => '')->name('categories.show.tag');
        foreach (['about-us', 'contact-us', 'privacy', 'terms', 'cookiePolicy', 'reviewPolicy', 'games.show', 'posts.show', 'authors.show'] as $name) {
            Route::get('/test-'.$name, fn () => '')->name($name);
        }
        Route::getRoutes()->refreshNameLookups();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
