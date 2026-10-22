<div>
    <!-- Page Header & Overview -->
    <div class="mb-8">
        <h2 class="text-2xl font-extrabold text-[#111111] tracking-tight">Store Settings & Policy Controls</h2>
        <p class="mt-1 text-sm text-[#686868]">
            Manage store behavior dials, pickup intervals, uncollected order grace periods, and return windows. Changes take effect on the storefront immediately.
        </p>
    </div>

    <!-- Group Navigation Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-8 border-b border-[#EEEEEE]">
        @foreach ($groups as $id => $g)
            <button type="button"
                    wire:click="selectGroup('{{ $id }}')"
                    class="px-4 py-2 rounded-xl text-sm font-bold whitespace-nowrap transition-all duration-150 {{ $group === $id ? 'bg-[#111111] text-white shadow-sm' : 'bg-white text-[#686868] border border-[#EEEEEE] hover:bg-[#F6F6F6] hover:text-[#111111]' }}">
                {{ $g['label'] }}
            </button>
        @endforeach
    </div>

    <!-- Flash Message -->
    @if (session('saved'))
        <div class="mb-6 p-4 rounded-xl bg-[#DAF4E7] border border-[#A8DFC4] flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-[#007D49]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-sm font-bold text-[#007D49]">{{ session('saved') }}</span>
            </div>
            <span class="text-xs font-semibold text-[#007D49] opacity-75">Cache purged</span>
        </div>
    @endif

    <!-- Settings Form Container -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <div class="p-6 lg:p-8 border-b border-[#EEEEEE] bg-[#FAFAFA]/50">
            <h3 class="text-lg font-extrabold text-[#111111]">{{ $currentGroup['label'] ?? 'Configuration' }}</h3>
            @if (!empty($currentGroup['description']))
                <p class="mt-1 text-xs text-[#686868]">{{ $currentGroup['description'] }}</p>
            @endif
        </div>

        <form wire:submit="save" class="p-6 lg:p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($currentGroup['fields'] ?? [] as $key => $field)
                    <div class="{{ ($field['type'] ?? '') === 'text' ? 'md:col-span-2' : '' }} space-y-2">
                        <label for="{{ $key }}" class="block text-sm font-bold text-[#111111]">
                            {{ $field['label'] }}
                        </label>

                        @if (($field['type'] ?? '') === 'select')
                            <select id="{{ $key }}" 
                                    wire:model="values.{{ $key }}"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDEDE] bg-white text-sm font-medium text-[#111111] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600] transition-colors">
                                @foreach ($field['options'] ?? [] as $val => $optionLabel)
                                    <option value="{{ $val }}">{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif (($field['type'] ?? '') === 'number')
                            <input type="number" 
                                   id="{{ $key }}" 
                                   wire:model="values.{{ $key }}"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDEDE] bg-white text-sm font-medium text-[#111111] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600] transition-colors">
                        @else
                            <input type="text" 
                                   id="{{ $key }}" 
                                   wire:model="values.{{ $key }}"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#DEDEDE] bg-white text-sm font-medium text-[#111111] focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600] transition-colors">
                        @endif

                        @if (!empty($field['help']))
                            <p class="text-xs text-[#686868]">{{ $field['help'] }}</p>
                        @endif

                        @error("values.{$key}")
                            <p class="text-xs font-semibold text-[#DF2600] mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="pt-6 border-t border-[#EEEEEE] flex items-center justify-between">
                <span class="text-xs text-[#686868]">
                    Last changed entries are recorded into the <span class="font-bold text-[#111111]">setting_changes</span> audit diary.
                </span>

                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-[#DF2600] hover:bg-[#E64A24] text-white text-sm font-extrabold shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[#DF2600]">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
