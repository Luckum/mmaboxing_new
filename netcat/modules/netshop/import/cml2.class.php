<?php

class cml2 {
    /* var $db,
      $filedir,
      $filename,
      $filepath; */

    function __construct() {
        $this->exclude_groups = array();
    }

    function get_groups($filepath) {
        // file existence
        if (!file_exists($filepath)) return false;

        $handle = @fopen($filepath, "r");

        if (!$handle) return false;

        $section_groups = false;
        $buffer = "";
        $result = "";

        $level = 0;

        while (!( $buffer == "</Группы>" && $level == 0 || feof($handle) )) {
            // read file line
            $buffer = trim(fgets($handle, 4096));

            if ($buffer == "<Группы>") {
                $section_groups = true;
                $level++;
            }

            if ($buffer == "</Группы>") {
                $level--;
            }

            if (!$section_groups) continue;

            $result.= $buffer;
        }

        $arrXML = array();

        if ($buffer == "</Группы>" && $level == 0 || feof($handle)) {
            // parse XML to array
            $arrXML = $this->xml2array($result);
        }

        // close file descriptor
        fclose($handle);

        return (!empty($arrXML) ? $this->groups_to_flat($arrXML) : false );
    }

    function get_classifier_properties($filepath) {
        // file existence
        if (!file_exists($filepath)) return false;

        $handle = @fopen($filepath, "r");

        if (!$handle) return false;

        $classifier_properties = false;
        $buffer = "";
        $result = "";

        $level = 0;

        while (!( $buffer == "</Свойства>" && $level == 0 || feof($handle) )) {
            // read file line
            $buffer = trim(fgets($handle, 4096));

            if ($buffer == "<Свойства>") {
                $classifier_properties = true;
                $level++;
            }

            if ($buffer == "</Свойства>") {
                $level--;
            }

            if (!$classifier_properties) continue;

            $result.= $buffer;
        }

        $arrXML = array();
        $properties = array();

        if ($buffer == "</Свойства>" && $level == 0 || feof($handle)) {
            // parse XML to array
            $arrXML = $this->xml2array($result);

            if (isset($arrXML['Свойства'])) {
                foreach(array('Свойство', 'СвойствоНоменклатуры') as $propertyType) {
                    if (isset($arrXML['Свойства'][$propertyType])) {
                        $tmpProperties = $arrXML['Свойства'][$propertyType];
                        if (!isset($tmpProperties[0])) {
                            $tmpProperties = array($tmpProperties);
                        }

                        foreach($tmpProperties as $property) {
                            $values = array();
                            if (isset($property['ТипЗначений']) &&
                                $property['ТипЗначений'] == 'Справочник' &&
                                isset($property['ВариантыЗначений'])
                            ) {
                                $tmpValues = $property['ВариантыЗначений']['Справочник'];
                                if (!isset($tmpValues[0])) {
                                    $tmpValues = array($tmpValues);
                                }

                                foreach($tmpValues as $value) {
                                    $values[$value['ИдЗначения']] = $value['Значение'];
                                }
                            } else if (isset($property['ТипыЗначений']) &&
                                isset($property['ТипыЗначений']['ТипЗначений']) &&
                                isset($property['ТипыЗначений']['ТипЗначений']['Тип']) &&
                                $property['ТипыЗначений']['ТипЗначений']['Тип'] == 'Справочники' &&
                                isset($property['ТипыЗначений']['ТипЗначений']['ВариантыЗначений'])
                            ) {
                                $tmpValues = $property['ТипыЗначений']['ТипЗначений']['ВариантыЗначений']['ВариантЗначения'];
                                if (!isset($tmpValues[0])) {
                                    $tmpValues = array($tmpValues);
                                }

                                foreach($tmpValues as $value) {
                                    $values[$value['Ид']] = $value['Значение'];
                                }
                            }

                            $properties[$property['Ид']] = array(
                                'name' => $property['Наименование'],
                                'values' => $values,
                            );
                        }
                    }
                }
            }
        }

        // close file descriptor
        fclose($handle);

        return (!empty($properties) ? $properties : false );
    }

