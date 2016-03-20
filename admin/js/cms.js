function row_ovr(row) {
    row.className = 'ovr';
}

function row_out(row) {
    row.className = '';
}

function row_click(num) {
    cb = document.getElementById('cb'+num);
    cb.checked = cb.checked ? false : true;
}

function cm_go(menu_id) {
    box = document.getElementById(menu_id);
    url = box.options[box.selectedIndex].value;
    if (url) location.href = url;
}


function switch_listbox(id, state) {
    if (state) {
        document.getElementById(id+'_input_div').style.display = 'none';
        document.getElementById(id+'_select_div').style.display = 'block';
    } else {
        document.getElementById(id+'_input_div').style.display = 'block';
        document.getElementById(id+'_select_div').style.display = 'none';
    }
}

function switch_all(cb_all) {
    i = 1;
    while (cb = document.getElementById(cb_all.id+i)) {
        cb.checked = cb_all.checked;
        i++;
    }
}










function remove(){
}

function add_class(e,c) {
  e.className=e.className+" "+c;
}

function remove_class(e,c) {
  cn=e.className;
  p=cn.indexOf(c);
  if (p>-1){e.className=cn.substr(0,p)+cn.substr(p+c.length);  }
}

function expand_node(sid){
	var cookie_sep = '-';
	collapsed = GetCookie('pm_collapsed');
	if (collapsed == null) {
		collapsed = '';
	}
	expanded  = GetCookie('pm_expanded');
	if (expanded == null) {
		expanded = '';
	}
	node_marker = cookie_sep+sid+cookie_sep;
	if (collapsed.indexOf(node_marker)>-1) {	
		collapsed1 = collapsed.substring(0,collapsed.indexOf(node_marker));
		collapsed2 = collapsed.substring(collapsed.indexOf(node_marker)+node_marker.length, collapsed.length);
		collapsed = collapsed1+cookie_sep+collapsed2;
		SetCookie('pm_collapsed', collapsed, 24*7);
	} 
	if (expanded.indexOf(node_marker)==-1) {
		expanded = expanded + node_marker;
	}
	SetCookie('pm_expanded', expanded, 24*7);
}

function collapse_node(sid){
	var cookie_sep = '-';
	collapsed = GetCookie('pm_collapsed');
	if (collapsed == null) {
		collapsed = '';
	}
	expanded  = GetCookie('pm_expanded');
	if (expanded == null) {
		expanded = '';
	}
	node_marker = cookie_sep+sid+cookie_sep;
	if (expanded.indexOf(node_marker)>-1) {	
		expanded1 = expanded.substring(0,expanded.indexOf(node_marker));
		expanded2 = expanded.substring(expanded.indexOf(node_marker)+node_marker.length, expanded.length);
		expanded = expanded1+cookie_sep+expanded2;
		SetCookie('pm_expanded', expanded, 24*7);
	} 
	if (collapsed.indexOf(node_marker)==-1) {
		collapsed = collapsed + node_marker;
	}
	SetCookie('pm_collapsed', collapsed, 24*7);
}

function switch_node(sid){
	row_id = 'r'+(keys[sid]["left"]+1);
//	alert(row_id);
	row = document.getElementById(row_id);
	marker_show_src = '/admin/img/minus.gif';
	marker_hide_src = '/admin/img/plus.gif';
	if (row) {
		p=row.className.indexOf('hide');
		if (p>-1) {
			expand_node(sid);
			new_class = 'show';
			old_class = 'hide';
			new_marker = marker_show_src;
		}
		else {	  
			collapse_node(sid);	
			new_class = 'hide';
			old_class = 'show';
			new_marker = marker_hide_src;
		}
	}
	else {
		return false;
	}
	for (var i = keys[sid]["left"]+1; i <= keys[sid]["right"]-1; i++) {
		row_id = "r"+i;
		row=document.getElementById(row_id);
		if (row) {
			remove_class(row, old_class);
			p=row.className.indexOf(old_class);
			if (p==-1){
				add_class(row, new_class);
			}
		}
		next_row=document.getElementById('r'+(i+1));
		if (next_row) {
			p=next_row.className.indexOf(old_class);
			if (p==-1){
				marker=document.getElementById('mk'+i);
				if (marker) {
//					marker.src = new_marker;
				}
			}
		}
	} // end for
	marker=document.getElementById('mk'+(keys[sid]["left"]));
	if (marker) {
		marker.src = new_marker;
	}
}

function colorme(cell, color) {
  cell.bgColor=color;
  }

function preview_image(img_src) {
    pic = new Image();
    pic.src = img_src;
    setTimeout('view_image(pic.src, pic.width, pic.height);', 500);
}

