<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\Facades\GitHub;
use GuzzleHttp\Client;
use Html2Text\Html2Text;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use p3k\Micropub\Request as MicropubRequest;
use Symfony\Component\Yaml\Yaml;

class MicropubController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function query(Request $request)
    {
        if ($request->get('q') === 'source') {
            $path = $this->path($request, $request->get('url'));

            Log::debug('Found path', ['path' => $path]);
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $mpRequest = MicropubRequest::create($request->all());

        $source = $mpRequest->toMf2();

        $contentType = is_string(Arr::get($source, 'properties.content.0'))
            ? 'text'
            : 'html';

        $published = Arr::has($source, 'properties.published')
            ? Carbon::parse(Arr::get($source, 'properties.published'))
            : Carbon::now();

        $contentType = is_string(Arr::get($source, 'properties.content.0'))
            ? 'text'
            : 'html';

        $frontMatter = collect()
            ->merge([
                'date' => $published,
                'source' => $source,
                'type' => 'post',
            ])
            ->when(
                Arr::get($source, 'commands.mp-slug'),
                function ($coll, $slug) {
                    return $coll->put('slug', $slug);
                }
            )
            ->when(
                Arr::get($source, 'properties.category'),
                function ($coll, $tags) {
                    return $coll->put('tags', $tags);
                }
            )
            ->when(
                Arr::get($source, 'files'),
                function ($coll, $files) {

                }
            )
            ->when(
                Arr::get($source, 'properties.photo'),
                function ($coll, $photos) use ($request) {
                    return $coll->put(
                        'photo',
                        collect($photos)
                            ->map(
                                function ($photo) use ($request) {
                                    if ($photo instanceof UploadedFile) {
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

                                        $photo = $this->url($request, $slug);
                                    }

                                    return is_string($photo) ? ['value' => $photo] : $photo;
                                }
                            )
                            ->all()
                    );
                }
            )
            ->all();

        $view = 'types.' . Arr::get($source, 'type.0');
        $content = view(
            $view,
            [
                'contentType' => $contentType,
                'frontMatter' => trim(Yaml::dump($frontMatter, 10)),
                'post' => $source,
                'published' => $published->toIso8601String(),
            ]
        )->render();

        $now = Carbon::now();
        $nowPath = Str::slug($now->toDateTimeString());
        $nowSlug = $now->format('Y/m/d/_His/');

        $path = "docs/_posts/$nowPath.md";

        $slug = Arr::has($source, 'commands.mp-slug')
            ? Arr::get($source, 'commands.mp-slug')
            : $nowSlug;

        $message = 'posted by ' . config('app.name');

        $response = GitHub::repo()->contents()->create(
            config('micropub.github.owner'),
            config('micropub.github.repo'),
            $path,
            $content,
            $message
        );

        Log::debug(
            'Micropub response',
            compact('content', 'path', 'slug', 'response')
        );

        return response()->json(
            null,
            201,
            [
                'Location' => $this->url($request, $slug),
            ]
        );
    }

    protected function url(Request $request, string $slug)
    {
        return preg_replace('#/$#', '', $request->session()->get('user.me')) . '/' . $slug;
    }

    protected function path(Request $request, string $url)
    {
        $host = preg_replace('#/$#', '', $request->session()->get('user.me')) . '/';

        return str_replace(
            '/',
            '-',
            preg_replace('#/$#', '', str_replace($host, '', $url)) . '.md'
        );
    }
}
