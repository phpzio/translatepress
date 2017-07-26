<h3 class="nav-tab-wrapper">
        <?php
        foreach( $tabs as $tab ) {
            echo '<a href="' . $tab['url'] . '" '. ( $tab['page'] == 'trp_translation_editor' ? 'target="_blank"' : '' ) .' class="nav-tab ' . ( ( $active_tab == $tab['page'] ) ? 'nav-tab-active' : '' ) . ( ( $tab['page'] == 'trp_translation_editor' ) ? 'trp-translation-editor' : '' ) . '">' . $tab['name'] . '</a>';
        }
        ?>
</h3>
