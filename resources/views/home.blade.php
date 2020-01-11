Logged in as <a href="{{ Session::get('user.me') }}">{{ Session::get('user.me') }}</a>  with scope(s) <em>{{ Session::get('user.scope') }}</em>.
<a href="{{ route('auth.logout') }}">Logout</a>

<form method="post" action="{{ route('entry.store') }}">
    @csrf
    <textarea name="content"></textarea>

    <input type="submit" value="Post">
</form>
