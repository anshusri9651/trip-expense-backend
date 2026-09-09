@extends('layouts.app')

@section('content')
<main class="legal-admin">
    <div class="legal-admin-head">
        <a class="brand" href="{{ route('dashboard') }}"><span>✦</span> roamly</a>
        <a href="{{ $document->slug === 'terms' ? route('legal.terms') : route('legal.privacy') }}">View live page ↗</a>
    </div>

    <div class="editor-card">
        <p class="kicker">ADMIN · LEGAL CONTENT</p>
        <h1>Edit {{ $document->title }}</h1>
        <p class="sub">Write and format the content shown to every visitor. Changes are saved securely to the database.</p>

        @if (session('status'))
            <div class="save-message">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.legal.update', $document->slug) }}">
            @csrf
            @method('PUT')
            <label>Page title
                <input name="title" value="{{ old('title', $document->title) }}" required>
            </label>

            <div class="editor-toolbar" role="toolbar">
                <button type="button" data-command="formatBlock" data-value="p">¶</button>
                <button type="button" data-command="formatBlock" data-value="h2">H2</button>
                <button type="button" data-command="bold"><b>B</b></button>
                <button type="button" data-command="italic"><i>I</i></button>
                <button type="button" data-command="underline"><u>U</u></button>
                <button type="button" data-command="insertUnorderedList">• List</button>
                <button type="button" data-command="insertOrderedList">1. List</button>
                <button type="button" data-command="formatBlock" data-value="blockquote">❝</button>
                <button type="button" data-command="createLink">↗ Link</button>
                <button type="button" data-command="removeFormat">Clear</button>
            </div>

            <div id="editor" class="rich-editor" contenteditable="true">{!! old('content', $document->content) !!}</div>
            <textarea name="content" id="content" hidden></textarea>
            <div class="editor-footer">
                <small>Supported formatting is sanitized before storage for safety.</small>
                <button class="primary" type="submit">Save document <span>→</span></button>
            </div>
        </form>
    </div>
</main>
<script>
document.querySelectorAll('[data-command]').forEach(button => button.addEventListener('click', () => {
    const command = button.dataset.command;
    if (command === 'createLink') {
        const url = prompt('Enter a secure https:// link');
        if (url && /^https:\/\//i.test(url)) document.execCommand(command, false, url);
    } else {
        document.execCommand(command, false, button.dataset.value || null);
    }
    document.querySelector('#editor').focus();
}));
document.querySelector('form').addEventListener('submit', () => {
    document.querySelector('#content').value = document.querySelector('#editor').innerHTML;
});
</script>
@endsection
