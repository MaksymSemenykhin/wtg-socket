<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="messenger({{ auth()->id() }}, {{ json_encode($users) }})" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex" style="min-height: 70vh;">
                {{-- Users list --}}
                <div class="w-1/3 border-r border-gray-200 flex flex-col">
                    <div class="p-4 border-b border-gray-200 font-medium text-gray-700">{{ __('Users') }}</div>
                    <div class="flex-1 overflow-y-auto">
                        <template x-for="u in users" :key="u.id">
                            <button
                                type="button"
                                @click="selectUser(u)"
                                class="w-full text-left px-4 py-3 hover:bg-gray-50 border-b border-gray-100 flex items-center gap-3"
                                :class="{ 'bg-indigo-50': selectedUser && selectedUser.id === u.id }"
                            >
                                <span class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold shrink-0" x-text="(u.name || '').charAt(0).toUpperCase()"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-gray-900 truncate" x-text="u.name"></div>
                                    <div class="text-sm text-gray-500 truncate" x-text="u.email"></div>
                                </div>
                                <span
                                    x-show="(u.unread_count || 0) > 0"
                                    x-text="u.unread_count > 99 ? '99+' : u.unread_count"
                                    class="shrink-0 min-w-[1.25rem] h-5 px-1.5 flex items-center justify-center rounded-full bg-indigo-600 text-white text-xs font-medium"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Chat area --}}
                <div class="flex-1 flex flex-col">
                    <template x-if="selectedUser">
                        <div class="flex flex-col flex-1">
                            <div class="p-4 border-b border-gray-200 font-medium text-gray-700" x-text="selectedUser ? selectedUser.name : ''"></div>
                            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-container">
                                <template x-for="(m, i) in messages" :key="m._key ?? m.id ?? i">
                                    <div
                                        class="flex"
                                        :class="m.sender_id === currentUserId ? 'justify-end' : 'justify-start'"
                                    >
                                        <div
                                            class="max-w-[75%] rounded-lg px-4 py-2"
                                            :class="m.sender_id === currentUserId ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-900'"
                                        >
                                            <div class="text-xs opacity-80" x-text="(m.sender && m.sender.name) || ''"></div>
                                            <div x-text="m.body || ''"></div>
                                            <div class="text-xs mt-1 opacity-80" x-text="formatTime(m.created_at)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <form @submit.prevent="sendMessage()" class="p-4 border-t border-gray-200 flex gap-2">
                                <input
                                    type="text"
                                    x-model="newMessage"
                                    placeholder="{{ __('Type a message...') }}"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    :disabled="!newMessage.trim()"
                                >
                                    {{ __('Send') }}
                                </button>
                            </form>
                        </div>
                    </template>
                    <template x-if="!selectedUser">
                        <div class="flex-1 flex items-center justify-center text-gray-500">
                            {{ __('Select a user to start messaging') }}
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