    function get_classifier($filepath) {
        // file existence
        if (!file_exists($filepath)) return false;

        // open file descriptor
        $handle = @fopen($filepath, "r");

        // can't open file
        if (!$handle) return false;

        // buffer string from file
        $buffer = "";
        // checkout result
        $result = array();

        $buffer_str = "";
        // search classifier section and process
        while (!( $buffer == "</Классификатор>" || feof($handle) )) {
            // read file line
            $buffer = trim(fgets($handle, 4096));

            // classifier section found
            if ($buffer == "<Классификатор>") {
                // set flag
                $section_classifier = true;
                // skip once
                continue;
            }

            // classifier section not found yet
            if (!$section_classifier) continue;

            // combine section
            $buffer_str.= $buffer;
        }

        // for the parser
        $buffer_str = "<Классификатор>".$buffer_str."</Классификатор>";
        // parse section
        $result = $this->xml2array($buffer_str);

        return $result['Классификатор'];
    }

    function get_catalog_properties($filepath) {
        // file existence
        if (!file_exists($filepath)) return false;

        // open file descriptor
        $handle = @fopen($filepath, "r");

        // can't open file
        if (!$handle) return false;

        // buffer string from file
        $buffer = "";
        // checkout result
        $result = array();

        $catalog_only_updates = NULL;

        $buffer_str = "";
        // search classifier section and process
        while (!( $buffer == "<Товары>" || $buffer == "</Каталог>" || feof($handle) )) {
            // read file line
            $buffer = trim(fgets($handle, 4096));
            // classifier section found
            if (preg_match('/^<Каталог.*?(?:СодержитТолькоИзменения=\"(true|false)\")?.*?>$/is', $buffer, $matches)) {
                // set flag
                $catalog_only_updates = intval($matches);
                // skip once
                continue;
            }

            // classifier section not found yet
            if (!$catalog_only_updates) continue;

            // combine section
            $buffer_str.= $buffer;
        }
        // for the parser
        $buffer_str = "<Каталог>".$buffer_str."</Каталог>";
        // parse section
        $result = $this->xml2array($buffer_str);

        return $result['Каталог'];
    }

    function get_offers_properties($filepath) {
        // file existence
        if (!file_exists($filepath)) return false;

        // open file descriptor
        $handle = @fopen($filepath, "r");

        // can't open file
        if (!$handle) return false;

        // buffer string from file
        $buffer = "";
        // checkout result
        $result = array();

        $offers_only_updates = NULL;

        $buffer_str = "";
        // search classifier section and process
        while (!( $buffer == "<Предложения>" || $buffer == "</ПакетПредложений>" || feof($handle) )) {
            // read file line
            $buffer = trim(fgets($handle, 4096));

            // classifier section found
            if (preg_match('/^<ПакетПредложений.*?(?:СодержитТолькоИзменения=\"(true|false)\")?.*?>$/is', $buffer, $matches)) {
                // set flag
                $offers_only_updates = intval($matches);
                // skip once
                continue;
            }

            // classifier section not found yet
            if (!$offers_only_updates) continue;

            // combine section
            $buffer_str.= $buffer;
        }

        // for the parser
        $buffer_str = "<ПакетПредложений>".$buffer_str."</ПакетПредложений>";
        // parse section
        $result = $this->xml2array($buffer_str);

        return $result['ПакетПредложений'];
    }

