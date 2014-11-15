<?php

/**
 * conditions under which to (not) show an ad
 * I don’t like huge arrays like this to clutter my classes
 *  and anyway, this might be needed on multiple places
 *
 * at the bottom, you find a filter to be able to extend / remove your own elements
 *
 * elements
 * key - internal id of the condition; needs to be unique, obviously
 * label - title in the dashboard
 * description - (optional) description displayed in the dashboard
 * type - information / markup type
 *      idfield - input field for comma separated lists of ids
 *      radio - radio button
 *      others - added to not trigger internal sanitization
 *
 * note: ’idfield’ always has a {field}_not version that is created automatically and being its own condition
 *
 */

$advanced_ads_slug = Advanced_Ads::get_instance()->get_plugin_slug();

$advanced_ads_ad_conditions = array(
    'posttypes' => array(
        'label' => __('Post Types', $advanced_ads_slug),
        'description' => __('Choose the public post types on which to display the ad.', $advanced_ads_slug),
        'type' => 'textvalues',
        'callback' => array('AdvAds_Display_Condition_Callbacks', 'post_types')
    ),
    'categoryids' => array(
        'label' => __('Categories, Tags and Taxonomies', $advanced_ads_slug),
        'description' => __('Choose terms from public category, tag and other taxonomies a post must belong to in order to have ads.', $advanced_ads_slug),
        'type' => 'idfield',
        'callback' => array('AdvAds_Display_Condition_Callbacks', 'terms')
    ),
    'categoryarchiveids' => array(
        'label' => __('Category Archives', $advanced_ads_slug),
        'description' => __('comma seperated IDs of category archives', $advanced_ads_slug),
        'type' => 'idfield',
        'callback' => array('AdvAds_Display_Condition_Callbacks', 'category_archives')
    ),
    'postids' => array(
        'label' => __('Individual Posts, Pages and Public Post Types', $advanced_ads_slug),
        'description' => __('Choose on which individual posts, pages and public post type pages you want to display or hide ads.', $advanced_ads_slug),
        'type' => 'other',
        'callback' => array('AdvAds_Display_Condition_Callbacks', 'single_posts')
    ),
    'is_front_page' => array(
        'label' => __('Home Page', $advanced_ads_slug),
        'description' => __('(don’t) show on Home page', $advanced_ads_slug),
        'type' => 'radio',
    ),
    'is_singular' => array(
        'label' => __('Singular Pages', $advanced_ads_slug),
        'description' => __('(don’t) show on singular pages/posts', $advanced_ads_slug),
        'type' => 'radio',
    ),
    'is_archive' => array(
        'label' => __('Archive Pages', $advanced_ads_slug),
        'description' => __('(don’t) show on any type of archive page (category, tag, author and date)', $advanced_ads_slug),
        'type' => 'radio',
    ),
    'is_search' => array(
        'label' => __('Search Results', $advanced_ads_slug),
        'description' => __('(don’t) show on search result pages', $advanced_ads_slug),
        'type' => 'radio',
    ),
    'is_404' => array(
        'label' => __('404 Page', $advanced_ads_slug),
        'description' => __('(don’t) show on 404 error page', $advanced_ads_slug),
        'type' => 'radio',
    ),
    'is_attachment' => array(
        'label' => __('Attachment Pages', $advanced_ads_slug),
        'description' => __('(don’t) show on attachment pages', $advanced_ads_slug),
        'type' => 'radio',
    )
);

$advanced_ads_ad_conditions = apply_filters('advanced-ads-conditions', $advanced_ads_ad_conditions);