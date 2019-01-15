<div id="trp-addons-page" class="wrap">

    <h1> <?php _e( 'TranslatePress Settings', 'translatepress-multilingual' );?></h1>

    <div class="grid feat-header">
        <div class="grid-cell">
            <h2><?php _e('Update TranslatePress tables', 'translatepress-multilingual' );?> </h2>
	        <?php if ( empty( $_GET['trp_updb_lang'] ) ){ ?>
                <div>
			        <?php _e( '<p><strong>IMPORTANT NOTE: Before performing this action it is strongly recommended to backup the database first.</strong></p>', 'translatepress-multilingual' )?>
                </div>
                <form onsubmit="return confirm('<?php _e( 'IMPORTANT: It is strongly recommended to backup the database first!\nAre you sure you want to continue?', 'translatepress-multilingual' ) ?>');">
                    <input type="hidden" name="trp_updb_nonce" value="<?php echo wp_create_nonce('tpupdatedatabase')?>">
                    <input type="hidden" name="page" value="trp_update_database">
                    <input type="hidden" name="trp_updb_batch" value="0">
                    <input type="hidden" name="trp_updb_batch_size" value="200">
                    <input type="hidden" name="trp_updb_lang" value="<?php echo $this->settings['translation-languages'][0]?>">
                    <input type="submit" class="button-primary" value="<?php _e( 'Update database', 'translatepress-multilingual' ); ?>">
                </form>
            <?php } ?>

        </div>
    </div>

</div>