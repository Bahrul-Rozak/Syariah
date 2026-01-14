        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden"></div>

    <script>
        // Toggle sidebar on mobile
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            sidebarOverlay.classList.toggle('hidden');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('hidden');
            sidebarOverlay.classList.add('hidden');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.add('hidden');
                    sidebarOverlay.classList.add('hidden');
                }
            }
        });

        // Auto hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert-auto-hide').forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);

        // Confirm delete actions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('delete-form')) {
                if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>