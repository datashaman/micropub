---
{!! $frontMatter !!}
---
@if($contentType === 'html')
{!! Arr::get($post, 'properties.content.0.html') !!}
@elseif($contentType === 'text')
{{ Arr::get($post, 'properties.content.0') }}
@endif
