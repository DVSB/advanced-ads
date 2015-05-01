<p class="description"><?php _e( 'Display conditions that are based on the user. Use with caution on cached websites.', ADVADS_SLUG ); ?></p>
<?php $options = $ad->options( 'visitor' ); ?>
<ul id="advanced-ad-visitor-mobile">
    <li>
        <input type="radio" name="advanced_ad[visitor][mobile]"
               id="advanced-ad-visitor-mobile-all" value=""
				<?php checked( empty($options['mobile']), 1 ); ?>/>
        <label for="advanced-ad-visitor-mobile-all"><?php _e( 'Display on all devices', ADVADS_SLUG ); ?></label>
        <input type="radio" name="advanced_ad[visitor][mobile]"
               id="advanced-ad-visitor-mobile-only" value="only"
				<?php checked( $options['mobile'], 'only' ); ?>/>
        <label for="advanced-ad-visitor-mobile-only"><?php _e( 'only on mobile devices', ADVADS_SLUG ); ?></label>
        <input type="radio" name="advanced_ad[visitor][mobile]"
               id="advanced-ad-visitor-mobile-no" value="no"
				<?php checked( $options['mobile'], 'no' ); ?>/>
        <label for="advanced-ad-visitor-mobile-no"><?php _e( 'not on mobile devices', ADVADS_SLUG ); ?></label>
    </li>
</ul>
<?php do_action( 'advanced-ads-visitor-conditions-after', $ad ); ?>
<?php if ( ! defined( 'AAR_SLUG' ) ) : ?>
<p><?php printf( __( 'Define the exact browser width for which an ad should be visible using the <a href="%s" target="_blank">Responsive add-on</a>.', ADVADS_SLUG ), ADVADS_URL . 'add-ons/responsive-ads/' ); ?></p>
<?php endif;