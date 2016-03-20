{if $output.user_data.uid >0}

  <p>{if $lang eq 'ru'}Добро пожаловать{elseif $lang eq 'en'}Welcome{/if}, <b>{$output.user_data.name}</b>!</p>

  <p>{if $lang eq 'ru'}Вам доступны следующие разделы{elseif $lang eq 'en'}Now you have an access to the following pages{/if}:</p>
  <ul>
    <li><a href="/{$lang}/forum/">Forum</a></li>
    {section name=cid loop=$output.pages}{/section}
    {if $smarty.section.cid.total > 0}
      {section name=cid loop=$output.pages}
      <li><a href="/{$output.pages[cid].relative_url}/">{$output.pages[cid].title}</a></li>
      {/section}
    {/if}
  </ul>

  <p><a href="/{$lang}/logout/">{if $lang eq 'ru'}Выйти{elseif $lang eq 'en'}Logout{/if}</a></p>

{else}

  {if $output.user.error}<p>{$output.user.error}</p>{/if}

  <form action="{$strucure.url}" method="post" enctype="multipart/form-data">
  <table cellpadding="5" cellspacing="0" border="0">
  <tr>
    <td><label for="login">{if $lang eq 'ru'}Логин{elseif $lang eq 'en'}Login{/if}:</label></td>
    <td><input type="Text" class="text" name="form[login]" id="login" style="width: 156px;"></td>
  </tr>
  <tr>
    <td><label for="passwd">{if $lang eq 'ru'}Пароль{elseif $lang eq 'en'}Password{/if}:</label></td>
    <td><input type="Password" class="text" name="form[pass]" id="passwd" style="width: 156px;"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><input type="submit" class="button" value="Войти"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="/{$lang}/register/">{if $lang eq 'ru'}Регистрация{elseif $lang eq 'en'}Register{/if} <b>&raquo;</b></a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="/{$lang}/register/reg_info/" target="_blank" onClick="popupWin = window.open(this.href, 'reg_info', 'location,width=400,height=300,top=0'); popupWin.focus(); return false;">{if $lang eq 'ru'}Преимущества регистрации{elseif $lang eq 'en'}Registration benefits{/if} <b>&raquo;</b></a></td>
  </tr>
  </table>
  
  </form>

{/if}