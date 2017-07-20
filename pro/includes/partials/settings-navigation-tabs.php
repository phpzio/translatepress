<h3 class="nav-tab-wrapper">
        <?php
        foreach( $tabs as $tab ) {
            echo '<a href="' . add_query_arg( 'nav_tab', $tab['nav_tab'], $tab['url'] ) . '" class="nav-tab ' . ( ( $active_tab == $tab['nav_tab'] ) ? 'nav-tab-active' : '' ) . '">' . $tab['name'] . '</a>';
        }
        ?>
</h3>
