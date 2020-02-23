@auth
    <p>
        Logged in to <a href="https://github.com" target="_blank" rel="noopener noreferrer">GitHub</a>
        as <a href="https://github.com/{{ auth()->user()->name }}" target="_blank" rel="noopener noreferrer">{{ auth()->user()->name }}</a>.
        <a href="{{ route('github.logout') }}">Logout</a>
    </p>

    <p>
        Login to your Micropub endpoints below.
    </p>

    @if(isset($errors) && $errors->count())
        <div class="errors">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="post" action="{{ route('indieauth.login') }}">
        <input type="text" name="url" placeholder="https://example.com/">
        <input type="submit" value="Login">
    </form>

    @foreach(auth()->user()->sites as $site)
        <ul>
            <li>
                Endpoint <a href="{{ $site->url }}" target="_blank" rel="noopener noreferrer">{{ $site->url }}</a>
            </li>
            <li>
                Content
                <a href="https://github.com/{{ $site->owner }}/{{ $site->repo }}@if($site->branch !== 'master')/tree/{{ $site->branch }}@endif" target="_blank" rel="noopener noreferrer">
                    {{ $site->owner }}/{{ $site->repo }}@if($site->branch !== 'master')#{{ $site->branch }}@endif
                </a>
            </li>
        </ul>
    @endforeach
@endauth

@guest
    <p>
        This application requires you to login to GitHub in order to proceed. The application requires <em>repo</em> access to publish to your content repositories on your behalf.
    </p>
    <p>
        <a href="{{ route('github.login') }}">Login to GitHub</a>
    </p>
@endguest
