
<?php $__env->startSection('title', 'Artwork Yükle'); ?>
<?php $__env->startSection('page-title', 'Artwork Yükle'); ?>

<?php $__env->startSection('header-actions'); ?>
    <a href="<?php echo e(route('orders.show', $line->purchaseOrder)); ?>" class="btn-secondary">← Siparişe Dön</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="max-w-2xl">

    
    <div class="card p-4 mb-5 flex items-center gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-0.5">
                <span class="text-xs font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-600"><?php echo e($line->line_no); ?></span>
                <span class="text-sm font-semibold text-slate-900"><?php echo e($line->product_code); ?></span>
            </div>
            <p class="text-xs text-slate-500">
                <?php echo e($line->purchaseOrder->order_no); ?> · <?php echo e($line->purchaseOrder->supplier->name); ?>

            </p>
        </div>
        <?php if($line->artwork && $line->artwork->revisions->isNotEmpty()): ?>
            <div class="text-right">
                <p class="text-xs text-slate-500">Mevcut revizyon</p>
                <p class="text-sm font-semibold text-slate-900">Rev.<?php echo e($line->artwork->revisions->first()->revision_no); ?></p>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="card p-6">
        <h2 class="text-sm font-semibold text-slate-900 mb-5">
            <?php echo e($line->artwork ? 'Yeni Revizyon Yükle' : 'İlk Artwork Yükle'); ?>

        </h2>

        <form
            method="POST"
            action="<?php echo e(route('artworks.store', $line)); ?>"
            enctype="multipart/form-data"
            id="uploadForm"
        >
            <?php echo csrf_field(); ?>

            
            <div
                id="dropZone"
                class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center
                       hover:border-blue-400 transition-colors cursor-pointer mb-5"
                onclick="document.getElementById('artwork_file').click()"
            >
                <div id="dropIcon" class="mb-3">
                    <svg class="w-10 h-10 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-600" id="dropText">
                    Dosyayı sürükleyin veya <span class="text-blue-600">seçin</span>
                </p>
                <p class="text-xs text-slate-400 mt-1">
                    PDF, AI, EPS, ZIP, PSD, INDD, PNG, TIF — Maks. 1.2 GB
                </p>
                <input
                    type="file"
                    id="artwork_file"
                    name="artwork_file"
                    class="hidden"
                    accept=".pdf,.ai,.eps,.zip,.svg,.png,.jpg,.jpeg,.tif,.tiff,.psd,.indd"
                >
            </div>
            <?php $__errorArgs = ['artwork_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-xs text-red-600 -mt-3 mb-4"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            
            <div id="progressWrapper" class="hidden mb-5">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                    <span id="progressFilename" class="truncate max-w-xs"></span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5">
                    <div id="progressBar" class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" style="width:0%"></div>
                </div>
                <p class="text-xs text-slate-400 mt-1" id="progressSize"></p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="label" for="title">Başlık <span class="text-slate-400 font-normal">(opsiyonel)</span></label>
                    <input type="text" id="title" name="title" value="<?php echo e(old('title')); ?>"
                           class="input" placeholder="Otomatik: dosya adından alınır">
                </div>

                <div>
                    <label class="label" for="notes">Notlar <span class="text-slate-400 font-normal">(opsiyonel)</span></label>
                    <textarea id="notes" name="notes" rows="3"
                              class="input resize-none"
                              placeholder="Revizyon notları, değişiklik açıklaması..."><?php echo e(old('notes')); ?></textarea>
                </div>
            </div>

            <?php if($line->artwork): ?>
                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                    <strong>Dikkat:</strong> Bu işlem mevcut aktif revizyonu (Rev.<?php echo e($line->artwork->revisions->first()->revision_no); ?>)
                    pasife alarak yeni revizyonu aktif yapacak. Eski revizyon silinmez, arşivlenir.
                </div>
            <?php endif; ?>

            <div class="mt-6 flex gap-3">
                <button type="submit" id="submitBtn" class="btn-primary flex-1 justify-center py-2.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Yükle
                </button>
                <a href="<?php echo e(route('orders.show', $line->purchaseOrder)); ?>" class="btn-secondary px-6">İptal</a>
            </div>
        </form>
    </div>

    
    <?php if($line->artwork && $line->artwork->revisions->isNotEmpty()): ?>
        <div class="card mt-5">
            <div class="px-5 py-3 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900">Revizyon Geçmişi</h3>
            </div>
            <div class="divide-y divide-slate-100">
                <?php $__currentLoopData = $line->artwork->revisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rev): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="px-5 py-3 flex items-center gap-3">
                        <span class="text-xs font-mono bg-slate-100 px-2 py-1 rounded text-slate-700 flex-shrink-0">
                            Rev.<?php echo e($rev->revision_no); ?>

                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-slate-800 truncate"><?php echo e($rev->original_filename); ?></p>
                            <p class="text-xs text-slate-400">
                                <?php echo e($rev->file_size_formatted); ?> · <?php echo e($rev->uploadedBy->name); ?> · <?php echo e($rev->created_at->format('d.m.Y H:i')); ?>

                            </p>
                        </div>
                        <?php if($rev->is_active): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Arşiv</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const fileInput   = document.getElementById('artwork_file');
const dropZone    = document.getElementById('dropZone');
const dropText    = document.getElementById('dropText');
const progressW   = document.getElementById('progressWrapper');
const progressBar = document.getElementById('progressBar');
const progressPct = document.getElementById('progressPercent');
const progressFn  = document.getElementById('progressFilename');
const progressSz  = document.getElementById('progressSize');
const submitBtn   = document.getElementById('submitBtn');
const form        = document.getElementById('uploadForm');

fileInput.addEventListener('change', e => showFile(e.target.files[0]));

dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('border-blue-400','bg-blue-50'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-blue-400','bg-blue-50'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-400','bg-blue-50');
    const f = e.dataTransfer.files[0];
    if (f) { fileInput.files = e.dataTransfer.files; showFile(f); }
});

function showFile(file) {
    if (!file) return;
    const mb = (file.size / 1048576).toFixed(1);
    dropText.innerHTML = `<span class="text-blue-600 font-medium">${file.name}</span>`;
    progressFn.textContent = file.name;
    progressSz.textContent = mb + ' MB';
}

form.addEventListener('submit', function(e) {
    if (!fileInput.files.length) return;
    e.preventDefault();
    progressW.classList.remove('hidden');
    submitBtn.disabled = true;
    submitBtn.querySelector('span') && (submitBtn.textContent = 'Yükleniyor...');

    const xhr  = new XMLHttpRequest();
    const data = new FormData(form);

    xhr.upload.addEventListener('progress', ev => {
        if (ev.lengthComputable) {
            const pct = Math.round(ev.loaded / ev.total * 100);
            progressBar.style.width = pct + '%';
            progressPct.textContent = pct + '%';
        }
    });

    xhr.addEventListener('load', () => {
        if (xhr.status === 302 || xhr.responseURL) {
            window.location.href = xhr.responseURL || "<?php echo e(route('orders.show', $line->purchaseOrder)); ?>";
        } else {
            // Fallback — normal form submit
            form.submit();
        }
    });

    xhr.addEventListener('error', () => {
        submitBtn.disabled = false;
        alert('Yükleme başarısız. Lütfen tekrar deneyin.');
    });

    xhr.open('POST', form.action);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(data);
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/artworks/create.blade.php ENDPATH**/ ?>