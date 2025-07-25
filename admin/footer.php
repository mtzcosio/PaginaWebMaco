<?php
// admin/partials/footer.php
?>
    </div> <!-- Cierre de #content -->

<!-- Scripts de Bootstrap y Vendor -->
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggler = document.getElementById('sidebar-toggler');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const body = document.body;

    const toggleSidebar = () => {
        sidebar.classList.toggle('active');
        body.classList.toggle('sidebar-active');
    };

    if (sidebarToggler && sidebar) {
        sidebarToggler.addEventListener('click', toggleSidebar);
    }
    if(overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }
});
</script>
</body>
</html>