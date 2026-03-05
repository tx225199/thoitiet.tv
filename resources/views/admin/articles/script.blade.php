<script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
<script>
    $(document).on('change', '.article-active', function() {
        const checkbox = $(this);
        const id = checkbox.data('id');
        const hidden = checkbox.prop('checked');

        $.ajax({
            url: '{{ route('admin.articles.active') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                hidden: hidden
            },
            success: function(res) {
                if (!res.error) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                    checkbox.bootstrapToggle('toggle');
                }
            },
            error: function() {
                toastr.error('Đã xảy ra lỗi.');
                checkbox.bootstrapToggle('toggle');
            }
        });
    });

    $(document).on('change', '.article-highlight', function() {
        const checkbox = $(this);
        const id = checkbox.data('id');
        const highlight = checkbox.prop('checked');

        $.ajax({
            url: '{{ route('admin.articles.highlight') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                highlight: highlight
            },
            success: function(res) {
                if (!res.error) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                    checkbox.bootstrapToggle('toggle');
                }
            },
            error: function() {
                toastr.error('Đã xảy ra lỗi.');
                checkbox.bootstrapToggle('toggle');
            }
        });
    });
</script>
