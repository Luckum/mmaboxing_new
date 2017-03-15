<?php

abstract class nc_netshop_goodslist_base {

    protected $initialized = false; // if session is initialized

    //--------------------------------------------------------------------------

    protected $cookie_id = null; // cookie id in DB

    //--------------------------------------------------------------------------

    protected $user_id = null; // user id in DB

    //--------------------------------------------------------------------------

    protected $nc_core; // nc_Core object

    //--------------------------------------------------------------------------

    protected $db; // db object

    //--------------------------------------------------------------------------

    protected $tablename; // db table name

    //--------------------------------------------------------------------------

    public function __construct(nc_netshop $netshop) {
        /* @todo MIGRATE TO NEW NETSHOP CLASS */
        $this->nc_core = nc_Core::get_object();
        $this->db = $this->nc_core->db;
    }

    //--------------------------------------------------------------------------

    /**
     * Initializes the session id
     *
     * @return bool
     */
    private function initialize() {
        if (!$this->initialized && $this->tablename) {
            global $AUTH_USER_ID;

            $user_id = (int)$AUTH_USER_ID;
            $cookie_id = '';

            if (!$user_id) {
                if (isset($_COOKIE['nc_' . $this->tablename . '_sid'])) {
                    $cookie_id = $_COOKIE['nc_' . $this->tablename . '_sid'];
                } else {
                    $cookie_id = session_id();
                }

                $expire = time() + 2592000;
                setcookie("nc_{$this->tablename}_sid", $cookie_id, $expire, "/");
            } else {
                if (isset($_COOKIE['nc_' . $this->tablename . '_sid'])) {
                    $cookie_escaped = $this->db->escape($_COOKIE['nc_' . $this->tablename . '_sid']);
                    $sql = "UPDATE `{$this->tablename}` SET `User_ID` = {$user_id}, `Cookie_ID` = '' WHERE `Cookie_ID` = '{$cookie_escaped}'";
                    $this->db->query($sql);
                    setcookie("nc_{$this->tablename}_sid", false, time() - 1000);
                }
            }

            $this->user_id = $user_id;
            $this->cookie_id = $cookie_id;

            $this->initialized = true;
            return true;
        }

        return false;
    }

    //--------------------------------------------------------------------------

    /**
     * Adds goods to list
     *
     * @param int $item_id
     * @param int $class_id
     * @return bool
     */
    public function add($item_id, $class_id) {
        $this->initialize();
        if ($this->initialized) {
            $db = $this->db;
            $cookie_id = $db->escape($this->cookie_id);
            $user_id = $this->user_id;
            $item_id = (int)$item_id;
            $class_id = (int)$class_id;

            $sql = "SELECT `Parent_Message_ID` FROM `Message{$class_id}` WHERE `Message_ID` = {$item_id}";
            $parent_message_id = (int)$db->get_var($sql);

            $item_id = $parent_message_id ? $parent_message_id : $item_id;

            $this->remove($item_id, $class_id);

            $sql = "INSERT INTO `{$this->tablename}` (`Cookie_ID`, `User_ID`, `Item_ID`, `Class_ID`, `Added`) VALUES " .
                "('{$cookie_id}', {$user_id}, {$item_id}, {$class_id}, NOW())";
            $db->query($sql);

            return true;
        }

        return false;
    }

    //--------------------------------------------------------------------------

    /**
     * Remove goods from list
     * by class id and item id
     *
     * @param int $item_id
     * @param int $class_id
     * @return bool
     */
    public function remove($item_id, $class_id) {
        $this->initialize();
        if ($this->initialized) {
            $cookie_id = $this->db->escape($this->cookie_id);
            $user_id = $this->user_id;
            $item_id = (int)$item_id;
            $class_id = (int)$class_id;

            $sql = "DELETE FROM `{$this->tablename}` WHERE `Item_ID` = {$item_id} AND `Class_ID` = {$class_id} AND ";
            $sql .= ($user_id ? "`User_ID` = {$user_id}" : "`Cookie_ID` = '{$cookie_id}'");
            $this->db->query($sql);

            return true;
        }

        return false;
    }

    //--------------------------------------------------------------------------

