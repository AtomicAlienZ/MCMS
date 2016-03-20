<tr class="lev{$level}" id="r{$left_key}" onmouseover="javascript:highlight_row(this.id);" onmouseout="javascript:dehighlight_row(this.id);">
  <td>

    <table cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td style="padding: 0;">{$depth_marker}</td>
      <td style="padding: 0;">{$structure_icon}</td>
      <td style="padding: 0 3px 0 0;">{$modify_link}</td>
      <td style="padding: 0;">{$add_sub_link}</td>
    </tr>
    </table>
    <div><img src="/admin/img/px.gif" width="1" height="1" alt=""></div>

  </td>
  <td>{$up}<img src="/admin/img/px.gif" width="2" height="1" alt="">{$down}</td>
  <td>{$template}<br><img src="/admin/img/px.gif" width="1" height="1" alt=""></td>
  <td>{$actions_group}<br><img src="/admin/img/px.gif" width="1" height="1" alt=""></td>
  <td>{$preview}<br><img src="/admin/img/px.gif" width="1" height="1" alt=""></td>
  <td>{$switch}<br><img src="/admin/img/px.gif" width="1" height="1" alt=""></td>
  <td align="center"><input type="checkbox" name="s_id[]" value={$s_id}><br><img src="/admin/img/px.gif" width="1" height="1" alt=""></td>
</tr>