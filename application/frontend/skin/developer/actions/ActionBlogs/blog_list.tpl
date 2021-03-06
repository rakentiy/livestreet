{**
 * Список блогов
 *
 * @styles css/blog.css
 * @scripts <frontend>/common/js/blog.js
 *}


{* Список блогов *}
{if $aBlogs}
	{* Сортировка *}
	{include 'sort.tpl'
			 sSortName     = 'sort-blog-list'
			 aSortList     = [ [ name => 'blog_title',      text => $aLang.sort.by_name ],
							   [ name => 'blog_count_user', text => $aLang.blog.sort.by_users ],
							   [ name => 'blog_rating',     text => $aLang.sort.by_rating ] ]
			 sSortUrl      = $sBlogsRootPage
			 sSortOrder    = $sBlogOrder
			 sSortOrderWay = $sBlogOrderWay}

	{* Список блогов *}
	<ul class="object-list object-list-actions blog-list">
		{foreach $aBlogs as $oBlog}
			<li class="object-list-item">
				{* Аватар *}
				<a href="{$oBlog->getUrlFull()}">
					<img src="{$oBlog->getAvatarPath(100)}" width="100" height="100" alt="{$oBlog->getTitle()|escape}" class="object-list-item-image" />
				</a>
				
				{* Заголовок *}
				<h2 class="object-list-item-title">
					{if $oBlog->getType() == 'close'}
						<i title="{$aLang.blog.private}" class="icon-lock"></i>
					{/if}

					<a href="{$oBlog->getUrlFull()}">{$oBlog->getTitle()|escape}</a>
				</h2>

				{* Описание *}
				<p class="object-list-item-description">{$oBlog->getDescription()|strip_tags|truncate:120}</p>

				{* Информация *}
				<ul class="object-list-item-info">
					<li>{$aLang.blog.users.readers_total}: <strong class="js-blog-users-number" data-blog-id="{$oBlog->getId()}">{$oBlog->getCountUser()}</strong></li>
					<li>{$aLang.vote.rating}: <strong>{$oBlog->getRating()}</strong></li>
					<li>{$aLang.blog.topics_total}: <strong>{$oBlog->getCountTopic()}</strong></li>
				</ul>

				{* Действия *}
				<div class="object-list-item-actions">
					{* Вступить/покинуть блог *}
					{include 'actions/ActionBlog/button_join.tpl'}
				</div>
			</li>
		{/foreach}
	</ul>
{else}
	{* TODO: Fix error message *}
	{if $sBlogsEmptyList}
		{$sBlogsEmptyList}
	{else}
		{include 'alert.tpl' mAlerts=$aLang.blog.alerts.empty sAlertStyle='empty'}
	{/if}
{/if}