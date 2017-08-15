jQuery(document).ready(function() {
    var body = $('body');
    body.on('keyup focus', '.tntsearch-field', function() {
        var element = $(this),
            container = element.closest('form').find('.tntsearch-results'),
            value = element.val(),
            data = element.data('tntsearch');

        if (!value.length) {
            container.hide();
            return false;
        }

        if (value.length < (data.min || 3)) { return false; }

        var snippet = data.snippet|| '',
            limit = data.limit || '',
            uri = data.uri;

        $.ajax({
            url: uri,
            data: {
                q: value,
                sl: snippet,
                l: limit,
                ajax: true
            },
            success: function(result) {
                container.show().html(result);
            }
        });
    });

    body.on('click', function(e) {
        e.stopPropagation();
        var dropdown = $('.tntsearch-dropdown'),
            results = $('.tntsearch-results:visible:not(.tntsearch-inpage)');

        if (dropdown.has(e.target).length === 0 && results.has(e.target).length === 0) {
            results.hide();
        }
    });
});