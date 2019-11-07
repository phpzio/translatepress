<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    do_action( 'trp_string_translation_editor_head' );
    ?>
    <title>TranslatePress - <?php _e('String Translation Editor', 'translatepress-multilingual'); ?> </title>
</head>
<body>

    <div id="trp-editor-container">

    </div>

    <?php do_action( 'trp_string_translation_editor_footer' ); ?>
</body>
</html>

<?php
