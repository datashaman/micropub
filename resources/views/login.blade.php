@if($errors->count())
    <div class="errors">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form action="{{ route('auth.login') }}" method="post">
    @csrf
    <input type="url" name="url" placeholder="https://example.com" required>
    <input type="submit" value="Log In">
</form>