    /**
     * Remove goods from list
     * by index
     *
     * @param int $index
     * @param string $sort
     * @return bool
     */
    public function remove_by_index($index, $sort = 'ASC') {
        $this->initialize();
        if ($this->initialized) {
            $sort = $sort == 'DESC' ? $sort : 'ASC';

            $cookie_id = $this->db->escape($this->cookie_id);
            $user_id = $this->user_id;
            $index = (int)$index;

            $sql = "DELETE FROM `{$this->tablename}` WHERE ";
            $sql .= ($user_id ? "`User_ID` = {$user_id}" : "`Cookie_ID` = '{$cookie_id}'");
            $sql .= " ORDER BY `ID` {$sort} LIMIT {$index}, 1";
            $this->db->query($sql);

            return true;
        }

        return false;
    }

    //--------------------------------------------------------------------------

    /**
     * Clears list
     *
     * @return bool
     */
    public function clear() {
        $this->initialize();
        if ($this->initialized) {
            $cookie_id = $this->db->escape($this->cookie_id);
            $user_id = $this->user_id;

            $sql = "DELETE FROM `{$this->tablename}` WHERE ";
            $sql .= ($user_id ? "`User_ID` = {$user_id}" : "`Cookie_ID` = '{$cookie_id}'");
            $this->db->query($sql);

            return true;
        }

        return false;
    }

    //--------------------------------------------------------------------------

    /**
     * Returns goods list
     *
     * @param string $sort
     * @param int $limit
     * @return array
     */
    public function get_all($sort = 'ASC', $limit = 3, $skip_filter = false) {
        $this->initialize();
        if ($this->initialized) {
            $sort = $sort == 'DESC' ? $sort : 'ASC';
            $cookie_id = $this->db->escape($this->cookie_id);
            $user_id = $this->user_id;
            $limit = (int)$limit;

            $sql = "SELECT `Item_ID`, `Class_ID` FROM `{$this->tablename}`";
            if (!$skip_filter) {
                $sql .= " WHERE ";
                $sql .= ($user_id ? "`User_ID` = {$user_id}" : "`Cookie_ID` = '{$cookie_id}'");
            }
            $sql .= " ORDER BY `ID` {$sort} LIMIT {$limit}";

            return (array)$this->db->get_results($sql, ARRAY_A);
        }

        return array();
    }

    //--------------------------------------------------------------------------

    /**
     * Returns one item from list
     *
     * @param int $index
     * @param string $sort
     * @return null|array
     */
    public function get($index, $sort = 'ASC') {
        $this->initialize();
        if ($this->initialized) {
            $sort = $sort == 'DESC' ? $sort : 'ASC';
            $cookie_id = $this->db->escape($this->cookie_id);
            $user_id = $this->user_id;
            $index = (int)$index;

            $sql = "SELECT `Item_ID`, `Class_ID` FROM `{$this->tablename}` WHERE ";
            $sql .= ($user_id ? "`User_ID` = {$user_id}" : "`Cookie_ID` = '{$cookie_id}'");
            $sql .= " ORDER BY `ID` {$sort} LIMIT 0, {$index}";

            return $this->db->get_row($sql, ARRAY_A);
        }

        return null;
    }


    /**
     * Toggles item in list
     *
     * @param int $item_id
     * @param int $class_id
     * @return bool
     */
    public function toggle($item_id, $class_id) {
        if ($this->check($item_id, $class_id)) {
            $this->remove($item_id, $class_id);
        } else {
            $this->add($item_id, $class_id);
        }

        return true;
    }

    /**
     * Checks if item exists
     * in list
     *
     * @param int $item_id
     * @param int $class_id
     * @return bool
     */
    public function check($item_id, $class_id) {
        $this->initialize();
        if ($this->initialized) {
            $db = $this->db;
            $cookie_id = $db->escape($this->cookie_id);
            $user_id = $this->user_id;
            $item_id = (int)$item_id;
            $class_id = (int)$class_id;

            $sql = "SELECT `Parent_Message_ID` FROM `Message{$class_id}` WHERE `Message_ID` = {$item_id}";
            $parent_message_id = (int)$db->get_var($sql);

            $item_id = $parent_message_id ? $parent_message_id : $item_id;

            $sql = "SELECT `ID` FROM `{$this->tablename}` WHERE `Item_ID` = {$item_id} AND `Class_ID` = {$class_id} AND ";
            $sql .= ($user_id ? "`User_ID` = {$user_id}" : "`Cookie_ID` = '{$cookie_id}'");

            return $db->get_var($sql) ? true : false;
        }
    }
}