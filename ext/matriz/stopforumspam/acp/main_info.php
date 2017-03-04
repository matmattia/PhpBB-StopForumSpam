<?php
namespace matriz\stopforumspam\acp;

class main_info
{
    function module()
    {
        return array(
            'filename'    => '\matriz\stopforumspam\acp\main_module',
            'title'        => 'StopForumSpam',
            'version'    => '2.0.1',
            'modes'        => array(
                'index'        => array('title' => 'StopForumSpam', 'auth' => 'acl_a_', 'cat' => array('')),
            ),
        );
    }

    function install()
    {
    }

    function uninstall()
    {
    }
}