<?php
/**
 * @package     Polytope.InstagramGallery
 * @subpackage  mod_polinstagallery
 *
 * @copyright   Copyright (C) 2020 POLYTOPE, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/../helper/instagram.php';

// module parameters
$itemNum = intval($params->get('gallery_items', 8));
$colsPc = intval($params->get('gallery_cols', 4));
$colsSp = intval($params->get('gallery_cols_sp', 2));
$breakpoint = intval($params->get('breakpoint', 767));
$hoverColor = $params->get('hover_color', 'rgba(0, 0, 0, 0.6)');
$dispType = $params->get('display_type', 'gallery');
$sliderIconColor = $params->get('slider_icon_color', 'rgb(0, 0, 0)');

$pcWidth = InstagramHelper::floorEx(100 / $colsPc, 5);
$spWidth = InstagramHelper::floorEx(100 / $colsSp, 5);

if ($dispType === 'slider') {
    $pcWidth = 100;
    $spWidth = 100;
}

$mediaPath = 'media/mod_poly_instagallery';

JHtml::_('stylesheet', $mediaPath . '/css/mod_poly_instagallery.css', array('version' => 'auto', 'relative' => false));
if ($dispType === 'slider') {
    JHtml::_('stylesheet', $mediaPath . '/css/slick.css', array('version' => 'auto', 'relative' => false));
    JHtml::_('stylesheet', $mediaPath . '/css/slick-theme_custom.css', array('version' => 'auto', 'relative' => false));
    JHtml::_('script', $mediaPath . '/js/slick.min.js', array('version' => 'auto', 'relative' => false));
}

?>
<style>
    .poly_insta-item .poly_insta-overlay {
        background-color: <?php echo $hoverColor; ?> !important;
    }

    .poly_insta-item {
        width: <?php echo $pcWidth; ?>% !important;
    }
    @media screen and (max-width: <?php echo $breakpoint; ?>px) {
        .poly_insta-item {
            width: <?php echo $spWidth; ?>% !important;
        }
    }
    .slick-prev:before,
    .slick-next:before,
    .slick-dots li.slick-active button:before {
        color: <?php echo $sliderIconColor; ?>;
    }
</style>
<script>
    jQuery(function() {
        jQuery.ajax({
            url: 'index.php?option=com_ajax&module=poly_instagallery&method=getItems&format=json',
            type: "post",
            dataType: "json",
            success :function(response){
                let data = JSON.parse(response.data);

                for (let idx = 0; idx < data.length; idx++) {
                    let item = data[idx];

                    let media = '';
                    if (item.display_type === 'IMAGE') {
                        media = '<img alt="" src="' + item.display_url + '"/>';
                    } else if (item.display_type === 'VIDEO') {
                        media = '<video src="' + item.display_url + '"></video>';
                    }

                    jQuery(
                        '<a href="' + item.permalink + '" target="_blank">' +
                            '<div class="poly_insta-item">' + media +
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

                <?php if ($dispType === 'slider') : ?>
                initializeSlick();
                <?php endif; ?>
            }
        });
    });

    function initializeSlick() {
        jQuery('.poly_insta').slick({
            infinite: false,
            dots: true,
            slidesToShow: <?php echo $colsPc; ?>,
            slidesToScroll: <?php echo $colsPc; ?>,
            responsive: [
                {
                    breakpoint: <?php echo $breakpoint + 1; ?>,
                    settings: {
                        slidesToShow: <?php echo $colsSp; ?>,
                        slidesToScroll: <?php echo $colsSp; ?>,
                    }
                }],
        });
    }
</script>
<div class="poly_insta clearfix"></div>