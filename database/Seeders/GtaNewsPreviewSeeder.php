<?php

namespace Database\Seeders;

use App\Actions\GetTopTagsAction;
use App\Enums\BlockItemType;
use App\Enums\PostStatus;
use App\Enums\PostTCStyle;
use App\Models\Attachment;
use App\Models\Author;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GtaNewsPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing') || Post::withTrashed()->exists()) {
            $this->command?->warn('Preview content requires a local or testing environment with no existing posts. Nothing changed.');

            return;
        }

        DB::transaction(function () {
            $category = Category::where('slug', 'news')->firstOrFail();
            $category->update(['name' => 'GTA VI News', 'meta_title' => 'GTA VI News', 'meta_description' => 'Independent dispatches from Vice City and the world of Grand Theft Auto VI.']);
            $author = Author::firstOrCreate(['slug' => 'gta-vi-news-desk'], [
                'name' => 'GTA VI News Desk', 'title' => 'Independent GTA VI coverage',
                'description' => 'The independent news desk covering the world of GTA VI.',
                'description_small' => 'Stories from Vice City and beyond.',
                'meta_title' => 'GTA VI News Desk', 'meta_description' => 'Independent Grand Theft Auto VI coverage.',
            ]);
            $stories = [
                ['Vice City is calling. A new chapter begins.', 'Jason, Lucia, and a whole state of possibilities. Get acquainted with the next chapter of Grand Theft Auto.', 'The story', 'poster', 'Rockstar introduces Jason and Lucia as the central pair in a story that stretches across Leonida. What begins as an easy score puts the two inside a much larger criminal conspiracy.', 'The official GTA VI site is the starting point for character introductions, videos, screenshots, and announcements. Our coverage follows those primary sources and keeps speculation clearly separate from confirmed information.'],
                ['Jason & Lucia: two lives, one way out.', 'Meet the pair at the heart of Rockstar’s next Grand Theft Auto story.', 'Characters', 'trailer2', 'Jason and Lucia anchor the story of GTA VI. Rockstar’s official introduction places their relationship at the center of the journey through Leonida.', 'The character material gives us a first sense of the people behind the action. For their full official biographies and the latest character artwork, visit Rockstar’s GTA VI website.'],
                ['Beyond the beach: welcome to Leonida.', 'There is more to this world than the bright lights of Vice City.', 'The world', 'leonida', 'Vice City is the recognizable heart of the setting, but GTA VI’s story reaches across the state of Leonida. The official people-and-places material invites a closer look at that wider world.', 'Use the official website to explore the locations and the people who call them home. Screenshots offer a useful starting point for understanding the atmosphere without assuming what the final game will contain.'],
                ['Every frame tells a story: revisiting Trailer 2.', 'A closer look at the people, places, and atmosphere of the official second trailer.', 'Trailers', 'featured', 'Trailer 2 brings Jason and Lucia into sharper focus and offers another window onto Leonida. The official video is available through Rockstar’s GTA VI site.', 'Watching the source footage is the best way to assess the details yourself. Our trailer coverage starts with what appears on screen, while treating unconfirmed interpretations as speculation.'],
                ['The first look that brought us back to Vice City.', 'Revisit the official reveal and its introduction to the world of GTA VI.', 'Trailers', 'trailer1', 'The first GTA VI trailer introduced the new setting through a rapid sequence of city life, coastlines, roads, and characters. It remains available alongside the newer official footage.', 'The original reveal is useful context for understanding the visual identity of Leonida. For current release information, consult Rockstar’s live website rather than relying on the release window in older footage.'],
                ['From streets to swamps: the world of GTA VI.', 'A visual dispatch from the darkest side of the sunniest place in America.', 'The world', 'leonida', 'The official promotional material presents a world of contrasting spaces: city streets, waterfronts, and the landscapes beyond. Leonida is the common thread.', 'Rockstar’s people-and-places section is the primary reference for named locations and their descriptions. We will return to that source as new official material arrives.'],
                ['Your next stop: the official GTA VI hub.', 'Keep Rockstar’s primary sources close as the next chapter approaches.', 'Official updates', 'poster', 'Rockstar’s GTA VI site brings together official videos, promotional artwork, and links to announcements. It is a useful reference when a headline needs checking.', 'Follow the official Newswire for dated announcements, then use the game website for the broader overview. This independent publication adds context while linking back to those sources.'],
            ];
            foreach ($stories as $index => [$title, $description, $topic, $image, $paragraph1, $paragraph2]) {
                $post = Post::create([
                    'category_id' => $category->id, 'author_id' => $author->id,
                    'title' => $title, 'sub_title' => $description, 'slug' => Str::slug($title),
                    'meta_title' => $title.' | GTA VI News', 'meta_description' => $description,
                    'status' => PostStatus::PUBLISHED, 'tc_style' => PostTCStyle::R_SIDEBAR,
                    'block_groups' => [1], 'published_at' => now()->startOfDay()->subDays($index),
                    'external_data' => ['preview' => true, 'source' => 'https://www.rockstargames.com/VI'],
                ]);
                $tag = Tag::firstOrCreate(['slug' => Str::slug($topic)], ['name' => $topic]);
                $post->tags()->sync([$tag->id]);
                $block = $post->blocks()->create(['name' => 'Story', 'ident' => 'story', 'order' => 0]);
                $block->items()->create(['type' => BlockItemType::TEXT, 'order' => 0, 'value' => json_encode(['value' => '<p>'.e($paragraph1).'</p><p>'.e($paragraph2).'</p>'])]);
                $name = 'gta-vi-preview/'.$image.'.webp';
                Storage::disk('aimages')->put($name, file_get_contents(public_path('images/gta-vi/'.$image.'.webp')));
                $attachment = Attachment::firstOrCreate(['name' => $name], [
                    'type' => 'image', 'original_name' => $image.'.webp',
                    'alt' => 'Official GTA VI promotional artwork — Rockstar Games',
                    'title' => $title, 'size' => filesize(public_path('images/gta-vi/'.$image.'.webp')),
                    'source' => 'https://www.rockstargames.com/VI',
                ]);
                $post->addAttachment(['id' => $attachment->id], 'thumbnail');
            }
        });
        Cache::forget('headerCategories');
        GetTopTagsAction::run(true);
        Author::getAllSlugs(true);
        Post::getAllSlugs(true);
    }
}
