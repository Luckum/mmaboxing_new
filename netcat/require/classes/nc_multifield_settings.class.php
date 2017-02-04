<?php

class nc_multifield_settings {

    private $multifield = null;
    private $path = null;
    private $use_name = false;
    private $custom_name = null;
    private $preview_width = 0;
    private $preview_height = 0;
    private $preview_mode = 0;
    private $resize_width = 0;
    private $resize_height = 0;
    private $resize_mode = 0;
    private $crop_x0 = 0;
    private $crop_y0 = 0;
    private $crop_x1 = 0;
    private $crop_y1 = 0;
    private $crop_ignore_width = 0;
    private $crop_ignore_height = 0;
    private $min = 0;
    private $max = 0;

    public function __construct(nc_multifield $multifield) {
        $this->multifield = $multifield;
    }

    public function path($path) {
        $this->path = nc_standardize_path_to_folder($path);
        return $this;
    }

    public function use_name($custom_name = null) {
        $this->use_name = true;
        $this->custom_name = $custom_name;
        return $this;
    }

    public function resize($width, $height, $mode = 0) {
        $this->resize_width = +$width;
        $this->resize_height = +$height;
        $this->resize_mode = +$mode;
        return $this;
    }

    public function preview($width, $height, $mode = 0) {
        $this->preview_width = +$width;
        $this->preview_height = +$height;
        $this->preview_mode = +$mode;
        return $this;
    }

    public function crop($x0, $y0, $x1, $y1) {
        $this->crop_x0 = +$x0;
        $this->crop_y0 = +$y0;
        $this->crop_x1 = +$x1;
        $this->crop_y1 = +$y1;
        return $this;
    }

    public function crop_ignore($width, $height) {
        $this->crop_ignore_width = +$width;
        $this->crop_ignore_height = +$height;
        return $this;
    }

    public function min($min) {
        $this->min = +$min < 0 ? 0 : +$min;
        return $this;
    }

    public function max($max) {
        $this->max = +$max > $this->min ? +$max : 0;
        return $this;
    }

    public function get_setting_hash() {
        $str_hash = '';
        $str_hash .= $this->use_name;
        $str_hash .= $this->path;
        $str_hash .= +$this->preview_width;
        $str_hash .= +$this->preview_height;
        $str_hash .= +$this->preview_mode;
        $str_hash .= +$this->resize_width;
        $str_hash .= +$this->resize_height;
        $str_hash .= +$this->resize_mode;
        $str_hash .= +$this->min;
        $str_hash .= +$this->max;
        $str_hash .= nc_Core::get_object()->get_settings('SecretKey');
        return md5($str_hash);
    }

    public function __toString() {
        return '';
    }

    public function __get($name) {
        return isset($this->$name) ? $this->$name : false;
    }

}