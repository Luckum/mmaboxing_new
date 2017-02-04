<?php

/* $Id: nc_event.class.php 5960 2012-01-17 17:25:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Event extends nc_System {

    private $_binded_obj, $_events_arr;
    private $_events_name;

    public function __construct() {
        // load parent constructor
        parent::__construct();

        // collect objects for events
        $this->_binded_obj = array();

        // allowed events
        $events_arr = array(
                // add actions
                "addCatalogue",
                "addSubdivision",
                "addSubClass",
                "addClass",
                "addClassTemplate",
                "addMessage",
                "addSystemTable", # unused
                "addTemplate",
                "addUser",
                "addComment",
                // update actions
                "updateCatalogue",
                "updateSubdivision",
                "updateSubClass",
                "updateClass",
                "updateClassTemplate",
                "updateMessage",
                "updateSystemTable",
                "updateTemplate",
                "updateUser",
                "updateComment",
                // drop actions
                "dropCatalogue",
                "dropSubdivision",
                "dropSubClass",
                "dropClass",
                "dropClassTemplate",
                "dropMessage",
                "dropSystemTable", # unused
                "dropTemplate",
                "dropUser",
                "dropComment",
                // check, uncheck actions
                "checkComment",
                "uncheckComment",
                "checkMessage",
                "uncheckMessage",
                "checkUser",
                "uncheckUser",
                "checkSubdivision",
                "uncheckSubdivision",
                "checkCatalogue",
                "uncheckCatalogue",
                "checkSubClass",
                "uncheckSubClass",
                "checkModule",
                "uncheckModule",
                // other
                "authorizeUser",
                // widgets actions
                "addWidgetClass",
                "editWidgetClass",
                "dropWidgetClass",
                "addWidget",
                "editWidget",
                "dropWidget"
        );

        $this->_events_arr = array();
        foreach($events_arr as $event_name) {
            $this->_events_arr[] = $event_name . 'Prep';
            $this->_events_arr[] = $event_name;
        }

        // имена пользовательских событий
        $this->_events_name = array();
    }

    /**
     * Add object to the listen mode
     *
     * @param object examine object
     */
    public function bind(&$object, $event_data) {
        // validate
        if (!(
                is_string($event_data) ||
                is_array($event_data)
                )) return false;

        // remap array
        $events_remap_arr = array();

        // имя метода совпадает с именем события
        if (is_string($event_data)) {
            $event_name = $event_data;
        } else {
            // get parameters
            list($event_name, $event_remap_name) = each($event_data);
            // для одного метода названачены несколько событий ( перечислены через запятую )
            if (strpos($event_name, ',') && ($events = explode(',', $event_name))) {
                foreach ($events as $v)
                    $this->bind($object, array($v => $event_remap_name));
                return true;
            }

            // remap array
            $events_remap_arr = $event_data;
        }

        // already binded
        if (isset($this->_binded_obj[$event_name]) && in_array($object, $this->_binded_obj[$event_name])) {
            return true;
        }

        // bind object with remap array
        $this->_binded_obj[$event_name][] = array('object' => $object, 'remap' => $events_remap_arr);
        //echo get_class($object)." - ".print_r($events_remap_arr, 1)."<br/>";

        return true;
    }

    /**
     * Event processor
     * call objects function for current event
     *
     */
    public function execute() {
        // get function args
        $args = func_get_args();

        // check args
        if (empty($args) || empty($this->_binded_obj)) return false;

        // event name
        $event = array_shift($args);

        // check base system event
        if (!( $event && in_array($event, $this->_events_arr) )) {
            return false;
        }

        // check binded array
        if (empty($this->_binded_obj[$event])) {
            return false;
        }

        //echo "<h2>$event</h2>";
        foreach ($this->_binded_obj[$event] as $object) {
            // check remaping
            if (!empty($object['remap'])) {
                // remap event method
                $event_method = $object['remap'][$event] ? $object['remap'][$event] : "";
            } else {
                // default event name
                $event_method = $event;
            }
            // check and execute observer method
            if (is_callable(array($object['object'], $event_method))) {
                // execute event method
                call_user_func_array(array($object['object'], $event_method), $args);
                //echo "call_user_func_array( array(<b>[".get_class($object['object'])."]</b>, ".$event_method."), ".print_r($args, 1)." );<br/>";
            }
        }

        return;
    }

    /**
     * Get all events as array
     *
     * @return array events list
     */
    public function get_all_events() {
        // return result
        return $this->_events_arr;
    }

    /**
     * Check event by event name
     *
     * @return bool result
     */
    public function check_event($event) {
        // check base system event
        if (in_array($event, $this->_events_arr)) {
            return true;
        }

        // return result
        return false;
    }

    public function register_event($event, $name) {
        // не существует ли событие уже
        if ($this->check_event($event)) {
            return false;
        }

        if (!nc_preg_match("/^[_a-z0-9]+$/i", $event) || !$name) {
            return false;
        }

        $this->_events_arr[] = $event;
        $this->_events_name[$event] = $name;
    }

    /**
     *
     */
    public function event_name($event) {
        // check base system event
        if (!( $event && in_array($event, $this->_events_arr) )) {
            return false;
        }

        // пользовательское имя события
        if (array_key_exists($event, $this->_events_name))
                return $this->_events_name[$event];

        $const = "NETCAT_EVENT_".strtoupper($event);
        return defined($const) ? constant($const) : $const;
    }

}
?>