<!-- Global action loader -->
<div id="actionLoader" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-surface-900/40 p-4 backdrop-blur-[2px]" aria-hidden="true" role="status" aria-live="polite">
    <div class="w-full max-w-xs rounded-2xl bg-white px-6 py-7 text-center shadow-2xl">
        <div class="action-loader-ring mx-auto mb-4"></div>
        <p id="actionLoaderText" class="text-sm font-semibold text-surface-900">Processing...</p>
        <p id="actionLoaderHint" class="mt-1 text-xs text-surface-400">Please wait, don't close this page.</p>
    </div>
</div>
<div id="topProgress" class="pointer-events-none fixed left-0 top-0 z-[10000] h-[3px] w-0 bg-primary-500 opacity-0 transition-[width,opacity] duration-300"></div>

<script>
    (function () {
        const loader = document.getElementById('actionLoader');
        const loaderText = document.getElementById('actionLoaderText');
        const loaderHint = document.getElementById('actionLoaderHint');
        const progress = document.getElementById('topProgress');
        let progressTimer = null;

        function startTopProgress(amount) {
            if (!progress) return;
            clearTimeout(progressTimer);
            progress.style.transition = 'none';
            progress.style.opacity = '1';
            progress.style.width = (amount || 15) + '%';

            requestAnimationFrame(function () {
                progress.style.transition = 'width 1.8s ease-out, opacity .3s ease';
                progress.style.width = '82%';
            });
        }

        function finishTopProgress() {
            if (!progress) return;
            clearTimeout(progressTimer);
            progress.style.transition = 'width .2s ease, opacity .3s ease';
            progress.style.width = '100%';
            progressTimer = setTimeout(function () {
                progress.style.opacity = '0';
                progress.style.width = '0';
            }, 220);
        }

        window.showActionLoader = function (message, hint) {
            if (!loader) return;
            loaderText.textContent = message || 'Processing...';
            loaderHint.textContent = hint || "Please wait, don't close this page.";
            loader.classList.remove('hidden');
            loader.classList.add('flex');
            loader.setAttribute('aria-hidden', 'false');
            document.body.classList.add('is-loading');
            startTopProgress(80);
        };

        window.hideActionLoader = function () {
            if (!loader) return;
            loader.classList.add('hidden');
            loader.classList.remove('flex');
            loader.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('is-loading');
            finishTopProgress();
        };

        window.startTopProgress = startTopProgress;
        window.finishTopProgress = finishTopProgress;

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (event.defaultPrevented || form.hasAttribute('data-no-loading')) return;

            window.showActionLoader(
                form.dataset.loadingText || 'Processing...',
                form.dataset.loadingHint || "Please wait, don't close this page."
            );
        });

        window.addEventListener('pageshow', function () {
            window.hideActionLoader();
            finishTopProgress();
        });
    })();
</script>
