<?php

defined('ABSPATH') || exit;

//ajouter role et capabilities
if (empty(get_role('ritesdefemmes'))) {
    add_action('init', 'add_custom_roles');
}
function add_custom_roles()
{
    $newrole = add_role('ritesdefemmes', 'ritesdefemmes', array(
        'ritesdefemmes' => true,
    ));
    if (null !== $newrole) {
        $role->add_cap('ritesdefemmes', true);
    }
}
