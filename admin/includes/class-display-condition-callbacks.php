<?php
/**
 * container class for callbacks for display conditions
 *
 * @package WordPress
 * @subpackage Advanced Ads Plugin
 * @since 1.2.2
 */
class AdvAds_Display_Condition_Callbacks {

    /**
     * render display condition for post types
     *
     * @param obj $ad ad object
     * @since 1.2.2
     */
    public static function post_types($ad = false){

        // set defaults
        if(is_object($ad)){
            $_all = (isset($ad->conditions['posttypes']['all'])) ? 1 : 0;
            if(!$_all && !isset($ad->conditions['posttypes']['all']) && empty($ad->conditions['posttypes']['include']) && empty($ad->conditions['posttypes']['exclude'])){
                $_all = 1;
            }
        }

        ?><h4><label class="advads-conditions-all"><input type="checkbox" name="advanced_ad[conditions][posttypes][all]" value="1" <?php checked($_all, 1); ?>><?php
        _e('Display on all public <strong>post types</strong>.', ADVADS_SLUG); ?></label></h4><?php
        $post_types = get_post_types(array('public' => true, 'publicly_queryable' => true), 'object', 'or');
        ?><div class="advads-conditions-single">
        <p class="description"><?php _e('Choose the public post types on which to display the ad.', ADVADS_SLUG); ?></p><?php
        // backward compatibility
        // TODO: remove in a later version
        $_includes = (!empty($ad->conditions['posttypes']['include']) && is_string($ad->conditions['posttypes']['include'])) ? explode(',', $ad->conditions['posttypes']['include']) : array();
        $_excludes = (!empty($ad->conditions['posttypes']['exclude']) && is_string($ad->conditions['posttypes']['exclude'])) ? explode(',', $ad->conditions['posttypes']['exclude']) : array();

        foreach($post_types as $_type_id => $_type){
            // backward compatibility
            // TODO: remove this in a later version
            if($_includes == array() && count($_excludes) > 0 && !in_array($_type_id, $_excludes)){
                $_val = 1;
            } elseif(in_array($_type_id, $_includes)){
                $_val = 1;
            } else {
                $_val = 0;
            }

            if(!$_val && isset($ad->conditions['posttypes']['include']) && is_array($ad->conditions['posttypes']['include']) && in_array($_type_id, $ad->conditions['posttypes']['include'])){
                $_val = 1;
            }

            ?><label><input type="checkbox" name="advanced_ad[conditions][posttypes][include][]" <?php checked($_val, 1); ?> value="<?php echo $_type_id; ?>"><?php echo $_type->label; ?></label><?php
        }
        ?></div><?php
    }

