<div class="form">
	<div class="main_ttl"><h1>�������������� ������</h1></div><div class="clr">&nbsp;</div>
	{if $output.error == -1}
		<p>�� ��������� ���� e-mail ������ ����� � ������ ��� �����������.</p>
	{else}
		{if $output.error == 1}
			<div class="error">��������� ���� E-mail</div>
		{/if}
		{if $output.error == 2}
			<div class="error">���� E-mail ��������� �������</div>
		{/if}
		{if $output.error == 3}
			<div class="error">��� ������������ � ����� E-mail</div>
		{/if}
		{if $output.error == 4}
			<div class="error">�� ������� ����� �����</div>
		{/if}
		<form method="post" enctype="multipart/form-data" name="recover">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td class="field-name">��� E-mail:</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="text" class="text" name="send[email]">
						</div></div></div>
					</td>
				</tr>
				<tr>
					<td class="field-name">
						<a name="protect_code"></a><a href="#protect_code" onclick="d = new Date(); document.getElementById('antispam').src = '{$output.protect_img}?r=' + d.getTime(); return false"><img src="{$output.protect_img}" id="antispam" alt="�������� ���" title="��������" align="absmiddle"></a>
					</td>
					<td class="form-text">
						<div class="fieldC"><div class="fieldL"><div class="fieldR">
							<input type="Text"  class="text code" name="send[code]" maxlength="4" value="">
						</div></div></div>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<div class="btnC"><div class="btnL"><div class="btnR">
							<a href="#" onclick="document.forms.recover.submit(); return false">���������</a>
						</div></div></div>
					</td>
				</tr>
			</table>
		</form>
	{/if}
</div>
