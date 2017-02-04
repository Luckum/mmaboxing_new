<?php

class nc_netshop_mailer_rule_table extends nc_netshop_table {

    protected $record_class = 'nc_netshop_mailer_rule';

    protected $table = 'Netshop_MailRule';

    protected $primary_key = 'Rule_ID';

    protected $fields = array(
        'Rule_ID' => array(
            'field' => 'hidden',
        ),
        'Catalogue_ID' => array(
            'field' => 'hidden',
        ),
        'Name' => array(
            'title' => NETCAT_MODULE_NETSHOP_NAME_FIELD,
            'field' => 'string',
            'required' => true,
        ),
        'Email' => array(
            'title' => NETCAT_MODULE_NETSHOP_MAILER_RULE_ADDRESS,
            'field' => 'string',
            'required' => true,
        ),
        'Condition' => array(
            'title' => NETCAT_MODULE_NETSHOP_CONDITION_FIELD,
            'field' => 'custom',
            'html' => "<div id='nc_netshop_condition_editor'></div>",
            'wrap' => true,
        ),
        'Checked' => array(
            'field' => 'hidden',
            'default' => 1,
        ),

    );

}