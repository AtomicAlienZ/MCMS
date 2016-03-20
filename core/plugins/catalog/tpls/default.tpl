{* catalogue *}

{if $output.items}

	{* catalogue folders *}
	<h1 class="b-plugin__title">Название каталога: {$output.title}</h1>

	{* catalogue items *}
	{if $output.type eq 'folder'}


		{foreach from=$output.items item=item key=key}

			<div class="b-plugin__catalogue__items">

				<div class="b-plugin__catalogue__items__item">

					<div class="b-plugin__catalogue__items__item__image">
						<div style="background-image: url({$item.img})" />
					</div>
					<div class="b-plugin__catalogue__items__item__title">
						<a href="{$item.url}">
							{$item.title}
						</a>
					</div>
					<div class="b-plugin__catalogue__items__item__description">{$item.description}</div>
					<div class="b-plugin__catalogue__items__item__price">{$item.price}</div>
					<div class="b-plugin__catalogue__items__item__cart">
						<button class="b-plugin__catalogue__items__item__cart__add js-catalogue__cart__add">Добавить</button>
						<button class="b-plugin__catalogue__items__item__cart__remove">Убрать</button>
					</div>
					<div class="b-plugin__catalogue__items__item__compare">
						<button class="b-plugin__catalogue__items__item__compare__add">Сравнить</button>
					</div>

					<div class="b-plugin__catalogue__items__item__additional">
						{foreach from=$item.additional item=field}
							{if $field.value neq ''}
								<div class="b-plugin__catalogue__items__item__additional__field">
									<span class="b-plugin__catalogue__items__item__additional__field__name">{$field.field}:</span>

                                        <span class="b-plugin__catalogue__items__item__additional__field__name">
                                            {foreach from=$field.value item=value name=name}
												{$value}{if $smarty.foreach.name.last}{else},{/if}
											{/foreach}
                                        </span>
								</div>
							{/if}
						{/foreach}
					</div>




				</div>

			</div>

		{/foreach}

	{/if}

	{* catalogue item *}
	{if $output.type eq 'item'}



	{/if}

{/if}