    function get_catalog($filepath, $obj, $obj_method) {
        // file existence
        if (!file_exists($filepath)) return false;

        // open file descriptor
        $handle = @fopen($filepath, "r");

        // can't open file
        if (!$handle) return false;

        // goods section begins
        $section_goods = false;
        // total goods
        $i_goods = 0;
        // buffer string from file
        $buffer = "";
        // checkout result
        $result = array();
        // duplicate
        $duplicate = array();

        // read LIMIT per transaction
        $fread_limit = 250;

        $catalog_id = 0;
        $catalog_only_updates = NULL;
        while (is_null($catalog_only_updates) && !feof($handle)) {
            // read file line
            $buffer = trim(fgets($handle, 4096));

            if (preg_match('/^<Каталог.*?(?:СодержитТолькоИзменения=\"(true|false)\")?.*?>$/is', $buffer, $matches)) {
                // get BOOL value (0 or 1), not NULL
                $catalog_only_updates = intval($matches[1]);
            }
            else continue;
        }

        $buffer_str = "";
        // search goods section and process
        while (!( $buffer == "</Товары>" || feof($handle) )) {
            // read file line
            $buffer = trim(fgets($handle, 4096));

            // goods section found
            if ($buffer == "<Товары>") {
                // goods section begins now
                $section_goods = true;

                // for the parser
                $buffer_str = "<Каталог>".$buffer_str."</Каталог>";
                // parse undergood section
                $result = $this->xml2array($buffer_str);
                // nulled result
                $buffer_str = "";
            }

            // goods sections not found yet
            if (!$section_goods) {
                // combine undergood section
                $buffer_str.= $buffer;
                // skip
                continue;
            }

            // one good section
            if ($buffer == "<Товар>") {
                // combine good section
                $buffer_str.= $buffer;
                // ...
                while ($buffer != "</Товар>") {
                    $buffer = trim(fgets($handle, 4096));
                    $buffer_str.= $buffer;
                }
                // increment iterator
                $i_goods++;
            }

            // transaction LIMIT process
            if ($i_goods > 0 && ( ($i_goods % $fread_limit) == 0 || ( $buffer == "</Товары>" || feof($handle) ) )) {

                // close file descriptor
                if ($buffer == "</Товары>") fclose($handle);

                // for the parser
                $buffer_str = "<Товары>".$buffer_str."</Товары>";
                // parse XML to array
                $arrXML = $this->xml2array($buffer_str);
                // nulled result
                $buffer_str = "";

                if (!isset($arrXML['Товары']['Товар'][0])) {
                    $arrXML['Товары']['Товар'] = array($arrXML['Товары']['Товар']);
                }

                $obj->$obj_method($i_goods, $arrXML['Товары']['Товар']);
            }
        }

        return $result['Каталог'];
    }

    function get_offers($filepath, $obj, $obj_method) {
        // file existence
        if (!file_exists($filepath)) return false;

        // open file descriptor
        $handle = @fopen($filepath, "r");

        // can't open file
        if (!$handle) return false;

        // offers section begins
        $section_offers = false;
        // total offers
        $i_offers = 0;
        // buffer string from file
        $buffer = "";
        // checkout result
        $result = array();

        // read LIMIT per transaction
        $fread_limit = 250;

        $offers_only_updates = NULL;
        while (is_null($offers_only_updates) && !feof($handle)) {
            // read file line
            $buffer = trim(fgets($handle, 4096));

            if (preg_match('/^<ПакетПредложений.*?(?:СодержитТолькоИзменения=\"(true|false)\")?.*?>$/is', $buffer, $matches)) {
                // get BOOL value (0 or 1), not NULL
                $offers_only_updates = intval($matches[1]);
            }
            else continue;
        }

        $buffer_str = "";
        // search offers section and process
        while (!( $buffer == "</Предложения>" || feof($handle) )) {
            // read file line
            $buffer = trim(fgets($handle, 4096));
            // offers section found
            if ($buffer == "<Предложения>") {
                // goods section begins now
                $section_offers = true;
                // for the parser
                $buffer_str = "<ПакетПредложений>".$buffer_str."</ПакетПредложений>";
                // parse underoffer section
                $result = $this->xml2array($buffer_str);
                // nulled result
                $buffer_str = "";
            }

            // offers sections not found yet
            if (!$section_offers) {
                // combine undergood section
                $buffer_str.= $buffer;
                // skip
                continue;
            }

            // one offer section
            if ($buffer == "<Предложение>") {
                // combine offer section
                $buffer_str.= $buffer;
                // ...
                while ($buffer != "</Предложение>") {
                    $buffer = trim(fgets($handle, 4096));
                    $buffer_str.= $buffer;
                }
                // increment iterator
                $i_offers++;
            }

            // transaction LIMIT process
            if ($i_offers > 0 && ( ($i_offers % $fread_limit) == 0 || ( $buffer == "</Предложения>" || feof($handle) ) )) {

                // close file descriptor
                if ($buffer == "</Предложения>") fclose($handle);

                // for the parser
                $buffer_str = "<Предложения>".$buffer_str."</Предложения>";
                // parse XML to array
                $arrXML = $this->xml2array($buffer_str);
                // nulled result
                $buffer_str = "";

                if (!isset($arrXML['Предложения']['Предложение'][0])) {
                    $arrXML['Предложения']['Предложение'] = array($arrXML['Предложения']['Предложение']);
                }

                $obj->$obj_method($i_offers, $arrXML['Предложения']['Предложение']);
            }
        }

        return $result['ПакетПредложений'];
    }

