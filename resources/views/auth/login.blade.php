<x-guest-layout>

    {{-- Heading --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900">Welcome back</h2>
        <p class="mt-1 text-sm text-gray-500">Sign in to continue chatting.</p>
    </div>

    {{-- Session status --}}
    @if (session('status'))
        <div class="mb-5 flex items-center gap-2 bg-green-50 text-green-700 text-sm px-4 py-3 rounded-xl border border-green-200">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email address
            </label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </span>
                <input id="email" name="email" type="email"
                       value="{{ old('email') }}"
                       required autofocus autocomplete="username"
                       placeholder="you@example.com"
                       class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder-gray-400
                              focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white focus:border-transparent transition
                              @error('email') border-red-400 bg-red-50 @enderror">
            </div>
            @error('email')
                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-sm font-medium text-gray-700">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs text-indigo-600 hover:text-indigo-700 hover:underline font-medium transition">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </span>
                <input id="password" name="password" type="password"
                       required autocomplete="current-password"
                       placeholder="Your password"
                       class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-900 placeholder-gray-400
                              focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white focus:border-transparent transition
                              @error('password') border-red-400 bg-red-50 @enderror">
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center gap-2">
            <input id="remember_me" name="remember" type="checkbox"
                   class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
            <label for="remember_me" class="text-sm text-gray-600 cursor-pointer select-none">
                Keep me signed in
            </label>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800
                       text-white text-sm font-semibold rounded-xl transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Sign in to Chat
        </button>

        {{-- Register link --}}
        <p class="text-center text-sm text-gray-500 pt-1">
            Don't have an account?
            <a href="{{ route('register') }}"
               class="text-indigo-600 hover:text-indigo-700 font-semibold hover:underline transition">
                Create one free
            </a>
        </p>
    </form>

</x-guest-layout>
