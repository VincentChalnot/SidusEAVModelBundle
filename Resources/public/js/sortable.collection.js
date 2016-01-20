!function ($) {

    "use strict"; // jshint ;_;

    RegExp.escape= function(s) {
        return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    };

    $(document).ready(function() {
        /* Initialize bootstrap collection sortable */
        $('.bootstrap-collection').sortable({
            update: function () {
                var pattern = $(this).data('input-pattern');
                var regex = new RegExp(RegExp.escape(pattern).replace('__name__', '\\d+'));
                $(this).find(':input[name]').each(function (idx) {
                    var newName = $(this).attr('name').replace(regex, pattern.replace('__name__', idx));
                    $(this).attr('name', newName);
                });
            }
        });
    });
}(window.jQuery);
