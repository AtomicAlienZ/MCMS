<?php
function smarty_modifier_month_ru($string) {
    if ($string == '01') $string = 1;
    if ($string == '02') $string = 2;
    if ($string == '03') $string = 3;
    if ($string == '04') $string = 4;
    if ($string == '05') $string = 5;
    if ($string == '06') $string = 6;
    if ($string == '07') $string = 7;
    if ($string == '08') $string = 8;
    if ($string == '09') $string = 9;
    $month = array(
        1 => '������',
        2 => '�������',
        3 => '�����',
        4 => '������',
        5 => '���',
        6 => '����',
        7 => '����',
        8 => '�������',
        9 => '��������',
        10 => '�������',
        11 => '������',
        12 => '�������',
    );
    return $month[$string];
}
?>
