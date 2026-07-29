@php
    $statusLabels = [
        'profile-updated' => 'Profil berhasil diperbarui.',
        'password-updated' => 'Password berhasil diperbarui.',
        'verification-link-sent' => 'Link verifikasi baru sudah dikirim ke email.',
    ];

    $flashMessages = [];
    $pushFlash = function (?string $type, ?string $title, ?string $message, array $details = []) use (&$flashMessages) {
        if (! filled($message) && empty($details)) {
            return;
        }

        $flashMessages[] = [
            'type' => $type ?: 'info',
            'title' => $title ?: 'Info',
            'message' => $message ?: '',
            'details' => $details,
        ];
    };

    if (session('success')) {
        $pushFlash('success', 'Berhasil', session('success'));
    }

    if (session('warning')) {
        $pushFlash('warning', 'Perhatian', session('warning'));
    }

    if (session('error')) {
        $pushFlash('error', 'Gagal', session('error'));
    }

    if (session('status')) {
        $status = $statusLabels[session('status')] ?? session('status');
        $lowerStatus = \Illuminate\Support\Str::lower($status);
        $statusType = \Illuminate\Support\Str::contains($lowerStatus, ['gagal', 'error', 'habis', 'tidak', 'belum', 'wajib', 'nonaktif', 'terblokir', 'masih'])
            ? 'warning'
            : 'success';

        $pushFlash($statusType, $statusType === 'success' ? 'Berhasil' : 'Perhatian', $status);
    }

    $validationMessages = collect($errors->getBags())
        ->flatMap(fn ($bag) => $bag->all())
        ->unique()
        ->values()
        ->all();

    if (! empty($validationMessages)) {
        $pushFlash('error', 'Periksa form', 'Ada data yang belum sesuai. Cek kembali field yang ditandai.', array_slice($validationMessages, 0, 5));
    }
@endphp

<div x-data="appPopups(@js($flashMessages))" x-cloak>
    <div class="fixed right-4 top-4 z-[80] flex w-[calc(100vw-2rem)] max-w-sm flex-col gap-3 sm:right-6 sm:top-6">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-2 opacity-0 sm:translate-x-2 sm:translate-y-0"
                x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="overflow-hidden rounded-lg border-l-4 bg-white p-4 shadow-xl ring-1 ring-black/5"
                :class="{
                    'border-emerald-500': toast.type === 'success',
                    'border-rose-500': toast.type === 'error',
                    'border-amber-500': toast.type === 'warning',
                    'border-indigo-500': toast.type === 'info'
                }"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                        :class="{
                            'bg-emerald-50 text-emerald-700': toast.type === 'success',
                            'bg-rose-50 text-rose-700': toast.type === 'error',
                            'bg-amber-50 text-amber-700': toast.type === 'warning',
                            'bg-indigo-50 text-indigo-700': toast.type === 'info'
                        }"
                    >
                        <span x-show="toast.type === 'success'" class="text-base font-bold">✓</span>
                        <span x-show="toast.type === 'error'" class="text-base font-bold">!</span>
                        <span x-show="toast.type === 'warning'" class="text-base font-bold">!</span>
                        <span x-show="toast.type === 'info'" class="text-base font-bold">i</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-950" x-text="toast.title"></p>
                        <p class="mt-1 text-sm leading-5 text-gray-600" x-text="toast.message"></p>
                        <template x-if="toast.details && toast.details.length">
                            <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-gray-500">
                                <template x-for="detail in toast.details" :key="detail">
                                    <li x-text="detail"></li>
                                </template>
                            </ul>
                        </template>
                    </div>
                    <button type="button" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="dismiss(toast.id)" aria-label="Tutup popup">
                        <span class="text-lg leading-none">&times;</span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div
        x-show="dialog.open"
        x-transition.opacity
        class="fixed inset-0 z-[90] flex items-center justify-center bg-gray-950/55 px-4 py-6"
        style="display: none;"
        @keydown.escape.window="cancelDialog()"
    >
        <div
            x-show="dialog.open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-3 scale-95 opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-2 scale-95 opacity-0"
            class="w-full max-w-md rounded-lg bg-white p-6 shadow-2xl"
            @click.outside="cancelDialog()"
        >
            <div class="flex items-start gap-4">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full"
                    :class="dialog.variant === 'danger' ? 'bg-rose-50 text-rose-700' : 'bg-indigo-50 text-indigo-700'"
                >
                    <span class="text-xl font-bold" x-text="dialog.variant === 'danger' ? '!' : '?'"></span>
                </div>
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold text-gray-950" x-text="dialog.title"></h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600" x-text="dialog.message"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    x-ref="confirmCancel"
                    class="inline-flex min-h-[42px] items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    @click="cancelDialog()"
                    x-text="dialog.cancelText"
                ></button>
                <button
                    type="button"
                    class="inline-flex min-h-[42px] items-center justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm"
                    :class="dialog.variant === 'danger' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                    @click="confirmDialog()"
                    x-text="dialog.confirmText"
                ></button>
            </div>
        </div>
    </div>
</div>
