<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="change-password-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-3 sm:px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="close"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all my-4 sm:my-8 align-middle max-w-md w-full mx-auto" style="background-color: #ffffff;">
                    <!-- Modal Header -->
                    <div class="p-4 sm:p-6 border-b border-[#EEEEEE] flex items-center justify-between">
                        <div>
                            <h3 class="text-base sm:text-lg font-extrabold text-[#111111]" id="change-password-title">
                                Change Your Password
                            </h3>
                            <p class="mt-0.5 text-xs text-[#686868]">
                                Update your personal operations login credentials.
                            </p>
                        </div>
                        <button type="button" wire:click="close" class="text-[#686868] hover:text-[#111111] p-1.5 rounded-lg hover:bg-[#FAFAFA]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <form wire:submit="updatePassword">
                        <div class="p-4 sm:p-6 space-y-4">
                            <!-- Current Password -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Current Password *</label>
                                <input type="password"
                                       wire:model="current_password"
                                       placeholder="Enter your existing password"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('current_password') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- New Password -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">New Password (min. 6 characters) *</label>
                                <input type="password"
                                       wire:model="new_password"
                                       placeholder="Create a strong new password"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                                @error('new_password') <span class="text-xs text-[#DF2600] font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Confirm New Password -->
                            <div>
                                <label class="block text-xs font-bold text-[#111111] mb-1">Confirm New Password *</label>
                                <input type="password"
                                       wire:model="new_password_confirmation"
                                       placeholder="Repeat your new password"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#EEEEEE] bg-[#FAFAFA] text-sm text-[#111111] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#DF2600]/20 focus:border-[#DF2600]">
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="p-4 sm:p-6 border-t border-[#EEEEEE] bg-[#FAFAFA] flex items-center justify-end gap-3">
                            <button type="button"
                                    wire:click="close"
                                    class="px-4 py-2.5 rounded-xl border border-[#EEEEEE] bg-white text-xs font-bold text-[#686868] hover:text-[#111111] transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#DF2600] text-white text-xs font-bold shadow-sm hover:bg-[#b82000] transition-colors disabled:opacity-50">
                                <span wire:loading wire:target="updatePassword" class="animate-spin w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full"></span>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
