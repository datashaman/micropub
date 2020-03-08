<?php

namespace App\Http\Controllers;

use Embed\Embed;
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
use IndieWeb\jf2stream\Jf2StreamCleaner;
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
        $all = $request->except(['site']);
        $mpRequest = MicropubRequest::create($all);
        $source = $mpRequest->toMf2();

        Log::info(
            'Micropub Create Entry', [
                'all' => $all,
                'action' => $mpRequest->action,
                'commands' => $mpRequest->commands,
                'properties' => $mpRequest->properties,
                'update' => $mpRequest->update,
                'url' => $mpRequest->url,
            ]
        );

        $published = Carbon::now();

        $nowPath = Str::slug($published->toDateTimeString());
        $nowSlug = $published->format('Y/m/d/His/');

        $path = "src/posts/$nowPath.md";
        $content = $this->content($request, $source, $published);
        $message = 'posted by ' . config('app.name');

        $response = $this->getConnection($request)
            ->repo()
            ->contents()
            ->create(
                $request->site->owner,
                $request->site->repo,
                $path,
                $content,
                $message,
                $request->site->branch
            );

        $slug = $request->get('commands.mp-slug', $request->get('mp-slug', $nowSlug));
        $location = $this->url($request, $slug);

        Log::info('Post created', compact('location'));

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
        $content = $this->content($request, $source);
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
                $sha,
                $request->site->branch
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
                $sha,
                $request->site->branch
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
                $path,
                $request->site->branch
            );

        $content = base64_decode($response['content']);
        $object = YamlFrontMatter::parse($content);

        return [$object->matter('source'), $response['sha']];
    }

    protected function content(
        Request $request,
        array $source,
        Carbon $published = null
    ): string {
        $data = $this->toJf2($request, $source);

        if (!Arr::has($data, 'uid')) {
            $data['uid'] = (string) Str::orderedUuid();
        }

        if ($published) {
            $data['published'] = $published->toIso8601String();
        }

        $references = [
            'bookmark-of',
            'in-reply-to',
            'like-of',
            'repost-of',
        ];

        foreach ($references as $reference) {
            $url = Arr::get($data, $reference);

            if (is_string($url)) {
                if (!Arr::has($data, 'references')) {
                    $data['references'] = [];
                }

                $data['references'][$url] = $this->getReference($url);
            }
        }

        Log::debug(
            'Content',
            [
                'source' => $source,
                'data' => $data,
            ]
        );

        $view = 'types.' . Arr::get($data, 'type', 'entry');

        return view($view, ['data' => $data])->render();
    }

    protected function toJf2(
        Request $request,
        array $mf2
    ): array {
        $cleaner = new Jf2StreamCleaner();

        return $cleaner->clean($mf2, $request->site->url, $request->site->lang ?? 'en');
    }

    protected function getReference($url): array
    {
        $info = Embed::create($url);

        Log::debug('Core', ['code' => $info->code]);

        $reference = [
            'type' => 'entry',
            'url' => $info->url,
            'name' => $info->title,
            'content' => [
                'text' => preg_replace('/(^“|”$)/', '', $info->description),
                'html' => $info->code,
            ],
            'photo' => $info->image,
        ];

        $author = [];

        if ($info->authorName && $info->authorUrl) {
            $reference['author'] = [
                'name' => $info->authorName,
                'url' => $info->authorUrl,
            ];
        } elseif ($info->authorUrl) {
            $reference['author'] = $info->authorUrl;
        }

        if ($info->publishedTime) {
            $reference['published'] = $info->publishedTime;
        }

        return $reference;
    }
}
