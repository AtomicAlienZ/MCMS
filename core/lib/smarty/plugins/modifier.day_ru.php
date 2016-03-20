<?php
function smarty_modifier_day_ru($string) {
    $day = array(
        0 => 'воскресенье',
        1 => 'понедельник',
        2 => 'вторник',
        3 => 'среда',
        4 => 'четверг',
        5 => 'пятница',
        6 => 'суббота',
    );
    return $day[$string];
}
?>
