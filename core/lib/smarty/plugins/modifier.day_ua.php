<?php
function smarty_modifier_day_ua($string) {
    $day = array(
        0 => '�����',
        1 => '��������',
        2 => '�������',
        3 => '������',
        4 => '������',
        5 => '�&#39;������',
        6 => '������',
    );
    return $day[$string];
}
?>