     /**
     * render display condition for post types
     *
     * @param obj $ad ad object
     * @since 1.2.3
     */
    public static function terms($ad = false){

        // set defaults
        if(is_object($ad)){
            $_all = (isset($ad->conditions['categoryids']['all'])) ? 1 : 0;
            if(!$_all && !isset($ad->conditions['categoryids']['all']) && empty($ad->conditions['categoryids']['include']) && empty($ad->conditions['categoryids']['exclude'])){
                $_all = 1;
            }
        }

        if(!empty($ad->conditions['categoryids']['include'])){
            // backward compatibility
            // TODO: remove in a later version; this should already be an array
            if(is_string($ad->conditions['categoryids']['include'])){
                $_includes = explode(',', $ad->conditions['categoryids']['include']);
            } else {
                $_includes = $ad->conditions['categoryids']['include'];
            }
        } else {
            $_includes = array();
        }

        ?><h4><label class="advads-conditions-all"><input type="checkbox" name="advanced_ad[conditions][categoryids][all]" value="1" <?php checked($_all, 1); ?>><?php
        _e('Display for all <strong>categories, tags and taxonomies</strong>.', ADVADS_SLUG); ?></label></h4><?php
        $taxonomies = get_taxonomies(array('public' => true, 'publicly_queryable' => true), 'objects', 'or');
        ?><div class="advads-conditions-single"><h5 class="header"><?php _e('Display here', ADVADS_SLUG); ?></h5><p class="description"><?php _e('Choose terms from public categories, tags and other taxonomies a post must belong to in order to have ads.', ADVADS_SLUG); ?></p>
        <table><?php
        foreach($taxonomies as $_tax):
            if($_tax->name === 'advanced_ads_groups') continue; // exclude adv ads groups

            // limit the number of terms so many terms don’t break the admin page
            $max_terms = absint(apply_filters('advanced-ads-admin-max-terms', 200));
            $terms = get_terms($_tax->name, array('number' => $max_terms));

            if ( !empty( $terms ) && !is_wp_error( $terms ) ):
            ?><tr><th><?php echo $_tax->label; ?></th><?php
            ?><td><?php
                foreach($terms as $_term) :
                ?><label><input type="checkbox" name="advanced_ad[conditions][categoryids][include][]" value="<?php echo $_term->term_id; ?>" <?php
                checked(in_array($_term->term_id, $_includes), true); ?>><?php echo $_term->name; ?></label><?php
                endforeach;
            ?></td></tr><?php
            endif;
        endforeach;
        ?></table><?php

        if(!empty($ad->conditions['categoryids']['exclude'])){
            // backward compatibility
            // TODO: remove in a later version; this should already be an array
            if(is_string($ad->conditions['categoryids']['exclude'])){
                $_excludes = explode(',', $ad->conditions['categoryids']['exclude']);
            } else {
                $_excludes = $ad->conditions['categoryids']['exclude'];
            }
        } else {
            $_excludes = array();
        }

        ?><h5 class="header"><?php _e('Hide from here', ADVADS_SLUG); ?></h5><p class="description"><?php _e('Choose the terms from public categories, tags and other taxonomies a post must belong to hide the ad from it.', ADVADS_SLUG); ?></p>
        <table><?php
        foreach($taxonomies as $_tax):
            if($_tax->name === 'advanced_ads_groups') continue; // exclude adv ads groups

            // limit the number of terms so many terms don’t break the admin page
            $max_terms = absint(apply_filters('advanced-ads-admin-max-terms', 200));
            $terms = get_terms($_tax->name, array('number' => $max_terms));

            if ( !empty( $terms ) && !is_wp_error( $terms ) ):
            ?><tr><th><?php echo $_tax->label; ?></th><?php
            ?><td><?php
                foreach($terms as $_term) :
                ?><label><input type="checkbox" name="advanced_ad[conditions][categoryids][exclude][]" value="<?php echo $_term->term_id; ?>" <?php
                checked(in_array($_term->term_id, $_excludes), true); ?>><?php echo $_term->name; ?></label><?php
                endforeach;
            ?></td></tr><?php
            endif;
        endforeach;
        ?></table></div><?php
    }

