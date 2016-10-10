<?php if (isset($meta)) : ?>
    <?php if ($meta['status'][0] == 'borrowed' && $meta['user'][0] == wp_get_current_user()->ID) : ?>
        <input type="checkbox" checked data-post="<?= get_the_ID() ?>"
                data-toggle="toggle" data-on="Check-in" data-off="Check-out" data-onstyle="primary"
                data-offstyle="default" class="toggle-check reset-check-in">

    <?php elseif ($meta['status'][0] == 'available'):?>
        <input type="checkbox" data-post="<?= get_the_ID() ?>" data-toggle="toggle" data-on="Check-in" data-off="Check-out" data-onstyle="primary"
               data-offstyle="default" class="toggle-check reset-check-out">
        <?php else :?>
            <?php $user_name = get_user_by('id', $meta['user'][0]);?>
            <span class="checked-by"><span class="dashicons dashicons-yes"></span> Checked out By <?= $user_name->user_login ?></span>
            <span class="check-out-date"> on <?= get_check_out_date($user_name->ID, get_the_ID())?></span>
    <?php endif; ?>
<?php endif;?>
