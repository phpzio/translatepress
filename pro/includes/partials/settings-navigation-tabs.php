<h3 class="nav-tab-wrapper">
        <?php
        foreach( $tabs as $tab ) {
            echo '<a href="' . $tab['url'] . '" class="nav-tab ' . ( ( $active_tab == $tab['page'] ) ? 'nav-tab-active' : '' ) . '">' . $tab['name'] . '</a>';
        }
        ?>
</h3>
