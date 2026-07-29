import './bootstrap';

import Alpine from 'alpinejs';
import { initAdminCharts } from './charts';

window.Alpine = Alpine;
window.initAdminCharts = initAdminCharts;

document.addEventListener('alpine:init', () => {
    Alpine.data('appPopups', (initialMessages = []) => ({
        toasts: [],
        dialog: {
            open: false,
            title: '',
            message: '',
            confirmText: 'Lanjutkan',
            cancelText: 'Batal',
            variant: 'primary',
            resolve: null,
        },

        init() {
            window.AppPopup = {
                notify: (payload) => this.notify(payload),
                confirm: (payload) => this.ask(payload),
                refreshCsrfToken: () => this.refreshCsrfToken(true),
            };

            window.appNotify = window.AppPopup.notify;
            window.appConfirm = window.AppPopup.confirm;

            initialMessages.forEach((message, index) => {
                setTimeout(() => this.notify(message), index * 180);
            });

            document.addEventListener('submit', (event) => this.handleSubmit(event), true);
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    this.refreshCsrfToken(false);
                }
            });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.refreshCsrfToken(false);
                }
            });
        },

        notify(payload) {
            const normalized = typeof payload === 'string' ? { message: payload } : (payload || {});
            const toast = {
                id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                type: normalized.type || 'info',
                title: normalized.title || this.defaultTitle(normalized.type || 'info'),
                message: normalized.message || '',
                details: normalized.details || [],
            };

            this.toasts.push(toast);
            setTimeout(() => this.dismiss(toast.id), normalized.timeout || 5200);
        },

        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },

        defaultTitle(type) {
            return {
                success: 'Berhasil',
                error: 'Gagal',
                warning: 'Perhatian',
                info: 'Info',
            }[type] || 'Info';
        },

        ask(payload = {}) {
            return new Promise((resolve) => {
                this.dialog = {
                    open: true,
                    title: payload.title || 'Konfirmasi tindakan',
                    message: payload.message || 'Lanjutkan proses ini?',
                    confirmText: payload.confirmText || 'Lanjutkan',
                    cancelText: payload.cancelText || 'Batal',
                    variant: payload.variant || 'primary',
                    resolve,
                };

                this.$nextTick(() => {
                    this.$refs.confirmCancel?.focus();
                });
            });
        },

        confirmDialog() {
            const resolve = this.dialog.resolve;
            this.dialog.open = false;
            this.dialog.resolve = null;
            resolve?.(true);
        },

        cancelDialog() {
            const resolve = this.dialog.resolve;
            this.dialog.open = false;
            this.dialog.resolve = null;
            resolve?.(false);
        },

        async handleSubmit(event) {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const htmlMethod = (form.getAttribute('method') || 'GET').toUpperCase();

            if (htmlMethod === 'GET') {
                return;
            }

            if (form.hasAttribute('data-popup-skip')) {
                return;
            }

            if (form.dataset.appSubmitting === '1') {
                delete form.dataset.appSubmitting;
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            if (form.hasAttribute('data-confirm')) {
                const ok = await this.ask(this.confirmPayload(form));

                if (!ok) {
                    return;
                }
            }

            const csrfFresh = await this.refreshCsrfToken(true);

            if (!csrfFresh) {
                return;
            }

            this.submitForm(form, event.submitter || null);
        },

        confirmPayload(form) {
            const method = (new FormData(form).get('_method') || form.getAttribute('method') || 'POST').toString().toUpperCase();
            const defaultVariant = method === 'DELETE' ? 'danger' : 'primary';
            const defaultTitle = method === 'DELETE'
                ? 'Hapus data ini?'
                : (method === 'PUT' || method === 'PATCH' ? 'Simpan perubahan?' : 'Lanjutkan proses?');
            const defaultMessage = method === 'DELETE'
                ? 'Data yang dihapus tidak bisa dikembalikan dari halaman ini.'
                : 'Pastikan data yang diisi sudah benar sebelum diproses.';
            const defaultConfirm = method === 'DELETE'
                ? 'Ya, hapus'
                : (method === 'PUT' || method === 'PATCH' ? 'Ya, simpan' : 'Ya, lanjutkan');

            return {
                title: form.dataset.confirmTitle || defaultTitle,
                message: form.dataset.confirmMessage || defaultMessage,
                confirmText: form.dataset.confirmText || defaultConfirm,
                cancelText: form.dataset.confirmCancel || 'Batal',
                variant: form.dataset.confirmVariant || defaultVariant,
            };
        },

        async refreshCsrfToken(showError) {
            const refreshMeta = document.querySelector('meta[name="csrf-refresh-url"]');
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');

            if (!refreshMeta || !tokenMeta) {
                return true;
            }

            try {
                const response = await fetch(refreshMeta.content, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                const data = await response.json();

                if (!response.ok || !data.token) {
                    throw new Error('CSRF token refresh failed.');
                }

                tokenMeta.setAttribute('content', data.token);
                document.querySelectorAll('input[name="_token"]').forEach((input) => {
                    input.value = data.token;
                });

                if (window.axios) {
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.token;
                }

                return true;
            } catch (error) {
                if (showError) {
                    this.notify({
                        type: 'error',
                        title: 'Sesi perlu diperbarui',
                        message: 'Halaman terlalu lama terbuka. Refresh halaman lalu coba lagi.',
                    });
                }

                return false;
            }
        },

        submitForm(form, submitter) {
            form.dataset.appSubmitting = '1';

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter || undefined);
                return;
            }

            form.submit();
        },
    }));
});

Alpine.start();
