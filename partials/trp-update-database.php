<div id="trp-addons-page" class="wrap">

    <h1> <?php _e( 'TranslatePress Settings', 'translatepress-multilingual' );?></h1>

    <div class="grid feat-header">
        <div class="grid-cell">
            <h2><?php _e('Update TranslatePress tables', 'translatepress-multilingual' );?> </h2>
	        <?php if ( empty( $_GET['trp_updb_lang'] ) ){ ?>
                <div>
			        <?php _e( '<strong>IMPORTANT NOTE: Before performing this action it is strongly recommended to backup the database first.</strong>', 'translatepress-multilingual' )?>
                </div>
                <form onsubmit="return confirm('<?php _e( 'IMPORTANT: It is strongly recommended to backup the database first!\nAre you sure you want to continue?', 'translatepress-multilingual' ) ?>');">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e( 'Batch size', 'translatepress-multilingual' ); ?></th>
                            <td>
                                <input name="trp_updb_batch_size" type="number" value="10000" step="100" min="100">
                                <p>
                                    <i><?php _e( 'The number of rows to update at once.<br>Choosing a smaller number helps solve the 502 error "Page took too long to respond" on large databases.<br>May take several minutes depending on the database size.', 'translatepress-multilingual' ); ?></i>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <input type="hidden" name="trp_updb_nonce" value="<?php echo wp_create_nonce('tpupdatedatabase')?>">
                    <input type="hidden" name="page" value="trp_update_database">
                    <input type="hidden" name="trp_updb_batch" value="0">
                    <input type="hidden" name="trp_updb_lang" value="<?php echo $this->settings['translation-languages'][0]?>">
                    <input type="submit" class="button-primary" value="<?php _e( 'Update database', 'translatepress-multilingual' ); ?>">
                </form>
            <?php } ?>

        </div>
    </div>

</div>