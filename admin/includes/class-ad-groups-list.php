<?php
/**
 * Groups List Table class.
 *
 * @package Advanced Ads
 * @since 1.4.4
 */
class AdvAds_Groups_List {

    /**
     * array with all groups
     */
    public $groups = array();

    /**
     * array with all ad group types
     */
    public $types = array();

    /**
     * construct the current list
     */
    public function __construct(){

        // set default vars
        $this->taxonomy = Advanced_Ads::AD_GROUP_TAXONOMY;
        $this->post_type = Advanced_Ads::POST_TYPE_SLUG;

        $this->load_groups();

        $this->types = $this->get_ad_group_types();
    }

    /**
     * load ad groups
     */
    public function load_groups(){

        // load all groups
        $search = !empty($_REQUEST['s']) ? trim(wp_unslash($_REQUEST['s'])) : '';

        $args = array(
            'taxonomy' => $this->taxonomy,
            'search' => $search,
            'hide_empty' => 0,
        );
        // get wp term objects
        $terms = Advanced_Ads::get_ad_groups($args);

        // add meta data to groups
        $this->groups = $this->load_groups_objects_from_terms($terms);
    }

    /**
     * load ad groups objects from wp term objects
     *
     * @param arr $terms array of wp term objects
     */
    protected function load_groups_objects_from_terms(array $terms){

        $groups = array();
        foreach($terms as $_group ){
            $groups[] = new Advads_Ad_Group($_group);
        }

        return $groups;
    }

    /**
     * render group list header
     */
    public function render_header(){
        $file = ADVADS_BASE_PATH . 'admin/views/ad-group-list-header.php';
        require_once($file);
    }

    /**
     * render list rows
     */
    public function render_rows(){
        foreach($this->groups as $_group){
            $this->render_row($_group);
            $this->render_form_row($_group);
        }
    }

    /**
     * render a single row
     *
     * @param obj $group the ad group object
     */
    public function render_row($group){
        $file = ADVADS_BASE_PATH . 'admin/views/ad-group-list-row.php';
        require($file);
    }

    /**
     * render the form row of a group
     *
     * @param obj $group the ad group object
     */
    public function render_form_row(Advads_Ad_Group $group){

        // query ads
        $ads = $this->get_ads($group);
        $weights = $group->get_ad_weights();

        // The Loop
        $ad_form_rows = array();
        if ( $ads->post_count ) {
            foreach($ads->posts as $_ad)  {
                $row = '';
                $ad_id = $_ad->ID;
                $row .= '<tr><td>' . $_ad->post_title . '</td><td>';
                $row .= '<select name="advads-groups['. $group->id . '][ads]['.$_ad->ID.']">';
                $ad_weight = (isset($weights[$ad_id])) ? $weights[$ad_id] : Advads_Ad_Group::MAX_AD_GROUP_WEIGHT;
                for ( $i = 0; $i <= Advads_Ad_Group::MAX_AD_GROUP_WEIGHT; $i++ ) {
                    $row .= '<option ' . selected( $ad_weight, $i, false ) . '>' . $i . '</option>';
                }
                $row .= '</select></td></tr>';
                $ad_form_rows[] = $row;
            }
        }
        // Restore original Post Data
        wp_reset_postdata();

        $file = ADVADS_BASE_PATH . 'admin/views/ad-group-list-form-row.php';
        require($file);
    }

    /**
     * render the ads list
     *
     * @param $obj $group group object
     */
    public function render_ads_list(Advads_Ad_Group $group){

        $ads = $this->get_ads($group);

        $weights = $group->get_ad_weights();
        $weight_sum = array_sum( $weights );

        // The Loop
        if ( $ads->have_posts() ) {
            echo '<ul>';
            while ( $ads->have_posts() ) {
                $ads->the_post();
                echo '<li><a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_title() . '</a>';
                $_weight = (isset($weights[get_the_ID()])) ? $weights[get_the_ID()] : Advads_Ad_Group::MAX_AD_GROUP_WEIGHT;
                $_weight_string = ($group->type == 'default' && $weight_sum) ? number_format(($_weight / $weight_sum) * 100) . '%' : $_weight;
                echo '<span class="ad-weight" title="'.__('Ad weight', ADVADS_SLUG).'">' . $_weight_string .'</span></li>';
            }
            echo '</ul>';
            if($group->ad_count > 1) echo '<p>' . sprintf(__('up to %d ads displayed', ADVADS_SLUG), $group->ad_count) . '</p>';
        } else {
            _e('No ads assigned', ADVADS_SLUG);
        }
        // Restore original Post Data
        wp_reset_postdata();
    }

