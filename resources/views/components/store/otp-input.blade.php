{{-- Six single-digit boxes bound to one Livewire property. Typing advances, Backspace goes back, pasting fills every box. --}}
@props(['model' => 'code', 'label' => 'Six-digit code'])

<div {{ $attributes->only('class') }}
     x-data="{
         digits: Array(6).fill(''),
         sync() { $wire.set('{{ $model }}', this.digits.join(''), false) },
         type(i, e) {
             const v = e.target.value.replace(/\D/g, '');
             if (v.length > 1) return this.fill(v);
             this.digits[i] = v;
             this.sync();
             if (v && i < 5) this.$refs['d' + (i + 1)].focus();
         },
         back(i, e) {
             if (! this.digits[i] && i > 0) { this.$refs['d' + (i - 1)].focus(); }
         },
         fill(text) {
             const v = text.replace(/\D/g, '').slice(0, 6).split('');
             this.digits = Array.from({ length: 6 }, (_, k) => v[k] ?? '');
             this.sync();
             this.$refs['d' + Math.min(v.length, 5)].focus();
         },
     }"
     x-init="$nextTick(() => $refs.d0.focus())"
     @otp-clear.window="digits = Array(6).fill(''); $nextTick(() => $refs.d0.focus())"
     role="group" aria-label="{{ $label }}">
    <div class="flex justify-center gap-2">
        @for ($i = 0; $i < 6; $i++)
            <input x-ref="d{{ $i }}" x-model="digits[{{ $i }}]" @input="type({{ $i }}, $event)" @keydown.backspace="back({{ $i }}, $event)" @paste.prevent="fill($event.clipboardData.getData('text'))"
                   type="text" inputmode="numeric" maxlength="6" @if ($i === 0) autocomplete="one-time-code" @endif
                   aria-label="Digit {{ $i + 1 }}"
                   @class([
                       'size-11 rounded-sm border bg-grayscale-0 text-center text-[20px] font-bold focus:border-ink focus:bg-surface focus:outline-none',
                       'border-price-40' => $errors->has($model),
                       'border-transparent' => ! $errors->has($model),
                   ])>
        @endfor
    </div>
    @error($model)
        <p class="mt-3 rounded-sm bg-sale-0 px-4 py-3 text-body-sm text-price-40" role="alert">{{ $message }}</p>
    @enderror
</div>
