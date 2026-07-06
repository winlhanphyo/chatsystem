<div class="space-y-4 p-4 animate-pulse">
    @foreach(range(1, 6) as $i)
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-gray-200 flex-shrink-0"></div>
            <div class="flex-1 space-y-2">
                <div class="h-3.5 bg-gray-200 rounded w-1/3"></div>
                <div class="h-3 bg-gray-200 rounded w-2/3"></div>
            </div>
            <div class="h-3 bg-gray-200 rounded w-8 flex-shrink-0"></div>
        </div>
    @endforeach
</div>