    function get_owner($filepath) {
        // file existence
        if (!file_exists($filepath)) return false;

        // open file descriptor
        $handle = @fopen($filepath, "r");

        // can't open file
        if (!$handle) return false;

        while (!feof($handle)) {
            $buffer = trim(fgets($handle, 4096));

            if (preg_match('/^<(Каталог|ПакетПредложений).*?(?:СодержитТолькоИзменения=\"(true|false)\")?.*?>$/is', $buffer)) {
                break;
            }
        }

        $buffer_str = "";
        while (!( $buffer == "</Владелец>" || feof($handle) )) {
            // read file line
            $buffer = trim(fgets($handle, 4096));

            // one offer section
            if ($buffer == "<Владелец>") {
                while ($buffer != "</Владелец>") {
                    $buffer = trim(fgets($handle, 4096));
                    $buffer_str.= $buffer;
                }
                break;
            }
        }

        $buffer_str = "<Владелец>".$buffer_str."</Владелец>";
        // parse XML to array
        $arrXML = $this->xml2array($buffer_str);

        return $arrXML['Владелец'];
    }

    function get_orders($filepath, $obj, $obj_method) {
        // file existence
        if (!file_exists($filepath)) return false;

        // open file descriptor
        $handle = @fopen($filepath, "r");

        // can't open file
        if (!$handle) return false;

        // offers section begins
        $section_offers = false;
        // total offers
        $i_orders = 0;
        // buffer string from file
        $buffer = "";
        // checkout result
        $result = array();

        // read LIMIT per transaction
        $fread_limit = 100;

        $first_string = fgets($handle);
        nc_preg_match("/<\?xml\s+.*?encoding=\"([\w\d-]+)\".*?\?>/is", $first_string, $matches);
        $xml_charset = isset($matches[1]) ? strtolower($matches[1]) : 'utf-8';

        $utf_converter = nc_Core::get_object()->utf8;
        $convert_to_utf = $xml_charset != 'utf-8' && $xml_charset != 'utf8';


        $buffer_str = "";
        // search offers section and process
        while (!( $buffer == "</КоммерческаяИнформация>" || feof($handle) )) {

            $buffer = trim(fgets($handle, 4096));

            if ($convert_to_utf) {
                $buffer = $utf_converter->win2utf($buffer);
            }

            if ($buffer == "<Документ>") {
                $buffer_str.= $buffer;

                while ($buffer != "</Документ>") {
                    $buffer = trim(fgets($handle, 4096));
                    if ($convert_to_utf) {
                        $buffer = $utf_converter->win2utf($buffer);
                    }
                    $buffer_str.= $buffer;
                }
                $i_orders++;
            }

            // transaction LIMIT process
            if ($i_orders > 0 && ( ($i_orders % $fread_limit) == 0 || ( $buffer == "</КоммерческаяИнформация>" || feof($handle) ) )) {

                // close file descriptor
                if ($buffer == "</КоммерческаяИнформация>") fclose($handle);

                if ($buffer_str) {
                    // for the parser
                    $buffer_str = "<Документы>".$buffer_str."</Документы>";

                    // parse XML to array
                    $arrXML = $this->xml2array($buffer_str);

                    // nulled result
                    $buffer_str = "";

                    if (!isset($arrXML['Документы']['Документ'][0])) {
                        $arrXML['Документы']['Документ'] = array($arrXML['Документы']['Документ']);
                    }

                    $obj->$obj_method($arrXML['Документы']['Документ']);
                }
            }
        }

        return true;
    }

