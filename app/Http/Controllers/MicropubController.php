<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\GitHubFactory;
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
use stdClass;
use Symfony\Component\Yaml\Yaml;

class MicropubController extends Controller
{
    protected function getConnection(Request $request)
    {
        return resolve(GitHubFactory::class)->make(
            [
                'method' => 'token',
                'token' => decrypt($request->site->user->token),
            ]
        );
    }

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
                $config = new stdClass();
            }

            return response()->json($config);
        case 'syndicate-to':
            $syndicateTo = config('micropub.config.syndicate-to', []);

            return response()->json(
                [
                    'syndicate-to' => $syndicateTo,
                ]
            );
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
        Log::debug('Create', ['all' => $request->all(), 'headers' => $request->headers->all()]);

        $source = MicropubRequest::create($request->all())->toMf2();

        $now = Carbon::now();

        $nowPath = Str::slug($now->toDateTimeString());
        $nowSlug = $now->format('Y/m/d/His/');

        $path = "src/posts/$nowPath.md";
        $content = $this->content($request, $path, $source);
        $message = 'posted by ' . config('app.name');

        $response = $this->getConnection($request)
            ->repo()
            ->contents()
            ->create(
                $request->site->owner,
                $request->site->repo,
                $path,
                $content,
                $message
            );

        Log::debug('Response', compact('response'));

        $slug = Arr::has($source, 'commands.mp-slug')
            ? Arr::get($source, 'commands.mp-slug')
            : $nowSlug;

        $location = $this->url($request, $slug);

        Log::debug('Slug', compact('slug', 'location'));

        return response()->json(
            null,
            201,
            [
                'Location' => $location,
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

        $response = $this->getConnection($request)
            ->repo()
            ->contents()
            ->update(
                $request->site->owner,
                $request->site->repo,
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

        $response = $this->getConnection($request)
            ->repo()
            ->contents()
            ->rm(
                $request->site->owner,
                $request->site->repo,
                $path,
                $message,
                $sha
            );

        return response()->json(null, 204);
    }

    protected function url(Request $request, string $slug): string
    {
        return rtrim($request->site->url, '/') . '/' . trim($slug, '/') . '/';
    }

    protected function path(Request $request, string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        $path = str_replace('/', '-', $path);

        return "src/posts/$path.md";
    }

    protected function source(Request $request, string $url): array
    {
        $path = $this->path($request, $url);

        $response = $this->getConnection($request)
            ->repo()
            ->contents()
            ->show(
                $request->site->owner,
                $request->site->repo,
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

        $connection = $this->getConnection($request);

        $frontMatter = collect()
            ->merge([
                'id' => Str::uuid()->toString(),
                'date' => $published,
                'source' => $source,
                'view' => 'post',
            ])
            ->when(
                Arr::get($source, 'commands.mp-slug'),
                function ($coll, $slug) {
                    return $coll->put('url', $slug);
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
                    // TODO
                }
            )
            ->when(
                Arr::get($source, 'properties.photo'),
                function ($coll, $photos) use ($connection, $request) {
                    return $coll->put(
                        'photo',
                        collect($photos)
                            ->map(
                                function ($photo) use ($connection, $request) {
                                    if ($photo instanceof UploadedFile) {
                                        Log::debug('Photo', ['class' => get_class($photo), 'photo' => $photo]);

                                        $filename = $photo->hashName();
                                        $path = 'static/photo/' . $filename;
                                        $slug = 'photo/' . $filename;
                                        $content = $photo->get();
                                        $message = 'posted by ' . config('app.name');

                                        Log::debug('Path', compact('path'));

                                        $response = $this->connection()
                                            ->repo()
                                            ->contents()
                                            ->create(
                                                $request->site->owner,
                                                $request->site->repo,
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
