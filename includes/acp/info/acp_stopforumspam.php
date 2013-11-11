<?php
class acp_stopforumspam_info
{
    function module()
    {
        return array(
            'filename'    => 'acp_stopforumspam',
            'title'        => 'StopForumSpam',
            'version'    => '1.2.0',
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