     /**
     * render display condition for taxonomy/term archive pages
     *
     * @param obj $ad ad object
     * @since 1.2.5
     */
    public static function category_archives($ad = false){

        // set defaults
        if(is_object($ad)){
            $_all = (isset($ad->conditions['categoryarchiveids']['all'])) ? 1 : 0;
            if(!$_all && empty($ad->conditions['categoryarchiveids']['include']) && empty($ad->conditions['categoryarchiveids']['exclude'])){
                $_all = 1;
            }
        }

        if(!empty($ad->conditions['categoryarchiveids']['include'])){
            // backward compatibility
            // TODO: remove in a later version; this should already be an array
            if(is_string($ad->conditions['categoryarchiveids']['include'])){
                $_includes = explode(',', $ad->conditions['categoryarchiveids']['include']);
            } else {
                $_includes = $ad->conditions['categoryarchiveids']['include'];
            }
        } else {
            $_includes = array();
        }

        ?><h4><label class="advads-conditions-all"><input type="checkbox" name="advanced_ad[conditions][categoryarchiveids][all]" value="1" <?php checked($_all, 1); ?>><?php
        _e('Display on all <strong>category archive pages</strong>.', ADVADS_SLUG); ?></label></h4><?php
        $taxonomies = get_taxonomies(array('public' => true, 'publicly_queryable' => true), 'objects', 'or');
        ?><div class="advads-conditions-single"><table>
                <p class="description"><?php _e('Choose the terms from public categories, tags and other taxonomies on which\'s archive page ads can appear', ADVADS_SLUG); ?></p>
                <table><?php
        foreach($taxonomies as $_tax):
            if($_tax->name === 'advanced_ads_groups') continue; // exclude adv ads groups

            // limit the number of terms so many terms don’t break the admin page
            $max_terms = absint(apply_filters('advanced-ads-admin-max-terms', 200));
            $terms = get_terms($_tax->name, array('number' => $max_terms));

            if ( !empty( $terms ) && !is_wp_error( $terms ) ):
            ?><tr><th><?php echo $_tax->label; ?></th><?php
            ?><td><?php
                foreach($terms as $_term) :
                ?><label><input type="checkbox" name="advanced_ad[conditions][categoryarchiveids][include][]" value="<?php echo $_term->term_id; ?>" <?php
                checked(in_array($_term->term_id, $_includes), true); ?>><?php echo $_term->name; ?></label><?php
                endforeach;
            ?></td></tr><?php
            endif;
        endforeach;
        ?></table><?php

        if(!empty($ad->conditions['categoryarchiveids']['exclude'])){
            // backward compatibility
            // TODO: remove in a later version; this should already be an array
            if(is_string($ad->conditions['categoryarchiveids']['exclude'])){
                $_excludes = explode(',', $ad->conditions['categoryarchiveids']['exclude']);
            } else {
                $_excludes = $ad->conditions['categoryarchiveids']['exclude'];
            }
        } else {
            $_excludes = array();
        }

        ?><h5 class="header"><?php _e('Hide from here', ADVADS_SLUG); ?></h5><p class="description"><?php _e('Choose the terms from public categories, tags and other taxonomies on which\'s archive pages ads are hidden.', ADVADS_SLUG); ?></p>
        <table><?php
        foreach($taxonomies as $_tax):
            if($_tax->name === 'advanced_ads_groups') continue; // exclude adv ads groups

            // limit the number of terms so many terms don’t break the admin page
            $max_terms = absint(apply_filters('advanced-ads-admin-max-terms', 200));
            $terms = get_terms($_tax->name, array('number' => $max_terms));

            if ( !empty( $terms ) && !is_wp_error( $terms ) ):
            ?><tr><th><?php echo $_tax->label; ?></th><?php
            ?><td><?php
                foreach($terms as $_term) :
                ?><label><input type="checkbox" name="advanced_ad[conditions][categoryarchiveids][exclude][]" value="<?php echo $_term->term_id; ?>" <?php
                checked(in_array($_term->term_id, $_excludes), true); ?>><?php echo $_term->name; ?></label><?php
                endforeach;
            ?></td></tr><?php
            endif;
        endforeach;
        ?></table></div><?php
    }

