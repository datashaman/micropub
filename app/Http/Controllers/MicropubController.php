<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\Facades\GitHub;
use GuzzleHttp\Client;
use Html2Text\Html2Text;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use p3k\Micropub\Request as MicropubRequest;
use Symfony\Component\Yaml\Yaml;

class MicropubController extends Controller
{
    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $mpRequest = MicropubRequest::create($request->all());

        $mf2 = $mpRequest->toMf2();
        $commands = $mpRequest->commands;
        Log::debug('Mf2', compact('mf2', 'commands'));

        $contentType = is_string(Arr::get($mf2, 'properties.content.0'))
            ? 'text'
            : 'html';

        $published = Arr::has($mf2, 'properties.published')
            ? Carbon::parse(Arr::get($mf2, 'properties.published'))
            : Carbon::now();

        $contentType = is_string(Arr::get($mf2, 'properties.content.0'))
            ? 'text'
            : 'html';

        $frontMatter = collect()
            ->merge([
                'date' => $published,
                'type' => 'post',
            ])
            ->when(
                Arr::get($mf2, 'commands.mp-slug'),
                function ($coll, $slug) {
                    return $coll->put('slug', $slug);
                }
            )
            ->when(
                Arr::get($mf2, 'properties.category'),
                function ($coll, $tags) {
                    return $coll->put('tags', $tags);
                }
            )
            ->when(
                Arr::get($mf2, 'files'),
                function ($coll, $files) {

                }
            )
            ->when(
                Arr::get($mf2, 'properties.photo'),
                function ($coll, $photos) use ($request) {
                    return $coll->put(
                        'photo',
                        collect($photos)
                            ->map(
                                function ($photo) use ($request) {
                                    Log::debug('Photo', ['class' => get_class($photo), 'photo' => $photo]);

                                    $filename = $photo->hashName();
                                    $path = 'docs/.vuepress/public/photo/' . $filename;
                                    $slug = 'photo/' . $filename;
                                    $content = $photo->get();
                                    $message = 'posted by ' . config('app.name');

                                    Log::debug('Path', compact('path'));

                                    $response = GitHub::repo()->contents()->create(
                                        config('micropub.github.owner'),
                                        config('micropub.github.repo'),
                                        $path,
                                        $content,
                                        $message
                                    );

                                    Log::debug('GitHub response', compact('response'));

                                    $photo = $request->session()->get('user.me') . '/' . $slug;

                                    return is_string($photo) ? ['value' => $photo] : $photo;
                                }
                            )
                            ->all()
                    );
                }
            )
            ->all();

        $view = 'types.' . Arr::get($mf2, 'type.0');
        $content = view(
            $view,
            [
                'contentType' => $contentType,
                'frontMatter' => trim(Yaml::dump($frontMatter)),
                'post' => $mf2,
                'published' => $published->toIso8601String(),
            ]
        )->render();

        $title = $contentType === 'text'
            ? substr(Arr::get($mf2, 'properties.content.0'), 0, 200)
            : (new Html2Text(Arr::get($mf2, 'properties.content.0.html')))->getText();

        $path = $published->format('Y-m-d') . '-' . Str::slug(strtolower($title));

        $slug = Arr::has($mf2, 'commands.mp-slug')
            ? Arr::get($mf2, 'commands.mp-slug')
            : $published->format('Y/m/d') . '/' . Str::slug(strtolower($title));

        Log::debug(
            'Micropub output',
            compact('frontMatter', 'content', 'title', 'path', 'slug')
        );
    }
}
