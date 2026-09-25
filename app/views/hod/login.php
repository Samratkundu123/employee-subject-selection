<?php
$pageTitle = 'HOD Portal Login - Brainware University';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-bottom: 3px solid #f59e0b;">
            <h2>HOD Portal Login</h2>
            <p>Department of Computational Sciences Administration</p>
        </div>

        <div class="auth-body">
            <?php if (!empty($error)): ?>
                <div class="alert-custom alert-danger-custom">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span><?= Response::escape($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="/hod/login" method="POST" autocomplete="off">
                <?= Auth::csrfField() ?>

                <div class="form-group">
                    <label for="email">HOD Administrator Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-control-custom" 
                        placeholder="hod.css@brainwareuniversity.ac.in" 
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
                        placeholder="Enter administrative password" 
                        required
                    >
                </div>

                <button type="submit" class="btn-primary-custom" style="margin-top: 1.5rem; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                    <span>Sign In to HOD Dashboard</span>
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </button>
            </form>
        </div>

        <div class="auth-footer">
            <p>Faculty member? <a href="/login">&larr; Return to Faculty Login</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