    function groups_to_flat($data) {

        if (!is_array($data) || empty($data)) return false;

        static $index = 0;
        static $result = array();
        static $parent = array();
        static $level = 0;

        // walking...
        foreach ($data as $key => $value) {
            // if plural tag - call recursive function
            if ($key == "Группы") {
                $this->groups_to_flat($value);
                continue;
            }

            // clearing parent array to current level
            if (!empty($parent)) {
                for ($i = count($parent) - 1; $i >= $level; $i--) {
                    array_pop($parent);
                }
            }

            if ($key == "Группа") {

                if (!isset($value[0])) $value = array($value);

                foreach ($value as $v) {
                    // excluded groups
                    if (in_array($v['Ид'], $this->exclude_groups)) continue;

                    $index++;
                    $result[$index]['parent_gid'] = ( isset($parent[$level - 1]) ? $parent[$level - 1] : 0 );
                    $result[$index]['Ид'] = $v['Ид'];
                    $result[$index]['Наименование'] = $v['Наименование'];
                    $result[$index]['level'] = $level;

                    if (isset($v['Группы']) && !empty($v['Группы'])) {
                        $parent[$level] = $v['Ид'];
                        $level++;
                        $this->groups_to_flat($v['Группы']);
                        continue;
                    }
                }
            }
        }

        $level--;
        return $result;
    }

    function xml2array($contents, $get_attributes = 1, $priority = 'tag') {

        if (!function_exists('xml_parser_create')) {
            return array();
        }

        $parser = xml_parser_create('');

        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if (!$xml_values) return; //Hmm...

            $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
        $current = & $xml_array;
        $repeated_tag_index = array();

        foreach ($xml_values as $data) {
            unset($attributes, $value);
            extract($data);
            $result = array();
            $attributes_data = array();
            if (isset($value)) {
                if ($priority == 'tag') $result = $value;
                else $result['value'] = $value;
            }
            if (isset($attributes) and $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') $attributes_data[$attr] = $val;
                    else
                            $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'

                }
            }
            if ($type == "open") {
                $parent[$level - 1] = & $current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) {
                    $current[$tag] = $result;
                    if ($attributes_data)
                            $current[$tag.'_attr'] = $attributes_data;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    $current = & $current[$tag];
                }
                else {
                    if (isset($current[$tag][0])) {
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {
                        $current[$tag] = array(
                                $current[$tag],
                                $result
                        );
                        $repeated_tag_index[$tag.'_'.$level] = 2;
                        if (isset($current[$tag.'_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level] - 1;
                    $current = & $current[$tag][$last_item_index];
                }
            } elseif ($type == "complete") {
                if (!isset($current[$tag])) {
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if ($priority == 'tag' and $attributes_data)
                            $current[$tag.'_attr'] = $attributes_data;
                }
                else {
                    if (isset($current[$tag][0]) and is_array($current[$tag])) {
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {
                        $current[$tag] = array(
                                $current[$tag],
                                $result
                        );
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag.'_attr'])) {
                                $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                                unset($current[$tag.'_attr']);
                            }
                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag.'_'.$level].'_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                    }
                }
            } elseif ($type == 'close') {
                $current = & $parent[$level - 1];
            }
        }
        return ($xml_array);
    }

}
