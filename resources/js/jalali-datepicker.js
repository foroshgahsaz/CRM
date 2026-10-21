import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js';

function parseJalaliDate(value) {
    if (! value) {
        return null;
    }

    const parts = value.split('/').map((part) => Number(part.trim()));

    if (parts.length !== 3 || parts.some((part) => Number.isNaN(part))) {
        return null;
    }

    return { year: parts[0], month: parts[1], day: parts[2] };
}

function todayFromMeta() {
    const meta = document.querySelector('meta[name="jalali-today"]')?.content;

    return parseJalaliDate(meta ?? '');
}

let started = false;

export function initJalaliDatepickers() {
    const picker = window.jalaliDatepicker;

    if (! picker) {
        return;
    }

    const options = {
        selector: 'input[data-jdp]',
        date: true,
        time: false,
        autoHide: true,
        autoShow: true,
        minDate: 'attr',
        maxDate: 'attr',
    };

    if (! started) {
        picker.startWatch(options);
        started = true;

        return;
    }

    picker.updateOptions(options);
}

export function bindJalaliDatepickers(root = document) {
    initJalaliDatepickers();

    root.querySelectorAll('input[data-jdp]').forEach((input) => {
        if (input.dataset.jdpBound === '1') {
            return;
        }

        input.dataset.jdpBound = '1';

        if (input.dataset.jdpMaxDate === 'today') {
            const today = todayFromMeta();

            if (today) {
                input.setAttribute('data-jdp-max-date', `${today.year}/${today.month}/${today.day}`);
            }
        }
    });
}

function boot() {
    bindJalaliDatepickers();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

document.addEventListener('livewire:init', () => {
    if (! window.Livewire) {
        return;
    }

    window.Livewire.hook('morph.updated', () => {
        bindJalaliDatepickers();
    });

    window.Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            queueMicrotask(() => bindJalaliDatepickers());
        });
    });
});
