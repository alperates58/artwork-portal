import '../css/app.css';

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
