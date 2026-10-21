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

function prepareInputs(root = document) {
    root.querySelectorAll('input[data-jdp]').forEach((input) => {
        if (input.dataset.jdpMaxDate === 'today') {
            const today = todayFromMeta();

            if (today) {
                input.setAttribute('data-jdp-max-date', `${today.year}/${today.month}/${today.day}`);
            }
        }

        if (! input.hasAttribute('readonly')) {
            input.setAttribute('readonly', 'readonly');
        }
    });
}

let started = false;

export function initJalaliDatepickers() {
    const picker = window.jalaliDatepicker;

    if (! picker) {
        return false;
    }

    prepareInputs();

    const options = {
        selector: 'input[data-jdp]',
        date: true,
        time: false,
        autoHide: true,
        autoShow: true,
        minDate: 'attr',
        maxDate: 'attr',
        zIndex: 100000,
    };

    if (! started) {
        picker.startWatch(options);
        started = true;
    } else {
        picker.updateOptions(options);
    }

    return true;
}

export function bindJalaliDatepickers(root = document) {
    prepareInputs(root);

    if (! initJalaliDatepickers()) {
        return;
    }

    root.querySelectorAll('input[data-jdp]').forEach((input) => {
        if (input.dataset.jdpBound === '1') {
            return;
        }

        input.dataset.jdpBound = '1';

        input.addEventListener('click', () => {
            window.jalaliDatepicker?.show(input);
        });
    });
}

function boot() {
    if (initJalaliDatepickers()) {
        bindJalaliDatepickers();
        return;
    }

    let attempts = 0;
    const timer = window.setInterval(() => {
        attempts += 1;

        if (initJalaliDatepickers()) {
            bindJalaliDatepickers();
            window.clearInterval(timer);
        } else if (attempts > 40) {
            window.clearInterval(timer);
        }
    }, 100);
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
