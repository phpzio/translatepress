<?php

class TRP_Hooks_Loader{
    protected $actions;
    protected $filters;

    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 0 ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    public function remove_hook( $hook ){

        $this->filters = $this->unset_hook_from_array( $this->filters, $hook );
        $this->actions = $this->unset_hook_from_array( $this->actions, $hook );
    }

    private function unset_hook_from_array( $array, $hook ) {
        foreach ( $array as $key => $filter ){
            if ( $filter['hook'] == $hook ){
                unset( $array[$key] );
            }
        }
        return array_values( $array );
    }

    private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        return $hooks;
    }

    public function run() {

        foreach ( $this->filters as $hook ) {
            if ( $hook['component'] == null ){
                add_filter( $hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args'] );
            }else{
                add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
            }
        }

        foreach ( $this->actions as $hook ) {
            if ( $hook['component'] == null ){
                add_action( $hook['hook'], $hook['callback'], $hook['priority'], $hook['accepted_args'] );
            }else {
                add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
            }
        }
    }
}