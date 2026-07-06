@props([
    'user'   => null,
    'size'   => 'md',   // sm | md | lg
    'online' => false,
])

@php
    $sizes = [
        'sm' => ['wrap' => 'w-8 h-8',   'text' => 'text-xs', 'dot' => 'w-2.5 h-2.5'],
        'md' => ['wrap' => 'w-11 h-11',  'text' => 'text-sm', 'dot' => 'w-3 h-3'],
        'lg' => ['wrap' => 'w-14 h-14',  'text' => 'text-lg', 'dot' => 'w-3.5 h-3.5'],
    ];
    $s = $sizes[$size];
@endphp

<div class="relative flex-shrink-0">
    <div class="{{ $s['wrap'] }} rounded-full flex items-center justify-center overflow-hidden"
         style="background-color: {{ $user?->avatar_color ?? '#4F46E5' }}">
        @if($user?->avatar_url)
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                 class="w-full h-full object-cover">
        @else
            <span class="{{ $s['text'] }} font-semibold text-white select-none">
                {{ $user?->initials ?? '?' }}
            </span>
        @endif
    </div>

    @if($online)
        <span class="{{ $s['dot'] }} absolute bottom-0 right-0 bg-green-400 border-2 border-white rounded-full"></span>
    @endif
</div>