function view_image(img_src, img_width, img_height){
    if (img_width>screen.availWidth-150 || img_width==0) {
        win_width = screen.availWidth-150;
    } else {
        win_width = img_width+20;
    }
    if (img_height>screen.availHeight-150 || img_height==0) {
        win_height = screen.availHeight-150;
    } else {
        win_height = img_height+20;
    }

    win_top  = Math.abs((screen.availHeight - win_height)/3);
    win_left = Math.abs((screen.availWidth -win_width)/3);
    photoWindow = window.open('', '', "resizable=yes,top=" + win_top + ',left=' + win_left + ",width="+win_width+',height='+win_height+",status=0,menubar=0,toolbar=0,scrollbars=yes");
    photoWindow.document.write('<!DOCTYPE html>');
    photoWindow.document.write('<html>');
    photoWindow.document.write('<head><title>'+img_src+'</title><style type="text/css"><!-- body {margin:0;padding:0} --></style>');
    photoWindow.document.write('<scr'+'ipt type=text/javascript>');
    photoWindow.document.write('document.onkeypress = function CloseOnEsc(key) { if(document.all) { var keyCode = window.event.keyCode; } else { if (key.which == 0) {window.close();return;}   }  if (keyCode == 27) {window.close();return;} }');
    photoWindow.document.write('</scr'+'ipt>');
    photoWindow.document.write('</head><body><img src="'+img_src+'" ');
    if ( (img_width>1) && (img_height>1) ) {
        photoWindow.document.write('width="'+img_width+'" height="'+img_height+'"');
    }
    photoWindow.document.write(' border=0 onclick="javascript:window.close();">');
    photoWindow.document.write('</body></html>');
    photoWindow.document.bgColor="#f0f0f0";
    photoWindow.document.close()
}

function setCheckboxes(the_form, field_name, do_check)
{
    var elts = document.forms[the_form].elements[field_name];
    var elts_cnt  = (typeof(elts.length) != 'undefined')
                  ? elts.length
                  : 0;

    if (elts_cnt) {
        for (var i = 0; i < elts_cnt; i++) {
            elts[i].checked = do_check;
        } // end for
    } else {
        elts.checked        = do_check;
    } // end if... else

    return true;
}

function SetCookie(cookieName, cookieValue, nHours) {
 var today = new Date();
 var expire = new Date();
 if (nHours==null || nHours==0) nHours=1;
 expire.setTime(today.getTime() + 3600000*nHours);
 document.cookie = cookieName+"="+escape(cookieValue) + ";expires="+expire.toGMTString();
}

function GetCookie(name){
    var dc = document.cookie;
    var prefix = name + "=";
    var begin = dc.indexOf("; " + prefix);
    if (begin == -1)
    {
        begin = dc.indexOf(prefix);
        if (begin != 0) return null;
    }
    else
    {
        begin += 2;
    }
    var end = document.cookie.indexOf(";", begin);
    if (end == -1)
    {
        end = dc.length;
    }
    return unescape(dc.substring(begin + prefix.length, end));
}

function DeleteCookie(name, path, domain){
    if (getCookie(name))
    {
        document.cookie = name + "=" + 
            ((path) ? "; path=" + path : "") +
            ((domain) ? "; domain=" + domain : "") +
            "; expires=Thu, 01-Jan-70 00:00:01 GMT";
    }
}

function switch_admin_lang(lang){
  var expires = new Date ();
  expires.setTime(expires.getTime() + 7*(24*3600*1000));
  document.cookie = 'pm_admin_lang' + "=" + lang + "; expires=" + expires.toGMTString();
  document.location = document.location;
}


function highlight_row(row_id){
	old_class ='inactive_row';
	new_class = 'active_row';
    row=document.getElementById(row_id);
    if (row) {
      remove_class(row, old_class);
      add_class(row, new_class);
    }
}

function dehighlight_row(row_id){
	old_class ='active_row';
	new_class = 'inactive_row';
    row=document.getElementById(row_id);
    if (row) {
      remove_class(row, old_class);
      add_class(row, new_class);
    }
}

function highlight_menu(row_id){
	old_class ='inactive_menu';
	new_class = 'active_menu';
    row=document.getElementById(row_id);
    if (row) {
      remove_class(row, old_class);
      add_class(row, new_class);
    }
}

function dehighlight_menu(row_id){
	old_class ='active_menu';
	new_class = 'inactive_menu';
    row=document.getElementById(row_id);
    if (row) {
      remove_class(row, old_class);
      add_class(row, new_class);
    }
}


/*
function cm_show(menu_id){
	cm_hide(prev_menu_id);
	prev_menu_id = menu_id;
	old_class ='cm_hide';
	new_class = 'cm_show';
    obj=document.getElementById(menu_id);
    if (obj) {
      remove_class(obj, old_class);
      add_class(obj, new_class);
    }
}

function cm_hide(menu_id){
	old_class ='cm_show';
	new_class = 'cm_hide';
    obj=document.getElementById(menu_id);
    if (obj) {
      remove_class(obj, old_class);
      add_class(obj, new_class);
    }
}
*/
prev_menu_id = 0;