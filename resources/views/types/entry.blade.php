---
{!! yaml_dump($source, 10) !!}
---
@if($contentType === 'html')
{!! Arr::get($source, 'content.html') !!}
@elseif($contentType === 'text')
{{ Arr::get($source, 'content') }}
@endif
