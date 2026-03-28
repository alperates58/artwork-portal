@php
    $viteManifestPath = public_path('build/.vite/manifest.json');
    $viteManifestPathLegacy = public_path('build/manifest.json');
    $viteHotPath = public_path('hot');
    $manifestFile = file_exists($viteManifestPath) ? $viteManifestPath : (file_exists($viteManifestPathLegacy) ? $viteManifestPathLegacy : null);
    $useViteAssets = $manifestFile !== null || file_exists($viteHotPath);
    $viteAssets = [];
    if ($manifestFile) {
        $manifest = json_decode(file_get_contents($manifestFile), true);
        $entry = $manifest['resources/js/app.js'] ?? null;
        if ($entry) {
            $viteAssets['js'] = asset('build/' . $entry['file']);
            $viteAssets['css'] = isset($entry['css'][0]) ? asset('build/' . $entry['css'][0]) : null;
        }
    }
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

@if($useViteAssets && !empty($viteAssets))
    @if($viteAssets['css'])
        <link rel="stylesheet" href="{{ $viteAssets['css'] }}">
    @endif
    <script type="module" src="{{ $viteAssets['js'] }}"></script>
@else
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: { sans: ['Inter', 'sans-serif'] },
            colors: {
              brand: {
                50: '#fff9ed',
                100: '#fff0cf',
                200: '#ffe09c',
                300: '#ffca5d',
                400: '#ffb632',
                500: '#f49a0b',
                600: '#db7906',
                700: '#b65909',
                800: '#944511',
                900: '#7a3a12'
              }
            }
          }
        }
      }
    </script>
    <style>
      :root{--brand-500:#f49a0b;--brand-600:#db7906;--brand-700:#b65909;--ring:rgba(244,154,11,.26)}
      [x-cloak]{display:none!important}
      .card{background:#fff;border:1px solid rgb(226 232 240);border-radius:18px;box-shadow:0 1px 0 rgba(15,23,42,.04);overflow:hidden}
      .label{display:block;font-size:.875rem;line-height:1.25rem;font-weight:600;color:rgb(51 65 85);margin-bottom:.375rem}
      .hint{font-size:.75rem;line-height:1rem;color:rgb(100 116 139);margin-top:.375rem}
      .err{font-size:.75rem;line-height:1rem;color:rgb(220 38 38);margin-top:.375rem}
      .input{width:100%;padding:.625rem .875rem;font-size:.875rem;line-height:1.25rem;border-radius:12px;border:1px solid rgb(203 213 225);background:#fff;outline:none;transition:box-shadow .15s ease,border-color .15s ease,transform .15s ease}
      .input:focus{border-color:rgba(244,154,11,.8);box-shadow:0 0 0 4px var(--ring)}
      .input.error{border-color:rgba(220,38,38,.7)}
      .input.error:focus{box-shadow:0 0 0 4px rgba(220,38,38,.18)}
      .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;padding:.625rem 1rem;font-size:.875rem;line-height:1.25rem;font-weight:600;border-radius:12px;transition:background-color .15s ease,border-color .15s ease,box-shadow .15s ease,transform .15s ease,color .15s ease;user-select:none;white-space:nowrap}
      .btn:focus{outline:none;box-shadow:0 0 0 4px var(--ring)}
      .btn:disabled{opacity:.6;cursor:not-allowed}
      .btn-primary{color:#fff;background:linear-gradient(180deg,var(--brand-600),var(--brand-700));box-shadow:0 10px 22px rgba(180,89,9,.16)}
      .btn-primary:hover{background:linear-gradient(180deg,var(--brand-500),var(--brand-700))}
      .btn-secondary{color:rgb(30 41 59);background:#fff;border:1px solid rgb(203 213 225)}
      .btn-secondary:hover{background:rgb(248 250 252)}
      .badge{display:inline-flex;align-items:center;padding:.125rem .625rem;border-radius:999px;font-size:.75rem;line-height:1rem;font-weight:600;border:1px solid transparent}
      .badge-success{background:rgb(220 252 231);color:rgb(21 128 61);border-color:rgb(187 247 208)}
      .badge-warning{background:rgb(254 243 199);color:rgb(180 83 9);border-color:rgb(253 230 138)}
      .badge-danger{background:rgb(254 226 226);color:rgb(185 28 28);border-color:rgb(254 202 202)}
      .badge-info{background:rgb(219 234 254);color:rgb(29 78 216);border-color:rgb(191 219 254)}
      .badge-gray{background:rgb(241 245 249);color:rgb(71 85 105);border-color:rgb(226 232 240)}
      .step-dot{width:2rem;height:2rem;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:.75rem;line-height:1rem;font-weight:700;transition:all .2s ease}
      .step-dot.done{background:rgb(16 185 129);color:#fff}
      .step-dot.pending{background:rgb(226 232 240);color:rgb(148 163 184)}
      .step-dot.active{background:linear-gradient(180deg,var(--brand-600),var(--brand-700));color:#fff;box-shadow:0 0 0 6px rgba(244,154,11,.12)}
      .step-line{height:2px;flex:1;transition:background-color .2s ease}
      .step-line.done{background:rgba(16,185,129,.65)}
      .step-line.pending{background:rgb(226 232 240)}
      .sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.75rem .875rem;border-radius:16px;font-size:.875rem;font-weight:500;color:rgb(71 85 105);transition:background-color .15s ease,color .15s ease,box-shadow .15s ease}
      .sidebar-link:hover{background:rgb(248 250 252);color:rgb(15 23 42)}
      .sidebar-link.active{background:linear-gradient(180deg,rgba(255,247,237,.96),rgba(255,237,213,.82));color:rgb(122 58 18);box-shadow:0 12px 30px rgba(244,154,11,.12)}
      .brand-mark{display:flex;align-items:center;justify-content:center;width:4rem;height:4rem;border-radius:24px;background:#fff;border:1px solid rgba(255,255,255,.7);box-shadow:0 14px 30px rgba(15,23,42,.08)}
      .brand-eyebrow{font-size:11px;line-height:1rem;font-weight:600;letter-spacing:.24em;text-transform:uppercase;color:rgb(182 89 9)}
      .settings-nav-link{display:block;padding:1rem;border-radius:22px;border:1px solid transparent;transition:background-color .15s ease,border-color .15s ease,box-shadow .15s ease}
      .settings-nav-link:hover{background:rgba(248,250,252,.8);border-color:rgb(226 232 240)}
      .settings-nav-link.active{border-color:rgba(244,154,11,.24);background:linear-gradient(180deg,rgba(255,247,237,.98),rgba(255,237,213,.78));box-shadow:0 16px 36px rgba(244,154,11,.12)}
      .settings-nav-dot{display:block;width:.625rem;height:.625rem;border-radius:999px;background:rgb(226 232 240);margin-top:.375rem;flex-shrink:0}
      .settings-nav-dot.active{background:rgb(244 154 11);box-shadow:0 0 0 6px rgba(244,154,11,.12)}
      .settings-mini-link{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.625rem .75rem;border-radius:16px;border:1px solid transparent;color:rgb(71 85 105);transition:background-color .15s ease,border-color .15s ease,color .15s ease}
      .settings-mini-link:hover{background:rgb(248 250 252);border-color:rgb(226 232 240);color:rgb(15 23 42)}
      .settings-mini-link.active{background:rgba(255,249,237,.7);border-color:rgb(255 224 156);color:rgb(122 58 18)}
      .page-context-aside{display:none}
      .page-context-aside[data-aside-state='open']{display:block}
      @media (min-width:1280px){.page-context-aside{display:block}.page-context-aside[data-aside-state='closed']{display:none}}
      .update-modal::backdrop{background:rgba(15,23,42,.45)}
    </style>
    <script>
      const asideStoragePrefix = 'lider-portal:aside:';

      function resolveAsideDefault(aside) {
        if (!(aside instanceof HTMLElement)) {
          return 'closed';
        }

        return window.innerWidth >= 1280 ? 'open' : 'closed';
      }

      function applyAsideState(aside, state) {
        if (!(aside instanceof HTMLElement)) {
          return;
        }

        aside.dataset.asideState = state;

        if (aside.id) {
          document.querySelectorAll('[data-aside-toggle][aria-controls="' + aside.id + '"]').forEach(function (button) {
            button.setAttribute('aria-expanded', state === 'open' ? 'true' : 'false');
            button.dataset.asideState = state;
          });
        }
      }

      function initializeAside() {
        const aside = document.querySelector('[data-page-aside]');
        if (!(aside instanceof HTMLElement)) {
          return;
        }

        const storageKey = aside.dataset.asideStorageKey ? asideStoragePrefix + aside.dataset.asideStorageKey : null;
        const storedState = storageKey ? window.localStorage.getItem(storageKey) : null;
        const initialState = storedState === 'open' || storedState === 'closed' ? storedState : resolveAsideDefault(aside);

        applyAsideState(aside, initialState);

        document.querySelectorAll('[data-aside-toggle]').forEach(function (toggle) {
          toggle.addEventListener('click', function () {
            const nextState = aside.dataset.asideState === 'open' ? 'closed' : 'open';

            applyAsideState(aside, nextState);

            if (storageKey) {
              window.localStorage.setItem(storageKey, nextState);
            }
          });
        });
      }

      document.addEventListener('click', function (event) {
        const openTrigger = event.target.closest('[data-dialog-open]');
        if (openTrigger) {
          const dialog = document.getElementById(openTrigger.dataset.dialogOpen);
          if (dialog && dialog.showModal) {
            dialog.showModal();
          }
        }

        const closeTrigger = event.target.closest('[data-dialog-close]');
        if (closeTrigger) {
          const dialog = closeTrigger.closest('dialog');
          if (dialog && dialog.close) {
            dialog.close();
          }
        }
      });

      document.addEventListener('click', function (event) {
        const dialog = event.target;
        if (!(dialog instanceof HTMLDialogElement)) {
          return;
        }

        const rect = dialog.getBoundingClientRect();
        const isOutside =
          event.clientX < rect.left ||
          event.clientX > rect.right ||
          event.clientY < rect.top ||
          event.clientY > rect.bottom;

        if (isOutside) {
          dialog.close();
        }
      });

      initializeAside();
    </script>
@endif
