---
{!! $frontMatter !!}
---
@if($contentType === 'html')
{!! Arr::get($source, 'content.html') !!}
@elseif($contentType === 'text')
{{ Arr::get($source, 'content') }}
@endif
