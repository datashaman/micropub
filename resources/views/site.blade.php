<p>
    Logged in to <a href="https://indieauth.com" target="_blank" rel="noopener noreferrer">IndieAuth</a>
    as <a href="{{ $site->url }}">{{ $site->url }}</a>.
    <a href="{{ route('indieauth.logout') }}">Logout</a>
</p>

<p>
    Content Repository:
    <a href="https://github.com/{{ $site->owner }}/{{ $site->repo }}@if($site->branch !== 'master')/tree/{{ $site->branch }}@endif">
    {{ $site->owner }}/{{ $site->repo }}@if($site->branch !== 'master')#{{ $site->branch }}@endif
    </a>
</p>
