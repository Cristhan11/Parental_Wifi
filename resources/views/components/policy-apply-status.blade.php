{{-- Live status when blocking rules finish saving (Echo when enabled). --}}
<div id="policyApplyStatusBar" class="mb-4 hidden rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900" role="status">
    <span id="policyApplyStatusText">—</span>
</div>

@auth
<script>
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const bar = document.getElementById('policyApplyStatusBar');
        const text = document.getElementById('policyApplyStatusText');
        const userId = document.querySelector('meta[name="auth-user-id"]')?.getAttribute('content');
        if (!bar || !text || !userId || typeof window.Echo === 'undefined') {
            return;
        }
        const show = function (message) {
            text.textContent = message;
            bar.classList.remove('hidden');
        };
        window.Echo.private('user.' + userId).listen('.policy.apply.status', function (event) {
            if (!event || !event.message) {
                return;
            }
            var state = event.state || '';
            if (state === 'applying') {
                bar.className = 'mb-4 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-900';
            } else if (state === 'applied') {
                bar.className = 'mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-900';
            } else if (state === 'failed' || state === 'retry') {
                bar.className = 'mb-4 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950';
            } else {
                bar.className = 'mb-4 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900';
            }
            show(event.message);
        });
    });
})();
</script>
@endauth
