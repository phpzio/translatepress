<?php

/**
 * Class TRP_Hooks_Loader
 *
 * Buffer class for action and filters
 *
 * Collects all the actions and filters then registers them all at once in WP system.
 */
class TRP_Hooks_Loader{

    protected $actions;
    protected $filters;


    /**
     * TRP_Hooks_Loader constructor.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add action to array.
     *
     * @param string $hook          Action hook.
     * @param string $component     Object containing the method. Leave null for functions.
     * @param string $callback      Method name.
     * @param int $priority         WP priority.
     * @param int $accepted_args    Number of accepted args.
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 0 ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Add filter to array.
     *
     * @param string $hook          Filter hook.
     * @param string $component     Object containing the method. Leave null for functions.
     * @param string $callback      Method name.
     * @param int $priority         WP priority.
     * @param int $accepted_args    Number of accepted args.
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Remove all actions or filters registered functions for this hook.
     *
     * Should be called before plugins_loaded filter priority 15.
     *
     * @param string $hook          Hook name.
     */
    public function remove_hook( $hook ){

        $this->filters = $this->unset_hook_from_array( $this->filters, $hook );
        $this->actions = $this->unset_hook_from_array( $this->actions, $hook );
    }

    /**
     * Remove $hook from action or filter array
     *
     * @param array $array          Action or filters array.
     * @param string $hook          Hook to remove.
     * @return array                Action or filters without the hook.
     */
    private function unset_hook_from_array( $array, $hook ) {
        foreach ( $array as $key => $filter ){
            if ( $filter['hook'] == $hook ){
                unset( $array[$key] );
            }
        }
        return array_values( $array );
    }

    /**
     * Add hook to action or filter arrays.
     *
     * @param array $hooks          Action or filters array.
     * @param string $hook          Hook name.
     * @param string $component     Object name.
     * @param string $callback      Method name.
     * @param int $priority         Priority.
     * @param int $accepted_args    Number of args.
     * @return array                Action or filters array containing the new hook.
     */
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

    /**
     * Registers hooks with WordPress.
     *
     * Hooked on plugins_loaded filter, priority 15
     */
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