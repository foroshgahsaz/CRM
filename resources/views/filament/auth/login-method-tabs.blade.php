<div class="mb-6 grid grid-cols-2 gap-1 rounded-xl bg-gray-100 p-1 dark:bg-white/5">
    <button
        type="button"
        wire:click="switchToMobileLogin"
        @class([
            'rounded-lg px-3 py-2.5 text-sm font-medium transition',
            'bg-white text-gray-950 shadow dark:bg-white/10 dark:text-white' => $loginMethod === 'mobile',
            'text-gray-600 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' => $loginMethod !== 'mobile',
        ])
    >
        ورود با موبایل
    </button>
    <button
        type="button"
        wire:click="switchToEmailLogin"
        @class([
            'rounded-lg px-3 py-2.5 text-sm font-medium transition',
            'bg-white text-gray-950 shadow dark:bg-white/10 dark:text-white' => $loginMethod === 'email',
            'text-gray-600 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' => $loginMethod !== 'email',
        ])
    >
        ورود با ایمیل
    </button>
</div>
