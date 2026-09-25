</main>

<div id="toastContainer" class="toast-container"></div>

<footer class="footer-custom">
    <div style="max-width: 1280px; margin: 0 auto;">
        <p>&copy; <?= date('Y') ?> <strong>Brainware University</strong> &bull; Department of Computational Sciences &bull; Employee Subject Selection System</p>
    </div>
</footer>

<script>
// Global Toast Helper
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast-msg toast-${type}`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}
</script>

</body>
</html>
