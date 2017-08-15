((function($) {
    $(document).ready(function() {
        var indexer = $('#tntsearch-index'),
            current = null;
        if (!indexer.length) { return; }

        indexer.on('click', function(e) {
            e.preventDefault();
            var status = indexer.siblings('.tntsearch-status'),
                errorDetails = indexer.siblings('.tntsearch-error-details');
            current = status.clone(true);

            errorDetails
                .hide()
                .empty();

            status
                .removeClass('error success')
                .empty()
                .html('<i class="fa fa-circle-o-notch fa-spin" />');

            $.ajax({
                type: 'POST',
                url: GravAdmin.config.base_url_relative + '.json/task:reindexTNTSearch',
                data: { 'admin-nonce': GravAdmin.config.admin_nonce }
            }).done(function(done) {
                if (done.status === 'success') {
                    indexer.removeClass('critical').addClass('reindex');
                    status.removeClass('error').addClass('success');
                } else {
                    indexer.removeClass('reindex').addClass('critical');
                    status.removeClass('success').addClass('error');
                }

                status.html(done.message);
            }).fail(function(error) {
                if (error.responseJSON && error.responseJSON.error) {
                    indexer.removeClass('reindex').addClass('critical');
                    errorDetails
                        .text(error.responseJSON.error.message)
                        .show();

                    status.replaceWith(current);
                }
            }).always(function() {
                current = null;
            });
        })
    });
})(jQuery));