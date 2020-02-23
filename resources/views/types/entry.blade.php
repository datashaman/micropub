---
{!! $frontMatter !!}
---
@if($contentType === 'html')
{!! Arr::get($post, 'content.html') !!}
@elseif($contentType === 'text')
{{ Arr::get($post, 'content') }}
@endif
