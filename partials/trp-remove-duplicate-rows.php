<div id="trp-addons-page" class="wrap">

    <h1> <?php esc_html_e( 'TranslatePress Settings', 'translatepress-multilingual' );?></h1>

    <div class="grid feat-header">
        <div class="grid-cell">
            <h2><?php esc_html_e('Remove duplicate rows from TranslatePress tables', 'translatepress-multilingual' );?> </h2>
	        <?php if ( empty( $_GET['trp_rm_duplicates'] ) ){ ?>
                <div>
			        <?php esc_html_e( '<strong>IMPORTANT NOTE: Before performing this action it is strongly recommended to backup the database first.</strong><br><br>This feature can be used to cleanup duplicate entries in TranslatePress trp_dictionary tables. Such duplicates can appear in exceptional situations of unexpected behavior.', 'translatepress-multilingual' )?>
                </div>
                <form onsubmit="return confirm('<?php echo esc_js( __( 'IMPORTANT: It is strongly recommended to backup the database first!\nAre you sure you want to continue?', 'translatepress-multilingual' ) ); ?>');">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Batch size', 'translatepress-multilingual' ); ?></th>
                            <td>
                                <input name="trp_rm_batch_size" type="number" value="10000" step="100" min="100">
                                <p>
                                    <i><?php esc_html_e( 'The number of rows to check at once.<br>Choosing a smaller number helps solve the 502 error "Page took too long to respond" on large databases.<br>May take several minutes depending on the database size.', 'translatepress-multilingual' ); ?></i>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <input type="hidden" name="trp_rm_nonce" value="<?php echo wp_create_nonce('tpremoveduplicaterows')?>">
                    <input type="hidden" name="page" value="trp_remove_duplicate_rows">
                    <input type="hidden" name="trp_rm_batch" value="1">
                    <input type="hidden" name="trp_rm_duplicates" value="<?php echo esc_attr( $this->settings['translation-languages'][0] ); ?>">
                    <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Remove duplicate rows', 'translatepress-multilingual' ); ?>">
                </form>
            <?php } ?>

        </div>
    </div>

</div>