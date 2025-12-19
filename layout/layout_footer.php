<?php
// layout_footer.php
?>
        </main>
    </div> <!-- main area -->
</div> <!-- wrapper -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<script>
    function toggleSidebar() {
        const wrapper = document.getElementById('sidebarMobileWrapper');
        if (!wrapper) return;
        wrapper.classList.toggle('hidden');
    }
</script>

</body>
</html>
