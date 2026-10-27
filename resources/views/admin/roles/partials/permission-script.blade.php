<script>
    $(function () {
        const $permissions = $('.permission-checkbox');
        const $selectAll = $('#select-all');

        function syncSelectAll() {
            const selected = $permissions.filter(':checked').length;
            $selectAll.prop('checked', selected > 0 && selected === $permissions.length);
            $selectAll.prop('indeterminate', selected > 0 && selected < $permissions.length);
        }

        $selectAll.on('change', function () {
            $permissions.prop('checked', this.checked);
            syncSelectAll();
        });
        $permissions.on('change', syncSelectAll);
        syncSelectAll();
    });
</script>
