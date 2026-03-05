<script>
    $(document).on('click', '.adv-delete', function() {
        const id = $(this).data('id');
        const row = $('.tr-' + id);

        if (!confirm('Bạn có chắc chắn muốn xoá quảng cáo này?')) {
            return;
        }

        $.ajax({
            url: '/admin/adv/delete/' + id,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id
            },
            success: function(res) {
                if (!res.error) {
                    row.remove();
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function() {
                toastr.error('Đã xảy ra lỗi khi xoá quảng cáo.');
            }
        });
    });
</script>
