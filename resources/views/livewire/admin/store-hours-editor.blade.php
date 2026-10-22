<div class="space-y-10">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-extrabold text-[#111111] tracking-tight">Operating Hours & Holiday Closures</h2>
        <p class="mt-1 text-sm text-[#686868]">
            Configure weekly operating hours for pickup scheduling, and add special holiday closure dates. Pickup slot availability adapts dynamically.
        </p>
    </div>

    <!-- Weekly Hours Section -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <div class="p-6 border-b border-[#EEEEEE] bg-[#FAFAFA]/50 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-[#111111]">Weekly Schedule</h3>
                <p class="text-xs text-[#686868]">Pickup slots are generated between opening and closing buffer times.</p>
            </div>
            @if (session('saved_hours'))
                <span class="text-xs font-bold text-[#007D49] bg-[#DAF4E7] px-3 py-1 rounded-full">
                    {{ session('saved_hours') }}
                </span>
            @endif
        </div>

        <form wire:submit="save" class="p-6">
            <div class="divide-y divide-[#EEEEEE]">
                @foreach ($rows as $i => $row)
                    <div class="py-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="w-36 flex items-center gap-2">
                            <span class="text-sm font-bold text-[#111111]">{{ $row['day_name'] }}</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       wire:model.live="rows.{{ $i }}.is_closed" 
                                       class="sr-only peer">
                                <div class="w-9 h-5 bg-[#DEDEDE] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#DF2600]"></div>
                                <span class="ml-2.5 text-xs font-semibold {{ $row['is_closed'] ? 'text-[#DF2600]' : 'text-[#686868]' }}">
                                    {{ $row['is_closed'] ? 'Closed' : 'Open' }}
                                </span>
                            </label>
                        </div>

                        <div class="flex items-center gap-3 {{ $row['is_closed'] ? 'opacity-30 pointer-events-none' : '' }}">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-[#686868]">Open:</span>
                                <input type="text" 
                                       wire:model="rows.{{ $i }}.opens_at" 
                                       placeholder="09:00"
                                       class="w-24 px-3 py-1.5 rounded-lg border border-[#DEDEDE] text-sm font-mono text-center focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600]">
                            </div>

                            <span class="text-[#686868]">—</span>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-[#686868]">Close:</span>
                                <input type="text" 
                                       wire:model="rows.{{ $i }}.closes_at" 
                                       placeholder="19:00"
                                       class="w-24 px-3 py-1.5 rounded-lg border border-[#DEDEDE] text-sm font-mono text-center focus:outline-none focus:border-[#DF2600] focus:ring-1 focus:ring-[#DF2600]">
                            </div>

                            @error("rows.{$i}.closes_at")
                                <span class="text-xs font-semibold text-[#DF2600]">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-6 border-t border-[#EEEEEE] flex justify-end">
                <button type="submit" 
                        class="px-6 py-2.5 rounded-xl bg-[#DF2600] hover:bg-[#E64A24] text-white text-sm font-extrabold shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-[#DF2600]">
                    Save Opening Hours
                </button>
            </div>
        </form>
    </div>

    <!-- Special Closures & Holidays Section -->
    <div class="bg-white rounded-2xl border border-[#EEEEEE] shadow-xs overflow-hidden">
        <div class="p-6 border-b border-[#EEEEEE] bg-[#FAFAFA]/50 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-extrabold text-[#111111]">Store Closures & Holidays</h3>
                <p class="text-xs text-[#686868]">Dates where the store is completely closed for pickups and fulfillment.</p>
            </div>
            @if (session('saved_closure'))
                <span class="text-xs font-bold text-[#007D49] bg-[#DAF4E7] px-3 py-1 rounded-full">
                    {{ session('saved_closure') }}
                </span>
            @endif
        </div>

        <div class="p-6 space-y-6">
            <!-- Add Closure Form -->
            <form wire:submit="addClosure" class="flex flex-col sm:flex-row gap-4 items-end bg-[#FAFAFA] p-4 rounded-xl border border-[#EEEEEE]">
                <div class="w-full sm:w-48 space-y-1">
                    <label class="block text-xs font-bold text-[#111111]">Closure Date</label>
                    <input type="date" 
                           wire:model="closureDate"
                           min="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 rounded-lg border border-[#DEDEDE] bg-white text-sm focus:outline-none focus:border-[#DF2600]">
                    @error('closureDate') <span class="text-xs text-[#DF2600] font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="flex-1 space-y-1">
                    <label class="block text-xs font-bold text-[#111111]">Reason / Note (optional)</label>
                    <input type="text" 
                           wire:model="closureReason"
                           placeholder="e.g. Civic Holiday, Store Inventory Day, New Year"
                           class="w-full px-3 py-2 rounded-lg border border-[#DEDEDE] bg-white text-sm focus:outline-none focus:border-[#DF2600]">
                    @error('closureReason') <span class="text-xs text-[#DF2600] font-semibold">{{ $message }}</span> @enderror
                </div>

                <button type="submit"
                        class="px-5 py-2.5 rounded-lg bg-[#111111] hover:bg-black text-white text-sm font-bold transition-colors shrink-0">
                    Add Closure
                </button>
            </form>

            <!-- Closures Table -->
            @if ($closures->isNotEmpty())
                <div class="border border-[#EEEEEE] rounded-xl overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-[#FAFAFA] border-b border-[#EEEEEE] text-[11px] font-extrabold uppercase text-[#686868]">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Day of Week</th>
                                <th class="px-4 py-3">Reason</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EEEEEE]">
                            @foreach ($closures as $c)
                                <tr class="hover:bg-[#FAFAFA]/50 transition-colors">
                                    <td class="px-4 py-3 font-mono font-bold text-[#111111]">{{ $c->date->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-sm text-[#686868]">{{ $c->date->format('l') }}</td>
                                    <td class="px-4 py-3 text-sm text-[#111111]">{{ $c->reason ?: 'Scheduled Store Closure' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" 
                                                wire:click="removeClosure({{ $c->id }})"
                                                wire:confirm="Remove this closure date?"
                                                class="text-xs font-semibold text-[#DF2600] hover:underline">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-[#686868] italic">No upcoming closure dates scheduled.</p>
            @endif
        </div>
    </div>
</div>
