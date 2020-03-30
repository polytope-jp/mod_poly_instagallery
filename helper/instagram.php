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
    const API_BASEURL = 'https://graph.facebook.com/v6.0/';
    const FIELDS_MEDIA = 'media_url,media_type,comments_count,id,like_count,children{media_url,media_type,permalink},permalink,caption';

    public static function getInstagramItems(JRegistry $params) {
        // Module Parameters
        $from = $params->get('from');

        if ($from === 'username') {
            $items = self::getItemsFromUserName($params);
        }
        elseif ($from === 'hashtag') {
            $items = self::getItemsFromHashTags($params);
        }
        else {
            throw new Exception('parameter "from" is invalid.');
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

    private static function getItemsFromUserName(JRegistry $params) {
        // Module Parameters
        $accountId = $params->get('business_account_id');
        $accessToken = $params->get('access_token');
        $userName = $params->get('username');
        $cacheTime = $params->get('cache_time');
        $itemNum = $params->get('gallery_items');

        $userName = str_replace('@', '', $userName);

        $url = self::API_BASEURL . $accountId
            . '?fields=business_discovery.username(' . $userName . '){followers_count,media_count,media.limit(' . $itemNum . '){' . self::FIELDS_MEDIA . '}}'
            . '&access_token=' . $accessToken;

        $res = self::callJsonApi($url, $cacheTime);
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
            $tagIds = $res['data'];

            if (count($tagIds) > 0) {
                // search
                $url = self::API_BASEURL . $tagIds[0]['id'] . '/top_media?user_id=' . $accountId
                    . '&fields=' . self::FIELDS_MEDIA
                    . '&access_token=' . $accessToken;
                $res = self::callJsonApi($url, $cacheTime);

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
        $cacheId = md5($url);
        $cacheFile = __DIR__ . '/../cache/' . $cacheId . '.json';

        // Search Cache File
        if (JFile::exists($cacheFile)) {
            $cacheJson = file_get_contents($cacheFile);
            $cacheData = json_decode($cacheJson);
            $created = intval($cacheData->created);
            $limit = $created + $cacheTime * 60; // sec.
            $now = time();

            if ($now <= $limit) {
                // Cache Hit
                $result = json_decode($cacheData->response, true);
                return $result;
            }
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
            throw new Exception('API Error');
        }

        $result = json_decode($response, true);
        if (empty($result)) {
            // Error
            throw new Exception('API Error');
        }
        if (array_key_exists('error', $result)) {
            // Error
            throw new Exception('API Error : ' . $result['error']['message']);
        }

        // Save Cache File
        $now = time();
        $cacheData = new stdClass();
        $cacheData->created = $now;
        $cacheData->response = $response;
        $cacheJson = json_encode($cacheData);
        file_put_contents($cacheFile, $cacheJson);

        curl_close($curl);
        return $result;
    }
}