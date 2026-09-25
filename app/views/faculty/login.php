<?php
$pageTitle = 'Faculty Login - Employee Subject Selection System';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Faculty Portal</h2>
            <p>Department of Computational Sciences</p>
        </div>

        <div class="auth-body">
            <?php if (!empty($error)): ?>
                <div class="alert-custom alert-danger-custom">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span><?= Response::escape($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($info)): ?>
                <div class="alert-custom alert-warning-custom">
                    <span><?= Response::escape($info) ?></span>
                </div>
            <?php endif; ?>

            <form action="/login" method="POST" autocomplete="off">
                <?= Auth::csrfField() ?>

                <div class="form-group">
                    <label for="email">Official University Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-control-custom" 
                        placeholder="e.g. arindam.cs@brainwareuniversity.ac.in" 
                        value="<?= Response::escape($email ?? '') ?>" 
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-control-custom" 
                        placeholder="Enter your password" 
                        required
                    >
                    <div style="margin-top: 8px; font-size: 0.83rem; color: #475569; display: flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" fill="none" stroke="#0284c7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Default password is <code style="background: #e2e8f0; padding: 2px 7px; border-radius: 4px; font-weight: 700; color: #002147; font-size: 0.88rem;">nopass</code></span>
                    </div>
                </div>

                <button type="submit" class="btn-primary-custom" style="margin-top: 1.5rem;">
                    <span>Login & Select Subjects</span>
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </form>
        </div>

        <div class="auth-footer">
            <p>Are you the Head of Department? <a href="/hod/login">Access HOD Dashboard &rarr;</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
