<div class="mb-6 grid grid-cols-2 gap-1 rounded-2xl border border-indigo-100 bg-slate-50 p-1 shadow-inner">
    <button
        type="button"
        wire:click="switchToMobileLogin"
        @class([
            'rounded-xl px-3 py-2.5 text-sm font-bold transition duration-200',
            'bg-gradient-to-l from-indigo-600 via-violet-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30' => $loginMethod === 'mobile',
            'text-slate-600 hover:bg-white hover:text-indigo-700' => $loginMethod !== 'mobile',
        ])
    >
        ورود با موبایل
    </button>
    <button
        type="button"
        wire:click="switchToEmailLogin"
        @class([
            'rounded-xl px-3 py-2.5 text-sm font-bold transition duration-200',
            'bg-gradient-to-l from-indigo-600 via-violet-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30' => $loginMethod === 'email',
            'text-slate-600 hover:bg-white hover:text-indigo-700' => $loginMethod !== 'email',
        ])
    >
        ورود با ایمیل
    </button>
</div>
