<x-chat-layout>
    <div
        x-data="chatApp"
        class="flex h-screen bg-gray-50"
        @keydown.escape.window="closeSearch()"
    >
        {{-- ===== LEFT SIDEBAR ===== --}}
        <aside
            class="flex flex-col bg-white border-r border-gray-200 transition-all duration-200"
            :class="activeConversation && isMobile() ? 'hidden' : 'w-full md:w-80 flex-shrink-0'"
        >
            {{-- Sidebar header --}}
            <div class="px-4 pt-5 pb-3 border-b border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-xl font-bold text-gray-900">Messages</h1>
                    <div class="flex items-center gap-2">
                        {{-- User menu --}}
                        <a href="{{ route('profile.edit') }}"
                           class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                           style="background-color: {{ $currentUser->avatar_color }}"
                           title="Profile">
                            @if($currentUser->avatar_url)
                                <img src="{{ $currentUser->avatar_url }}" class="w-full h-full object-cover">
                            @else
                                {{ $currentUser->initials }}
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Search bar --}}
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        x-ref="searchInput"
                        x-model="searchQuery"
                        @input.debounce.350ms="onSearchInput()"
                        @focus="isSearchFocused = true"
                        type="text"
                        placeholder="Search users…"
                        class="w-full pl-9 pr-4 py-2 text-sm bg-gray-100 rounded-xl border-0 focus:ring-2 focus:ring-indigo-300 focus:bg-white transition-colors"
                    >
                    <button
                        x-show="searchQuery"
                        @click="clearSearch()"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- User search results --}}
            <div x-show="searchQuery.length > 0" class="border-b border-gray-100">
                {{-- Loading --}}
                <div x-show="isSearching" class="px-4 py-3 text-sm text-gray-400">Searching…</div>

                {{-- Results --}}
                <template x-if="!isSearching && searchResults.length > 0">
                    <div>
                        <p class="px-4 pt-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">People</p>
                        <template x-for="user in searchResults" :key="user.id">
                            <button
                                @click="startConversationWith(user)"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors text-left"
                            >
                                <div class="relative flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-semibold overflow-hidden"
                                         :style="{ backgroundColor: user.avatar_color }">
                                        <img x-show="user.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover">
                                        <span x-show="!user.avatar_url" x-text="user.initials"></span>
                                    </div>
                                    <span x-show="user.is_online || onlineUsers.includes(user.id)"
                                          class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="user.name"></p>
                                    <p class="text-xs text-gray-400 truncate" x-text="user.email"></p>
                                </div>
                                <span x-show="user.is_online || onlineUsers.includes(user.id)" class="text-xs text-green-500 font-medium flex-shrink-0">Online</span>
                            </button>
                        </template>
                    </div>
                </template>

                {{-- No results --}}
                <div x-show="!isSearching && searchResults.length === 0 && searchQuery.length >= 2"
                     class="px-4 py-4 text-sm text-gray-400 text-center">
                    No users found for "<span x-text="searchQuery"></span>"
                </div>
            </div>

            {{-- Conversation list --}}
            <div class="flex-1 overflow-y-auto">
                {{-- Loading skeleton --}}
                <div x-show="isLoadingConversations">
                    <x-chat.loading-skeleton />
                </div>

                {{-- Empty conversations --}}
                <div x-show="!isLoadingConversations && conversations.length === 0 && !searchQuery"
                     class="flex flex-col items-center justify-center h-full text-center p-6 text-gray-400">
                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                    </svg>
                    <p class="text-sm font-medium">No conversations yet</p>
                    <p class="text-xs mt-1">Search for a user above to start chatting.</p>
                </div>

                {{-- Conversation items --}}
                <template x-for="conv in conversations" :key="conv.id">
                    <button
                        @click="selectConversation(conv)"
                        class="w-full flex items-center gap-3 px-4 py-3.5 border-b border-gray-50 hover:bg-gray-50 transition-colors text-left"
                        :class="activeConversation?.id === conv.id ? 'bg-indigo-50 border-l-4 border-l-indigo-500 hover:bg-indigo-50' : ''"
                    >
                        {{-- Avatar --}}
                        <div class="relative flex-shrink-0">
                            <div class="w-11 h-11 rounded-full flex items-center justify-center text-white text-sm font-semibold overflow-hidden"
                                 :style="{ backgroundColor: conv.other_user?.avatar_color }">
                                <img x-show="conv.other_user?.avatar_url" :src="conv.other_user?.avatar_url"
                                     class="w-full h-full object-cover">
                                <span x-show="!conv.other_user?.avatar_url" x-text="conv.other_user?.initials"></span>
                            </div>
                            <span x-show="onlineUsers.includes(conv.other_user?.id)"
                                  class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></span>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-0.5">
                                <span class="text-sm font-semibold text-gray-900 truncate"
                                      :class="conv.unread_count > 0 ? 'text-gray-900' : 'text-gray-700'"
                                      x-text="conv.other_user?.name"></span>
                                <span class="text-xs text-gray-400 ml-1 flex-shrink-0"
                                      x-text="formatConvTime(conv.last_message_at || conv.created_at)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 truncate flex-1 min-w-0"
                                      :class="conv.unread_count > 0 ? 'font-medium text-gray-700' : ''"
                                      x-text="lastMessagePreview(conv)"></span>
                                <span x-show="conv.unread_count > 0"
                                      class="ml-2 flex-shrink-0 min-w-[1.25rem] h-5 px-1 bg-indigo-500 text-white text-xs rounded-full flex items-center justify-center font-semibold"
                                      x-text="conv.unread_count > 99 ? '99+' : conv.unread_count"></span>
                            </div>
                        </div>
                    </button>
                </template>
            </div>

            {{-- Sidebar footer (logout) --}}
            <div class="border-t border-gray-100 p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- ===== MAIN CHAT AREA ===== --}}
        <main class="flex-1 flex flex-col min-w-0" :class="!activeConversation && isMobile() ? 'hidden md:flex' : 'flex'">

            {{-- Empty state (no conversation selected) --}}
            <template x-if="!activeConversation">
                <div class="flex-1 flex flex-col items-center justify-center text-center p-8 select-none">
                    <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center mb-6">
                        <svg class="w-12 h-12 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-700 mb-2">Welcome to Chat System</h2>
                    <p class="text-sm text-gray-400 max-w-sm">Select a conversation on the left, or search for a user to start a new one.</p>
                </div>
            </template>

            {{-- Chat window --}}
            <template x-if="activeConversation">
                <div class="flex-1 flex flex-col min-h-0">
                    {{-- Chat header --}}
                    <div class="flex items-center gap-3 px-4 py-3 bg-white border-b border-gray-200 shadow-sm flex-shrink-0">
                        {{-- Back button (mobile) --}}
                        <button @click="activeConversation = null" class="md:hidden text-gray-400 hover:text-gray-600 mr-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>

                        {{-- Other user avatar --}}
                        <div class="relative flex-shrink-0">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-semibold overflow-hidden"
                                 :style="{ backgroundColor: activeConversation.other_user?.avatar_color }">
                                <img x-show="activeConversation.other_user?.avatar_url"
                                     :src="activeConversation.other_user?.avatar_url"
                                     class="w-full h-full object-cover">
                                <span x-show="!activeConversation.other_user?.avatar_url"
                                      x-text="activeConversation.other_user?.initials"></span>
                            </div>
                            <span x-show="onlineUsers.includes(activeConversation.other_user?.id)"
                                  class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full"></span>
                        </div>

                        {{-- Name & status --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate"
                               x-text="activeConversation.other_user?.name"></p>
                            <p class="text-xs"
                               :class="onlineUsers.includes(activeConversation.other_user?.id) ? 'text-green-500' : 'text-gray-400'"
                               x-text="onlineUsers.includes(activeConversation.other_user?.id) ? 'Online' : 'Offline'">
                            </p>
                        </div>

                        {{-- Header actions --}}
                        <a :href="'{{ route('profile.edit') }}'" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </a>
                    </div>

                    {{-- Messages area --}}
                    <div
                        id="messages-container"
                        class="flex-1 overflow-y-auto px-4 py-4 space-y-1"
                        @scroll="onMessagesScroll($event)"
                    >
                        {{-- Load more sentinel --}}
                        <div id="load-more-sentinel" class="h-1"></div>

                        {{-- Loading more indicator --}}
                        <div x-show="isLoadingMore" class="flex justify-center py-3">
                            <div class="flex gap-1">
                                <div class="w-2 h-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay:0ms"></div>
                                <div class="w-2 h-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay:150ms"></div>
                                <div class="w-2 h-2 bg-gray-300 rounded-full animate-bounce" style="animation-delay:300ms"></div>
                            </div>
                        </div>

                        {{-- Loading skeleton for initial messages --}}
                        <div x-show="isLoadingMessages" class="space-y-4 animate-pulse">
                            <div class="flex justify-end"><div class="h-9 w-40 bg-indigo-100 rounded-2xl rounded-tr-sm"></div></div>
                            <div class="flex gap-2"><div class="w-8 h-8 bg-gray-200 rounded-full"></div><div class="h-9 w-48 bg-gray-100 rounded-2xl rounded-tl-sm"></div></div>
                            <div class="flex justify-end"><div class="h-9 w-32 bg-indigo-100 rounded-2xl rounded-tr-sm"></div></div>
                            <div class="flex gap-2"><div class="w-8 h-8 bg-gray-200 rounded-full"></div><div class="h-14 w-56 bg-gray-100 rounded-2xl rounded-tl-sm"></div></div>
                        </div>

                        {{-- Grouped messages (date separators + bubbles) --}}
                        <template x-for="item in groupedMessages" :key="item.key">
                            <div>
                                {{-- Date separator --}}
                                <template x-if="item.type === 'separator'">
                                    <div class="flex items-center my-4">
                                        <div class="flex-1 border-t border-gray-200"></div>
                                        <span class="mx-3 text-xs text-gray-400 font-medium whitespace-nowrap"
                                              x-text="item.label"></span>
                                        <div class="flex-1 border-t border-gray-200"></div>
                                    </div>
                                </template>

                                {{-- Message bubble --}}
                                <template x-if="item.type === 'message'">
                                    <div class="flex mb-1.5"
                                         :class="item.user_id === currentUser.id ? 'justify-end' : 'items-end gap-2'">

                                        {{-- Other user avatar (left side) --}}
                                        <template x-if="item.user_id !== currentUser.id">
                                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0 overflow-hidden"
                                                 :style="{ backgroundColor: activeConversation.other_user?.avatar_color }">
                                                <img x-show="activeConversation.other_user?.avatar_url"
                                                     :src="activeConversation.other_user?.avatar_url"
                                                     class="w-full h-full object-cover">
                                                <span x-show="!activeConversation.other_user?.avatar_url"
                                                      x-text="activeConversation.other_user?.initials"></span>
                                            </div>
                                        </template>

                                        {{-- Bubble content --}}
                                        <div class="max-w-xs lg:max-w-md xl:max-w-lg">
                                            {{-- Text message --}}
                                            <template x-if="item.type_msg === 'text' || !item.type_msg">
                                                <div class="px-4 py-2.5 rounded-2xl break-words text-sm leading-relaxed"
                                                     :class="item.user_id === currentUser.id
                                                         ? 'bg-indigo-500 text-white rounded-tr-sm'
                                                         : 'bg-white text-gray-800 rounded-tl-sm shadow-sm border border-gray-100'">
                                                    <span x-text="item.message"></span>
                                                </div>
                                            </template>

                                            {{-- Image message --}}
                                            <template x-if="item.type_msg === 'image'">
                                                <div class="rounded-2xl overflow-hidden max-w-xs"
                                                     :class="item.user_id === currentUser.id ? 'rounded-tr-sm' : 'rounded-tl-sm'">
                                                    <a :href="item.attachment_url" target="_blank">
                                                        <img :src="item.attachment_url" :alt="item.message || 'Image'"
                                                             class="max-w-full rounded-2xl hover:opacity-90 transition-opacity cursor-pointer"
                                                             :class="item.user_id === currentUser.id ? 'rounded-tr-sm' : 'rounded-tl-sm'">
                                                    </a>
                                                    <p x-show="item.message" x-text="item.message"
                                                       class="text-xs mt-1"
                                                       :class="item.user_id === currentUser.id ? 'text-indigo-200 text-right' : 'text-gray-400'">
                                                    </p>
                                                </div>
                                            </template>

                                            {{-- File message --}}
                                            <template x-if="item.type_msg === 'file'">
                                                <a :href="item.attachment_url" target="_blank"
                                                   class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm"
                                                   :class="item.user_id === currentUser.id
                                                       ? 'bg-indigo-500 text-white rounded-tr-sm hover:bg-indigo-600'
                                                       : 'bg-white text-gray-700 rounded-tl-sm shadow-sm border border-gray-100 hover:bg-gray-50'">
                                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                    </svg>
                                                    <span class="truncate max-w-[160px]" x-text="item.message || 'Download file'"></span>
                                                    <svg class="w-4 h-4 flex-shrink-0 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                    </svg>
                                                </a>
                                            </template>

                                            {{-- Timestamp --}}
                                            <div class="mt-1 px-1"
                                                 :class="item.user_id === currentUser.id ? 'text-right' : ''">
                                                <span class="text-xs text-gray-400" x-text="item.formatted_time"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Typing indicator --}}
                        <div x-show="isOtherTyping" class="flex items-end gap-2 mb-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0 overflow-hidden"
                                 :style="{ backgroundColor: activeConversation?.other_user?.avatar_color }">
                                <img x-show="activeConversation?.other_user?.avatar_url"
                                     :src="activeConversation?.other_user?.avatar_url"
                                     class="w-full h-full object-cover">
                                <span x-show="!activeConversation?.other_user?.avatar_url"
                                      x-text="activeConversation?.other_user?.initials"></span>
                            </div>
                            <div class="bg-white border border-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 flex items-center gap-1 shadow-sm">
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                                <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                            </div>
                        </div>

                        {{-- Bottom anchor for auto-scroll --}}
                        <div id="messages-bottom"></div>
                    </div>

                    {{-- Attachment preview --}}
                    <div x-show="attachmentPreview" class="px-4 py-2 bg-white border-t border-gray-100">
                        <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-xl">
                            <template x-if="attachmentType === 'image'">
                                <img :src="attachmentPreview" class="w-12 h-12 object-cover rounded-lg flex-shrink-0">
                            </template>
                            <template x-if="attachmentType !== 'image'">
                                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </template>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-gray-700 truncate" x-text="attachmentName"></p>
                                <p class="text-xs text-gray-400" x-text="attachmentSize"></p>
                            </div>
                            <button @click="clearAttachment()" class="text-gray-400 hover:text-red-500 transition-colors flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        {{-- Upload progress --}}
                        <div x-show="isUploading" class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-1">
                                <div class="bg-indigo-500 h-1 rounded-full transition-all duration-300"
                                     :style="{ width: uploadProgress + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Message input --}}
                    <div class="px-4 py-3 bg-white border-t border-gray-200 flex-shrink-0">
                        <form @submit.prevent="sendMessage()" class="flex items-end gap-2">
                            {{-- Attach file --}}
                            {{-- Hidden file input (triggered by the button below) --}}
                            <input type="file" x-ref="fileInput" class="hidden"
                                   @change="onFileSelected($event)"
                                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar">
                            <button type="button" @click="$refs.fileInput.click()"
                                    class="flex-shrink-0 w-9 h-9 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-gray-200 transition-colors"
                                    title="Attach file">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                            </button>

                            {{-- Text input --}}
                            <div class="flex-1 relative">
                                <textarea
                                    x-model="newMessage"
                                    x-ref="messageInput"
                                    @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                                    @input="onTyping(); autoResize($refs.messageInput)"
                                    rows="1"
                                    placeholder="Type a message…"
                                    class="w-full resize-none rounded-2xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition-colors"
                                    style="max-height: 120px; overflow-y: auto;"
                                ></textarea>
                            </div>

                            {{-- Send button --}}
                            <button
                                type="submit"
                                :disabled="isSending || (!newMessage.trim() && !attachmentPreview)"
                                class="flex-shrink-0 w-9 h-9 rounded-full bg-indigo-500 text-white flex items-center justify-center hover:bg-indigo-600 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <template x-if="!isSending">
                                    <svg class="w-4 h-4 rotate-90" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                                    </svg>
                                </template>
                                <template x-if="isSending">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </template>
                            </button>
                        </form>
                        <p class="text-xs text-gray-400 mt-1.5 ml-11">Press Enter to send · Shift+Enter for new line</p>
                    </div>
                </div>
            </template>
        </main>
    </div>

    {{-- Bootstrap Alpine with server-rendered data --}}
    <script>
        window.__CHAT_DATA__ = {
            currentUser:   @json($currentUserData),
            conversations: @json($conversationsData),
        };
    </script>
</x-chat-layout>
