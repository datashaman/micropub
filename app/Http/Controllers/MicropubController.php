<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Html2Text\Html2Text;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Str;
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

        $mf2 = collect($mpRequest->toMf2());
        Log::debug('Mf2', compact('mf2'));

        $contentType = is_string($mf2->get('properties.content.0'))
            ? 'text'
            : 'html';

        $published = $mf2->has('properties.published')
            ? Carbon::parse($mf2->get('properties.published'))
            : Carbon::now();

        $contentType = is_string($mf2->get('properties.content.0'))
            ? 'text'
            : 'html';

        $frontMatter = collect()
            ->merge([
                'date' => $published,
                'type' => 'post',
            ])
            ->when(
                $mf2->get('properties.category'),
                function ($coll, $tags) {
                    return $coll->put('tags', $tags);
                }
            )
            ->when(
                $mf2->get('files'),
                function ($coll, $files) {

                }
            )
            ->when(
                $mf2->get('properties.photo'),
                function ($coll, $photos) {
                    return $coll->put(
                        'photo',
                        collect($photos)
                            ->map(
                                function ($photo) {
                                    return is_string($photo)
                                        ? ['value' => $photo]
                                        : $photo;
                                }
                            )
                            ->all()
                    );
                }
            );

        $view = 'types.' . $mf2->get('type.0');
        $content = view(
            $view,
            [
                'frontMatter' => trim(Yaml::dump($frontMatter)),
                'post' => $mf2,
                'published' => $published->toIso8601String(),
            ]
        )->render();

        $title = $contentType === 'text'
            ? substr($mf2->get('properties.content.0'), 0, 200)
            : (new Html2Text($mf2->get('properties.content.0.html')))->getText();

        $path = $published->format('Y-m-d') . '-' . Str::slug(strtolower($title));
        $slug = $published->format('Y/m/d') . '/' . Str::slug(strtolower($title));

        dd($frontMatter, $content, $title, $path, $slug);
    }
}
