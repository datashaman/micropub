<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\Facades\GitHub;
use GuzzleHttp\Client;
use Html2Text\Html2Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use p3k\Micropub\Request as MicropubRequest;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Yaml\Yaml;

class MicropubController extends Controller
{
    public function query(Request $request): JsonResponse
    {
        $q = $request->get('q');

        switch ($q) {
        case 'source':
            [$source, $sha] = $this->source($request, $request->get('url'));

            if ($request->has('properties')) {
                $properties = Arr::get($source, 'properties', []);

                return response()->json(
                    [
                        'properties' => Arr::only($properties, $request->get('properties')),
                    ]
                );
            }

            return response()->json($source);
        case 'config':
            $config = config('micropub.config', []);
            if (!$config) {
                return response(
                    '{}',
                    200,
                    [
                        'Content-Type' => 'application/json',
                    ]
                );
            }

            return response()->json($config);
        }
    }

    public function post(Request $request): JsonResponse
    {
        switch ($request->get('action')) {
        case 'delete':
            return $this->delete($request);
        case 'update':
            return $this->update($request);
        default:
            return $this->create($request);
        }
    }

    protected function create(Request $request): JsonResponse
    {
        $source = MicropubRequest::create($request->all())->toMf2();

        $now = Carbon::now();

        $nowPath = Str::slug($now->toDateTimeString());
        $nowSlug = $now->format('Y/m/d/_His/');

        $path = "docs/_posts/$nowPath.md";
        $content = $this->content($request, $path, $source);
        $message = 'posted by ' . config('app.name');

        $response = GitHub::repo()->contents()->create(
            config('micropub.github.owner'),
            config('micropub.github.repo'),
            $path,
            $content,
            $message
        );

        $slug = Arr::has($source, 'commands.mp-slug')
            ? Arr::get($source, 'commands.mp-slug')
            : $nowSlug;

        return response()->json(
            null,
            201,
            [
                'Location' => $this->url($request, $slug),
            ]
        );
    }

    protected function update(Request $request): JsonResponse
    {
        $update = MicropubRequest::create($request->all())->toMf2();

        $url = $request->get('url');

        [$source, $sha] = $this->source($request, $url);

        $properties = collect($source['properties']);

        if ($request->has('add')) {
            $add = $request->get('add');

            if (!is_array($add)) {
                return response()->json(
                    null,
                    400,
                    [
                        'error' => 'bad_request',
                    ]
                );
            }

            collect($add)
                ->each(
                    function ($value, $key) use ($properties) {
                        $properties->put($key, array_merge(
                            $properties->get($key, []),
                            $value
                        ));
                    }
                );
        }

        if ($request->has('delete')) {
            $delete = $request->get('delete');

            if (!is_array($delete)) {
                return response()->json(
                    null,
                    400,
                    [
                        'error' => 'bad_request',
                    ]
                );
            }

            collect($delete)
                ->each(
                    function ($value, $key) use ($properties) {
                        if (is_numeric($key) && is_string($value)) {
                            $properties->forget($value);
                        } else {
                            $original = $properties->get($key, []);
                            $value = array_diff($original, $value);

                            if ($value) {
                                $properties->put($key, $value);
                            } else {
                                $properties->forget($key);
                            }
                        }
                    }
                );
        }

        if ($request->has('replace')) {
            $replace = $request->get('replace');

            if (!is_array($replace)) {
                return response()->json(
                    null,
                    400,
                    [
                        'error' => 'bad_request',
                    ]
                );
            }

            $properties = $properties->merge($replace);
        }

        Log::debug('Property update', ['original' => $source['properties'], 'value' => $properties->all()]);

        $source['properties'] = $properties->all();

        $path = $this->path($request, $url);
        $content = $this->content($request, $path, $source);
        $message = 'posted by ' . config('app.name');

        $response = GitHub::repo()->contents()->update(
            config('micropub.github.owner'),
            config('micropub.github.repo'),
            $path,
            $content,
            $message,
            $sha
        );

        Log::debug('GitHub update', compact('path', 'content', 'response'));

        $path = parse_url($url, PHP_URL_PATH);
        $slug = preg_replace('#^/#', '', $path);

        return response()->json(
            null,
            204,
            [
                'Location' => $this->url($request, $slug),
            ]
        );
    }

    protected function delete(Request $request)
    {
        $url = $request->get('url');
        $path = $this->path($request, $url);
        $message = 'posted by ' . config('app.name');
        [$source, $sha] = $this->source($request, $url);

        $response = GitHub::repo()->contents()->rm(
            config('micropub.github.owner'),
            config('micropub.github.repo'),
            $path,
            $message,
            $sha
        );

        return response()->json(null, 204);
    }

    protected function url(Request $request, string $slug): string
    {
        return preg_replace('#/$#', '', $request->session()->get('user.me')) . '/' . $slug;
    }

    protected function path(Request $request, string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = preg_replace(['#^/#', '#/$#'], '', $path);
        $path = str_replace('/', '-', $path);
        $path = str_replace('_', '', $path);

        return "docs/_posts/$path.md";
    }

    protected function source(Request $request, string $url): array
    {
        $path = $this->path($request, $url);

        $response = GitHub::repo()->contents()->show(
            config('micropub.github.owner'),
            config('micropub.github.repo'),
            $path
        );

        $content = base64_decode($response['content']);
        $object = YamlFrontMatter::parse($content);

        return [$object->matter('source'), $response['sha']];
    }

    protected function content(
        Request $request,
        string $path,
        array $source
    ): string {
        $contentType = is_string(Arr::get($source, 'properties.content.0'))
            ? 'text'
            : 'html';

        $published = Arr::has($source, 'properties.published.0')
            ? Carbon::parse(Arr::get($source, 'properties.published.0'))
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

        return view(
            $view,
            [
                'contentType' => $contentType,
                'frontMatter' => trim(Yaml::dump($frontMatter, 10)),
                'post' => $source,
                'published' => $published->toIso8601String(),
            ]
        )->render();
    }
}