        /**
     * render display condition for single post types
     *
     * @param obj $ad ad object
     * @since 1.2.6
     */
    public static function single_posts($ad = false){

        if(is_object($ad)){
            $_all = (isset($ad->conditions['postids']['all'])) ? 1 : 0;
            if(!$_all && empty($ad->conditions['postids']['include']) && empty($ad->conditions['postids']['exclude'])){
                $_all = 1;
            }
        }

        ?><h4><label class="advads-conditions-all"><input type="checkbox" name="advanced_ad[conditions][postids][all]" value="1" <?php
            checked($_all, 1); ?>><?php _e('Display an all <strong>individual posts, pages</strong> and public post type pages', ADVADS_SLUG); ?></label></h4><?php

        ?><div class="advads-conditions-single">
        <p class="description"><?php _e('Choose on which individual posts, pages and public post type pages you want to display or hide ads.', ADVADS_SLUG); ?></p><?php

        // derrive method from previous setup
        // set defaults
        if(is_object($ad)){
            $_method = (isset($ad->conditions['postids']['method'])) ? $ad->conditions['postids']['method'] : 0;
            if($_method === 0){
                if(empty($ad->conditions['postids']['include']) && !empty($ad->conditions['postids']['exclude'])){
                    $_method = 'exclude';
                } elseif(!empty($ad->conditions['postids']['include']) && empty($ad->conditions['postids']['exclude'])) {
                    $_method = 'include';
                } else {
                    $_method = '';
                }
            }
        }

        ?><p><?php _e('What should happen with ads on the list of individual posts below?', ADVADS_SLUG); ?></p>
        <label><input type="radio" name='advanced_ad[conditions][postids][method]' value='' <?php checked('', $_method); ?>><?php _e('ignore the list', ADVADS_SLUG); ?></label></li>
        <label><input type="radio" name='advanced_ad[conditions][postids][method]' value='include' <?php checked('include', $_method); ?>><?php _e('display the ad only there', ADVADS_SLUG); ?></label></li>
        <label><input type="radio" name='advanced_ad[conditions][postids][method]' value='exclude' <?php checked('exclude', $_method); ?>><?php _e('hide the ad here', ADVADS_SLUG); ?></label></li>
        <?php

        /**
         * Update warning
         * @todo remove on a later version, if no longer needed
         */
        if(!empty($ad->conditions['postids']['include']) && !empty($ad->conditions['postids']['exclude'])){
            ?><div style="color: red;"><p><strong><?php _e('Update warning', ADVADS_SLUG); ?></strong></p>
                <p><?php _e('Due to some conflicts before version 1.2.6, it is from now on only possible to choose either individual pages to include or exclude an ad, but not both with mixed settings. It seems you are still using mixed settings on this page. Please consider changing your setup for this ad.', ADVADS_SLUG); ?></p>
                <p><?php _e('Your old values are:', ADVADS_SLUG); ?></p>
                <p><?php _e('Post IDs the ad is displayed on:', ADVADS_SLUG); echo $ad->conditions['postids']['include']; ?></p>
                <p><?php _e('Post IDs the ad is hidden from:', ADVADS_SLUG); echo $ad->conditions['postids']['exclude']; ?></p>
                <p><?php _e('Below you find the pages the ad is displayed on. If this is ok, just save the ad. If not, please update your settings.', ADVADS_SLUG); ?></p>

        </div><?php
        }

        if(!empty($ad->conditions['postids']['include'])){
            // backward compatibility
            // TODO: remove in a later version; this should already be an array
            if(is_string($ad->conditions['postids']['include'])){
                $_postids = explode(',', $ad->conditions['postids']['include']);
            } else {
                $_postids = $ad->conditions['postids']['include'];
            }
        } elseif(!empty($ad->conditions['postids']['exclude'])){
            // backward compatibility
            // TODO: remove in a later version; this should already be an array
            if(is_string($ad->conditions['postids']['exclude'])){
                $_postids = explode(',', $ad->conditions['postids']['exclude']);
            } else {
                $_postids = $ad->conditions['postids']['exclude'];
            }
        } else {
            $_postids = array();
        }

        ?><ul class='advads-conditions-postids-list'><?php
        if($_postids != array()){
            $args = array(
                'post_type' => 'any',
                // 'post_status' => 'publish',
                'post__in' => $_postids,
                'posts_per_page' => -1,
                // 'ignore_sticky_posts' => 1,
            );

            $the_query = new WP_Query( $args );
            while ( $the_query->have_posts() ) {
                $the_query->next_post();
                echo '<li><a class="remove" href="#">remove</a><a href="'.get_permalink($the_query->post->ID).'">' . get_the_title( $the_query->post->ID ) . '</a><input type="hidden" name="advanced_ad[conditions][postids][ids][]" value="'.$the_query->post->ID.'"></li>';
            }
        }
        ?><li class="show-search"><a href="#"><?php _e('new', ADVADS_SLUG); ?></a>
            <input type="text" style="display:none;" id="advads-display-conditions-individual-post" value="" placeholder="<?php
            _e('type the title', ADVADS_SLUG); ?>"/>
            <?php wp_nonce_field( 'internal-linking', '_ajax_linking_nonce', false ); ?>
        </li>
        </ul>
        </div><?php
    }
}