    /**
     * get ads for this group
     *
     * @param   obj $group group object
     * @return  obj $ads WP_Query result with ads for this group
     */
    public function get_ads($group){
        $args = array(
            'post_type' => $this->post_type,
            'post_status' => 'publish',
            'taxonomy' => $group->taxonomy,
            'term' => $group->slug
        );
        return $ads = new WP_Query($args);
    }

    /**
     * return ad group types
     *
     * @return arr $types ad group information
     */
    public function get_ad_group_types(){
        $types = array(
            'default' => array(
                'title' => __('Random ads', ADVADS_SLUG),
                'description' => __('Display ads randomly based on ad weight', ADVADS_SLUG)
            ),
            'ordered' => array(
                'title' => __('Ordered ads', ADVADS_SLUG),
                'description' => __('Display ads with the lowest ad weight first', ADVADS_SLUG),
            )
        );

        return apply_filters('advanced-ads-group-types', $types);
    }

    /**
     * render ad group action links
     *
     * @param $obj $group group object
     */
    public function render_action_links($group){
        global $tax;

        $tax = get_taxonomy($this->taxonomy);

        $actions = array();
        if (current_user_can($tax->cap->edit_terms)) {
            $actions['edit'] = '<a class="edit">' . __('Edit', ADVADS_SLUG) . '</a>';
            $actions['usage'] = '<a class="usage">' . __('Usage', ADVADS_SLUG) . '</a>';
        }

        if (current_user_can($tax->cap->delete_terms)){
            $args = array(
                'action' => 'delete',
                'group_id' => $group->id
            );
            $delete_link = Advanced_Ads_Admin::group_page_url($args);
            $actions['delete'] = "<a class='delete-tag' href='" . wp_nonce_url($delete_link, 'delete-tag_' . $group->id) . "'>" . __('Delete') . "</a>";
        }

        if (!count($actions)) return;

        echo '<div class="row-actions">';
        foreach ( $actions as $action => $link ) {
            echo "<span class='$action'>$link</span>";
        }
        echo '</div>';
    }

    /**
     * bulk update groups
     *
     */
    public function update_groups(){
        // check nonce
        if(! isset( $_POST['advads-group-update-nonce'] )
            || ! wp_verify_nonce( $_POST['advads-group-update-nonce'], 'update-advads-groups' )){

            return new WP_Error('invalid_ad_group', __('Invalid Ad Group', ADVADS_SLUG));
        }

        // check user rights
        if(!current_user_can('manage_options')){
            return new WP_Error('invalid_ad_group_rights', __('You don’t have permission to change the ad groups', ADVADS_SLUG));
        }


        // iterate through groups
        if(isset($_POST['advads-groups']) && count($_POST['advads-groups'])){
            // empty group settings
            update_option( 'advads-ad-groups', array());

            foreach($_POST['advads-groups'] as $_group_id => $_group){
                // save basic wp term
                wp_update_term( $_group_id, Advanced_Ads::AD_GROUP_TAXONOMY, $_group );

                // save ad weights
                $group = new Advads_Ad_Group($_group['id']);
                if(isset($_group['ads']))
                    $group->save_ad_weights($_group['ads']);

                // save other attributes
                $type       = isset($_group['type']) ? $_group['type'] : 'default';
                $ad_count   = isset($_group['ad_count']) ? $_group['ad_count'] : 1;
                $atts = array(
                    'type' => $type,
                    'ad_count' => $ad_count
                );
                $group->save($atts);
            }
        }

        // reload groups
        $this->load_groups();

        return true;
    }

}