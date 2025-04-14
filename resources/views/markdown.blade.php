@if(View::exists($layout))
    @extends($layout)

    @section('title', $title ?? '')
    @section('description', $description ?? '')

    @section('content')
        <div class="markfolio-content">
            {!! $content !!}
        </div>
    @endsection
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Markdown Page' }}</title>
        <meta name="description" content="{{ $description ?? '' }}">
    </head>
    <body>
        <div class="markfolio-content">
            {!! $content !!}
        </div>
    </body>
    </html>
@endif 