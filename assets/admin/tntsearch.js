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
                .removeClass('error')
                .empty()
                .html('<i class="fa fa-circle-o-notch fa-spin" />');

            $.ajax({
                type: 'POST',
                url: GravAdmin.config.base_url_relative + '.json/task:reindexTNTSearch',
                data: { 'admin-nonce': GravAdmin.config.admin_nonce }
            }).done(function(success) {
                indexer.addClass('reindex');
                status.addClass('success').html(success.message);
            }).fail(function(error) {
                if (error.responseJSON && error.responseJSON.error) {
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