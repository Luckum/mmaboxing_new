<?php

class nc_netshop_mailer_rule extends nc_netshop_record_conditional {

    protected $primary_key = "rule_id";
    protected $properties = array(
        'rule_id' => null,
        'catalogue_id' => 0,
        'name' => '',
        'email' => '',
        'condition' => '',
        'enabled' => true,
    );

    protected $table_name = "Netshop_MailRule";
    protected $mapping = array(
        'rule_id' => 'Rule_ID',
        'catalogue_id' => 'Catalogue_ID',
        'name' => 'Name',
        'email' => 'Email',
        'condition' => 'Condition',
        'enabled' => 'Checked',
    );

}