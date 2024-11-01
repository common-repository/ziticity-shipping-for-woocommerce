<?php if( ! empty( $lockers ) ) : ?>
    <tr class="wc_shipping_ziticity">
        <th><?php _e('Parcel locker', 'ziticity-shipping-for-woocommerce'); ?></th>
        <td>
            <select class="ziticity-parcel-locker" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $field_name ); ?>" style="width: 100%;">
                <option value="-1"><?php _e( 'Select parcel locker', 'ziticity-shipping-for-woocommerce' ); ?></option>
                <?php foreach( $lockers as $locker ) : ?>
                    <option value="<?php esc_attr_e( $locker['parcel_locker_id'] ); ?>" <?php selected( $selected, $locker['parcel_locker_id'] ); ?>>
                        <?php if ( isset( $locker['distance'] ) ) : ?>
                            <?php echo round( $locker['distance'] / 1000, 2 ) . ' km - ' . $locker['label']; ?>
                        <?php else : ?>
                            <?php echo $locker['label']; ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <script>
                jQuery(document).ready(function($) {
                    $(document).ready(function() {
                        $('.ziticity-parcel-locker').select2({width: '100%'});
                    });
                })
            </script>
        </td>
    </tr>
<?php endif; ?>
