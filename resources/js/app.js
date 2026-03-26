import '../css/app.css';

const asideStoragePrefix = 'lider-portal:aside:';

function resolveAsideDefault(aside) {
  if (!(aside instanceof HTMLElement)) {
    return 'closed';
  }

  const defaultMode = aside.dataset.defaultOpen ?? 'desktop';
  if (defaultMode === 'always') {
    return 'open';
  }

  return window.innerWidth >= 1280 ? 'open' : 'closed';
}

function applyAsideState(aside, state) {
  if (!(aside instanceof HTMLElement)) {
    return;
  }

  aside.dataset.asideState = state;

  const controlsId = aside.id;
  if (controlsId) {
    document
      .querySelectorAll(`[data-aside-toggle][aria-controls="${controlsId}"]`)
      .forEach((button) => {
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

  const storageKey = aside.dataset.asideStorageKey
    ? `${asideStoragePrefix}${aside.dataset.asideStorageKey}`
    : null;
  const storedState = storageKey ? window.localStorage.getItem(storageKey) : null;
  const initialState = storedState === 'open' || storedState === 'closed'
    ? storedState
    : resolveAsideDefault(aside);

  applyAsideState(aside, initialState);

  document.querySelectorAll('[data-aside-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const currentState = aside.dataset.asideState === 'open' ? 'open' : 'closed';
      const nextState = currentState === 'open' ? 'closed' : 'open';

      applyAsideState(aside, nextState);

      if (storageKey) {
        window.localStorage.setItem(storageKey, nextState);
      }
    });
  });
}

document.addEventListener('click', (event) => {
  const openTrigger = event.target.closest('[data-dialog-open]');
  if (openTrigger) {
    const dialog = document.getElementById(openTrigger.dataset.dialogOpen);
    if (dialog?.showModal) {
      dialog.showModal();
    }
  }

  const closeTrigger = event.target.closest('[data-dialog-close]');
  if (closeTrigger) {
    const dialog = closeTrigger.closest('dialog');
    if (dialog?.close) {
      dialog.close();
    }
  }
});

document.addEventListener('click', (event) => {
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
