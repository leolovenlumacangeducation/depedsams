        <footer class="footer mt-auto py-3">
            <div class="container-fluid">
                <div class="row align-items-center justify-content-between">
                    <div class="col-6">
                        <p class="mb-0 text-muted">
                            <small>&copy; <?php echo date('Y'); ?> DEPED SAMS. All rights reserved.</small>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </main>
</div>

<!-- Core JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.1/dist/sweetalert2.all.min.js"></script>

<!-- Common scripts -->
<script src="assets/js/theme.js"></script>
<!-- User-specific scripts -->
<script src="assets/js/user/main.js"></script>

<!-- Handle theme persistence -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', theme);
});
</script>

</body>
</html>