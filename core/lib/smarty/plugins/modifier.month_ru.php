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
        1 => '€нвар€',
        2 => 'феврал€',
        3 => 'марта',
        4 => 'апрел€',
        5 => 'ма€',
        6 => 'июн€',
        7 => 'июл€',
        8 => 'августа',
        9 => 'сент€бр€',
        10 => 'окт€бр€',
        11 => 'но€бр€',
        12 => 'декабр€',
    );
    return $month[$string];
}
?>
