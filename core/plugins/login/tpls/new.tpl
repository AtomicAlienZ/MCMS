{if $lang eq 'ru'}
    {assign var='l_enter_reg' value="Вход и регистрация"}
    {assign var='l_welcome' value="Добро пожаловать, "}
    {assign var='l_quit' value="Выйти"}
    {assign var='l_enter' value="Войти"}
    {assign var='l_login' value="Логин"}
    {assign var='l_password' value="Пароль"}
    {assign var='l_forgot' value="Забыли пароль?"}
    {assign var='l_register' value="Зарегистрироваться"}
{elseif $lang eq 'en'}
    {assign var='l_login' value="Username"}
    {assign var='l_password' value="Password"}
{/if}

<noindex>

    <div class="b-main-menu b-main-menu__fr f-ib m-small-show m-med-show">
        {if $output.user_data.uid >0}
            <a href="?logout=1" class="b-link">{$l_quit}</a>
        {else}
            <a href="/registration/" class="b-main-menu-item b-main-menu-item__white">{$l_enter_reg}</a>
        {/if}
    </div>

    <div class="b-login m-med-hide f-ib">
        {if $output.user_data.uid >0}
            <div class="b-login-hello">
                {$l_welcome}<a class="b-link b-link__alter" href="/registration/">{$output.user_data.name}</a>
            </div>

            <div class="b-login-additional">
                <a href="?logout=1" class="b-link b-link__alter">{$l_quit}</a>
            </div>
        {else}
            <form method="post">
                <input type="text" name="form[login]" placeholder="{$l_login}" class="b-input f-ib">
                <input type="password" name="form[pass]" placeholder="{$l_password}" class="b-input f-ib">
                <button type="submit" class="b-button f-ib"><div class="b-button-inner"><span class="b-button-label">{$l_enter}</span></div></button>
            </form>
            <div class="b-login-additional">
                <a href="#" class="b-link">{$l_forgot}</a>
                <a href="#" class="b-link">{$l_register}</a>
            </div>
        {/if}
    </div>

</noindex>