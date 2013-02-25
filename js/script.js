jQuery(function($) {

    /**
     * Submit or delete favorite
     */
    $('a.wpf-favorite-link').on('click', function(event) {
        event.preventDefault();

        var $self = $(this);

        var data = {
            post_id: $self.data('id'),
            nonce: wfp.nonce,
            action: 'wfp_action'
        };

        $self.addClass('wfp-loading');

        $.post(wfp.ajaxurl, data, function(res) {

            if (res.success) {
                $self.html(res.data);

            } else {
                alert(wfp.errorMessage);
            }

            // remove loader
            $self.removeClass('wfp-loading');
        });
    });

    /**
     * delete favorite
     */
    $('a.wpf-remove-favorite').on('click', function(event) {
        event.preventDefault();

        var $self = $(this);

        var data = {
            post_id: $self.data('id'),
            nonce: wfp.nonce,
            action: 'wfp_action'
        };

        $self.addClass('wfp-loading');

        $.post(wfp.ajaxurl, data, function(res) {

            if (res.success) {
                window.location.reload();

            } else {
                alert(wfp.errorMessage);
            }

            // remove loader
            $self.removeClass('wfp-loading');
        });
    });
});