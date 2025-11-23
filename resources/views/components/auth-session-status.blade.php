@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm']) }} style="color: #08E60F;">
        {{ $status }}
    </div>
@endif
