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

$app    = JFactory::getApplication();
$doc    = $app->getDocument();
$menus  = $app->getMenu();
$active = $menus->getActive();
$itemId = $active->id;

// module parameters
$itemNum         = intval($params->get('gallery_items', 8));
$colsPc          = intval($params->get('gallery_cols', 4));
$colsSp          = intval($params->get('gallery_cols_sp', 2));
$breakpoint      = intval($params->get('breakpoint', 767));
$hoverColor      = $params->get('hover_color', 'rgba(0, 0, 0, 0.6)');
$dispType        = $params->get('display_type', 'gallery');
$sliderIconColor = $params->get('slider_icon_color', 'rgb(0, 0, 0)');

$pcWidth = InstagramHelper::floorEx(100 / $colsPc, 5);
$spWidth = InstagramHelper::floorEx(100 / $colsSp, 5);

if ($dispType === 'slider')
{
	$pcWidth = 100;
	$spWidth = 100;
}


// Load JS / CSS
$mediaPath = 'media/mod_poly_instagallery';

JHtml::_('jquery.framework');
if ($dispType === 'slider')
{
	JHtml::_('stylesheet', $mediaPath . '/css/slick.css', array('version' => 'auto', 'relative' => false));
	JHtml::_('stylesheet', $mediaPath . '/css/slick-theme_custom.css', array('version' => 'auto', 'relative' => false));
	JHtml::_('script', $mediaPath . '/js/slick.min.js', array('version' => 'auto', 'relative' => false));
}

JHtml::_('stylesheet', $mediaPath . '/css/mod_poly_instagallery.css', array('version' => 'auto', 'relative' => false));
$doc->addStyleDeclaration("
.poly_insta-item .poly_insta-overlay {
    background-color: {$hoverColor} !important;
}

.poly_insta-item {
    width: {$pcWidth}% !important;
}

@media screen and (max-width: {$breakpoint}px) {
    .poly_insta-item {
        width: {$spWidth}% !important;
    }
}

.slick-prev:before,
.slick-next:before,
.slick-dots li.slick-active button:before {
    color: {$sliderIconColor};
}
");

?>
<script>
    let root_url   = '<?php echo JUri::root(); ?>';
    let item_id    = '<?php echo $itemId; ?>';
    let disp_type  = '<?php echo $dispType; ?>';
    let module_id  = '<?php echo $module->id; ?>';
    let cols_pc    = '<?php echo $colsPc; ?>';
    let cols_sp    = '<?php echo $colsSp; ?>';
    let breakpoint = '<?php echo $breakpoint; ?>';
</script>
<script src="<?php echo $mediaPath; ?>/js/mod_poly_instagallery.js"></script>
<div class="poly_insta clearfix"></div>