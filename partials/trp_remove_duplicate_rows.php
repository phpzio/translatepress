<?php


//trp_remove_duplicate_rows

?>
<div id="trp-addons-page" class="wrap">

    <h1> <?php _e( 'TranslatePress Settings', 'translatepress-multilingual' );?></h1>

    <div class="grid feat-header">
        <div class="grid-cell">
            <h2><?php _e('Remove duplicate rows from TranslatePress tables', 'translatepress-multilingual');?> </h2>

            <h3><?php if ( empty( $_GET['trp_rm_duplicates'] ) ){ ?>
                <a href="?page=trp_remove_duplicate_rows&trp_rm_duplicates=<?php echo $this->settings['translation-languages'][0]?>">
                    <input type="button" class="button-primary" value="<?php _e( 'Remove duplicate rows', 'translatepress-multilingual' ); ?>">
                </a>
                <?php } ?>
        </div>
    </div>

</div>