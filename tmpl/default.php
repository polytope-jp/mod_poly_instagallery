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

try {
    $items = InstagramHelper::getInstagramItems($params);
} catch (Exception $e) {
    echo '<p>' . $e->getMessage() . '</p>';
    return;
}

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

$basePath = 'modules/mod_poly_instagallery';

JHtml::_('stylesheet', $basePath . '/css/mod_poly_instagallery.css', array('version' => 'auto', 'relative' => false));
if ($dispType === 'slider') {
    JHtml::_('stylesheet', $basePath . '/css/slick.css', array('version' => 'auto', 'relative' => false));
    JHtml::_('stylesheet', $basePath . '/css/slick-theme_custom.css', array('version' => 'auto', 'relative' => false));
    JHtml::_('script', $basePath . '/js/slick.min.js', array('version' => 'auto', 'relative' => false));
}

?>
<?php if ($dispType === 'slider') : ?>
<script type="text/javascript">
    jQuery(document).ready(function(){
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
    });
</script>
<?php endif; ?>
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
<div class="poly_insta clearfix">
    <?php for ($col = 0; $col < count($items); $col++) : ?>
        <?php
        if ($col >= $itemNum) {
            break;
        }

        $item = $items[$col];
        $mediaType = InstagramHelper::getMediaType($item);
        $mediaUrl = InstagramHelper::getMediaUrl($item);
        ?>
        <a href="<?php echo $item['permalink']; ?>" target="_blank">
            <div class="poly_insta-item">
                <?php if ($mediaType === 'IMAGE') : ?>
                    <img alt="" src="<?php echo $mediaUrl; ?>"/>
                <?php elseif ($mediaType === 'VIDEO') : ?>
                    <video src="<?php echo $mediaUrl; ?>"></video>
                <?php endif; ?>
                <div class="poly_insta-overlay">
                    <div class="poly_insta-iteminfo">
                        <span class="icon-heart"></span><?php echo $item['like_count']; ?>
                        <span class="icon-bubble"></span><?php echo $item['comments_count']; ?>
                    </div>
                    <span class="icon-instagram pos-rb"></span>
                </div>
            </div>
        </a>
    <?php endfor; ?>
</div>