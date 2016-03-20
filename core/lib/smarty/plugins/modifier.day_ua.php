<?php
function smarty_modifier_day_ua($string) {
    $day = array(
        0 => 'нед≥л€',
        1 => 'понед≥лок',
        2 => 'в≥второк',
        3 => 'середа',
        4 => 'четвер',
        5 => 'п&#39;€тниц€',
        6 => 'субота',
    );
    return $day[$string];
}
?>
