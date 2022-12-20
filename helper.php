<?php
/**
 * @package     Polytope.InstagramGallery
 * @subpackage  mod_polinstagallery
 *
 * @copyright   Copyright (C) 2020 POLYTOPE, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

require_once __DIR__ . '/helper/instagram.php';

/**
 * Instagram Gallery Helper
 *
 * @package     mod_poly_instagallery
 *
 * @since       version 1.3.0
 */
class ModPolyInstagalleryHelper
{
	public static function getItemsAjax()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		$moduleId = $input->get('moduleId');

		$module = JModuleHelper::getModuleById($moduleId);
		$params = new JRegistry($module->params);
		$result = array();

		$items = InstagramHelper::getInstagramItems($params);
		if (!$items)
		{
			return json_encode($result);
		}

		$itemNum = intval($params->get('gallery_items', 8));

		for ($col = 0; $col < count($items); $col++)
		{
			if ($col >= $itemNum)
			{
				break;
			}

			$item                = $items[$col];
			$item['display_url'] = InstagramHelper::getDisplayUrl($item);
			if (!empty($item['display_url']))
			{
				$result[] = $item;
			}
		}

		return json_encode($result);
	}
}