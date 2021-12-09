<?php
/**
 * @package     Polytope.InstagramGallery
 * @subpackage  mod_polinstagallery
 *
 * @copyright   Copyright (C) 2020 POLYTOPE, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class InstagramHelper {
    const LOG_CATEGORY = 'mod_poly_instagallery';
    const API_BASEURL = 'https://graph.facebook.com/v9.0/';
    const FIELDS_MEDIA = 'media_url,media_type,comments_count,id,like_count,children{media_url,media_type,permalink},permalink,caption';

    public static function getInstagramItems(JRegistry $params) {
        JLog::addLogger(array('text_file' => 'mod_poly_instagallery.php'), JLog::ALL, array(self::LOG_CATEGORY));

        // Module Parameters
        $from = $params->get('from');

        if ($from === 'username') {
            $items = self::getItemsFromUserName($params);
        }
        elseif ($from === 'hashtag') {
            $items = self::getItemsFromHashTags($params);
        }
        else {
            return false;
        }

        if (!$items) {
            return false;
        }

        $items = self::removeEmptyAlbum($items);

        return $items;
    }

    private static function removeEmptyAlbum($items) {
        foreach ($items as $itemIdx => $item) {
            $mediaType = $item['media_type'];

            switch ($mediaType) {
                case 'CAROUSEL_ALBUM':
                    foreach ($item['children']['data'] as $childIdx => $child) {
                        if (!array_key_exists('media_url', $child) || !array_key_exists('media_type', $child)) {
                            unset($item['children']['data'][$childIdx]);
                        }
                    }
                    $item['children']['data'] = array_merge($item['children']['data']);
                    if (count($item['children']['data']) === 0) {
                        unset($items[$itemIdx]);
                    }
                    break;
                case 'IMAGE':
                case 'VIDEO':
                    if (!array_key_exists('media_url', $item)) {
                        unset($items[$itemIdx]);
                    }
                    break;
                default:
                    unset($items[$itemIdx]);
                    break;
            }
        }
        $items = array_merge($items);
        return $items;
    }

    public static function getMediaUrl($item) {
        $mediaType = $item['media_type'];
        $url = '';

        switch ($mediaType) {
            case 'CAROUSEL_ALBUM':
                $url = $item['children']['data'][0]['media_url'];
                break;
            case 'IMAGE':
            case 'VIDEO':
                $url = $item['media_url'];
                break;
            default:
                break;
        }

        return $url;
    }

    public static function getMediaType($item) {
        $mediaType = $item['media_type'];
        $type = '';

        switch ($mediaType) {
            case 'CAROUSEL_ALBUM':
                $type = $item['children']['data'][0]['media_type'];
                break;
            case 'IMAGE':
            case 'VIDEO':
                $type = $item['media_type'];
                break;
            default:
                break;
        }

        return $type;
    }

    public static function floorEx($value, $precision = 1) {
        return round($value - 0.5 * pow(0.1, $precision), $precision, PHP_ROUND_HALF_UP);
    }

    private static function getItemsFromUserName(JRegistry $params) {
        // Module Parameters
        $accountId = $params->get('business_account_id');
        $accessToken = $params->get('access_token');
        $userName = $params->get('username');
        $cacheTime = intval($params->get('cache_time', 60));
        $itemNum = $params->get('gallery_items');

        $userName = str_replace('@', '', $userName);

        $url = self::API_BASEURL . $accountId
            . '?fields=business_discovery.username(' . $userName . '){followers_count,media_count,media.limit(' . $itemNum . '){' . self::FIELDS_MEDIA . '}}'
            . '&access_token=' . $accessToken;

        $res = self::callJsonApi($url, $cacheTime);
        if (!$res) {
            return false;
        }
        $items = $res['business_discovery']['media']['data'];

        return $items;
    }

    private static function getItemsFromHashTags(JRegistry $params)
    {
        // Module Parameters
        $accountId = $params->get('business_account_id');
        $accessToken = $params->get('access_token');
        $hashTag = $params->get('hashtag');
        $cacheTime = $params->get('cache_time');

        $items = array();
        $tags = explode(' ', $hashTag);
        $tagsNum = count($tags);

        foreach ($tags as $tagIdx => $tag) {
            $tag = str_replace('#', '', $tag);

            // get hashtag ids
            $url = self::API_BASEURL . 'ig_hashtag_search?user_id=' . $accountId
                . '&q=' . $tag
                . '&access_token=' . $accessToken;
            $res = self::callJsonApi($url, $cacheTime);
            if (!$res) {
                return false;
            }
            $tagIds = $res['data'];

            if (count($tagIds) > 0) {
                // search
                $url = self::API_BASEURL . $tagIds[0]['id'] . '/top_media?user_id=' . $accountId
                    . '&fields=' . self::FIELDS_MEDIA
                    . '&access_token=' . $accessToken;
                $res = self::callJsonApi($url, $cacheTime);
                if (!$res) {
                    return false;
                }

                for ($itemIdx = 0; $itemIdx < count($res['data']); $itemIdx++) {
                    $res['data'][$itemIdx]['sort_key'] = $tagIdx + $itemIdx * $tagsNum;
                }

                $items = array_merge($items, $res['data']);
            }
        }

        // sort alternately
        $sortKeys = array();
        foreach ($items as $item) {
            $sortKeys[] = $item['sort_key'];
        }
        array_multisort($sortKeys, SORT_ASC, SORT_NUMERIC, $items);

        // unique by id
        $ids = array();
        foreach ($items as $itemIdx => $item) {
            if (array_key_exists($item['id'], $ids)) {
                unset($items[$itemIdx]);
            }
            else {
                $ids[$item['id']] = true;
            }
        }
        $items = array_merge($items);

        return $items;
    }

    private static function callJsonApi($url, $cacheTime) {
        // Search Cache File
        $cache = JFactory::getCache('mod_poly_instagallery', '')->cache;
        $cache->setCaching(true);
        $cache->setLifeTime($cacheTime);

        $cacheId = md5($url);

        $cacheJson = $cache->get($cacheId);
        if (!empty($cacheJson)) {
            $cacheData = json_decode($cacheJson);
            $result = json_decode($cacheData->response, true);
            return $result;
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $errno = curl_errno($curl);

        if (CURLE_OK !== $errno) {
            // Error
            JLog::add('API Error', JLog::ERROR, self::LOG_CATEGORY);
            return false;
        }

        $result = json_decode($response, true);
        if (empty($result)) {
            // Error
            JLog::add('API Error', JLog::ERROR, self::LOG_CATEGORY);
            return false;
        }
        if (array_key_exists('error', $result)) {
            // Error
            JLog::add('API Error : ' . $result['error']['message'], JLog::ERROR, self::LOG_CATEGORY);
            return false;
        }

        // Save Cache File
        $now = time();
        $cacheData = new stdClass();
        $cacheData->created = $now;
        $cacheData->response = $response;
        $cacheJson = json_encode($cacheData);
        $cache->store($cacheJson, $cacheId);

        curl_close($curl);
        return $result;
    }
}