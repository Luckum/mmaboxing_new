<?php

/* $Id: page.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * 
 */
class nc_search_area_page extends nc_search_area_sub {

    protected $include_children = false;

    protected function get_id() {
        return null;
    }

    protected function get_path() {
        if (!$this->path) {
            $this->path = (strpos($this->url, "://") ? parse_url($this->url, PHP_URL_PATH) : $this->url);
        }
        return $this->path;
    }

    public function to_string() {
        return $this->get_path();
    }

    public function get_field_condition() {
        return '""';
    }

    public function get_description() {
        $urls = $this->get_urls();
        return sprintf(NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_PAGE, $urls[0], $this->get_path());
    }

}
