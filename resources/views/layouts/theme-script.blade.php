{{-- Applies the saved (or OS) theme before first paint to avoid a flash. --}}
<script>
    (function () {
        try {
            var saved = localStorage.getItem('theme');
            var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
</script>
