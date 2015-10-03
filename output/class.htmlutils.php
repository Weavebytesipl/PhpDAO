<?php
class HTMLUtils {

    public static function generate_select_html($arr, $sel_name, $sel_id, $sel_val="") {
        $ret = "<select name=\"$sel_name\" id=\"$sel_id\">";
        foreach ( $arr as $k => $v ) {
            if ($v == $sel_val) {
                $ret = $ret . "<option value='$k' selected='selected'>$v</option>";
            }
            else {
                $ret = $ret . "<option value='$k'>$v</option>";
            }
        }

        $ret = $ret. "</select>";
        return $ret;
    }
}//HTMLUtils
?>
