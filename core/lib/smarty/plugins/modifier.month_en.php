<?php
function smarty_modifier_month_en($string) {
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
        1 => 'januare',
        2 => 'februare',
        3 => 'march',
        4 => 'april',
        5 => 'may',
        6 => 'june',
        7 => 'jule',
        8 => 'august',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    );
    return $month[$string];
}
?>
