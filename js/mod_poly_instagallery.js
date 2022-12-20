jQuery(function ($) {
    if ($('.poly_insta').length > 0) {
        $.ajax({
            url: root_url + '?option=com_ajax&module=poly_instagallery&method=getItems&format=json&moduleId=' + module_id + '&Itemid=' + item_id,
            type: "post",
            dataType: "json",
            success: function (response) {
                let data = JSON.parse(response.data);

                for (let idx = 0; idx < data.length; idx++) {
                    let item = data[idx];
                    $(
                        '<a href="' + item.permalink + '" target="_blank">' +
                        '<div class="poly_insta-item"><img alt="" src="' + item.display_url + '"/>' +
                        '<div class="poly_insta-overlay">' +
                        '<div class="poly_insta-iteminfo">' +
                        '<span class="icon-heart"></span>' + item.like_count +
                        '<span class="icon-bubble"></span>' + item.comments_count +
                        '</div>' +
                        '<span class="icon-instagram pos-rb"></span>' +
                        '</div>' +
                        '</div>' +
                        '</a>'
                    ).appendTo('.poly_insta');
                }

                if (disp_type === 'slider') {
                    initializeSlick();
                }
            }
        });

        function initializeSlick() {
            $('.poly_insta').slick({
                infinite: false,
                dots: true,
                slidesToShow: parseInt(cols_pc),
                slidesToScroll: parseInt(cols_pc),
                responsive: [
                    {
                        breakpoint: parseInt(breakpoint) + 1,
                        settings: {
                            slidesToShow: parseInt(cols_sp),
                            slidesToScroll: parseInt(cols_sp),
                        }
                    }],
            });
        }
    }
});