<?php
/**
 * @package     Polytope.InstagramGallery
 * @subpackage  mod_polinstagallery
 *
 * @copyright   Copyright (C) 2020 POLYTOPE, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

require_once __DIR__ . '/helper/instagram.php';

class ModPolyInstagalleryHelper {
    public static function getItemsAjax() {
        $app = JFactory::getApplication();
        $input = $app->input;

        $moduleId = $input->get('moduleId');

        $module = JModuleHelper::getModuleById($moduleId);
        $params = new JRegistry($module->params);
        $result = array();

        $items = InstagramHelper::getInstagramItems($params);
        if (!$items) {
            return json_encode($result);
        }

        $itemNum = intval($params->get('gallery_items', 8));



        for ($col = 0; $col < count($items); $col++) {
            if ($col >= $itemNum) {
                break;
            }

            $item = $items[$col];
            $item['display_type'] = InstagramHelper::getMediaType($item);
            $item['display_url'] = InstagramHelper::getMediaUrl($item);

            $result[] = $item;
        }

        return json_encode($result);
    }
}