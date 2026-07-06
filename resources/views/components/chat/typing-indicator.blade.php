<div class="flex items-end gap-2 mb-3">
    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0"
         :style="{ backgroundColor: activeConversation?.other_user?.avatar_color }">
        <template x-if="activeConversation?.other_user?.avatar_url">
            <img :src="activeConversation.other_user.avatar_url"
                 class="w-full h-full rounded-full object-cover">
        </template>
        <template x-if="!activeConversation?.other_user?.avatar_url">
            <span x-text="activeConversation?.other_user?.initials"></span>
        </template>
    </div>
    <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-3 flex items-center gap-1">
        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
    </div>
</div>
