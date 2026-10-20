<div class="mb-6 grid grid-cols-2 gap-1 rounded-full border border-indigo-200/80 bg-indigo-50/80 p-1">
    <button
        type="button"
        wire:click="switchToMobileLogin"
        @class([
            'rounded-full px-3 py-2.5 text-sm font-semibold transition',
            'bg-gradient-to-l from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/30' => $loginMethod === 'mobile',
            'text-indigo-700 hover:bg-white/70' => $loginMethod !== 'mobile',
        ])
    >
        ورود با موبایل
    </button>
    <button
        type="button"
        wire:click="switchToEmailLogin"
        @class([
            'rounded-full px-3 py-2.5 text-sm font-semibold transition',
            'bg-gradient-to-l from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/30' => $loginMethod === 'email',
            'text-indigo-700 hover:bg-white/70' => $loginMethod !== 'email',
        ])
    >
        ورود با ایمیل
    </button>
</div>
