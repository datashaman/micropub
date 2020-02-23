@auth
    <p>
        Logged in to <a href="https://github.com" target="_blank" rel="noopener noreferrer">GitHub</a>
        as <a href="https://github.com/{{ auth()->user()->name }}" target="_blank" rel="noopener noreferrer">{{ auth()->user()->name }}</a>.
        <a href="{{ route('github.logout') }}">Logout</a>
    </p>

    <p>
        Login to your Micropub endpoints below.
    </p>

    <form method="post" action="{{ route('indieauth.do-login') }}">
        <input type="text" name="url" placeholder="https://example.com/">
        <input type="submit" value="Login">
    </form>

    <ul>
        @foreach(auth()->user()->sites as $site)
            <li>{{ $site->url }}</li>
        @endforeach
    </ul>
@endauth

@guest
    <p>
        This application requires you to login to GitHub in order to proceed. The application requires <em>repo</em> access to publish to your content repositories on your behalf.
    </p>
    <p>
        <a href="{{ route('github.login') }}">Login to GitHub</a>
    </p>
@endguest
