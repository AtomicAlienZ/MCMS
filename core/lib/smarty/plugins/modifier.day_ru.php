<?php
function smarty_modifier_day_ru($string) {
    $day = array(
        0 => '�����������',
        1 => '�����������',
        2 => '�������',
        3 => '�����',
        4 => '�������',
        5 => '�������',
        6 => '�������',
    );
    return $day[$string];
}
?>
