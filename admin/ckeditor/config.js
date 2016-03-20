/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	
	config.language = 'ru';
	
	config.toolbar = 'Full';
	
	config.toolbar_Full =
	[
		{ name: 'document', items : [ 'Source','-','Save','NewPage','DocProps','Preview','Print','-','Templates' ] },
		{ name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
		{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
		{ name: 'insert', items : [ 'Image','Link','Youtube','Table','HorizontalRule','Smiley','SpecialChar','PageBreak' ] },
		'/',
		{ name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
		{ name: 'styles', items : [ /*'Styles','Font',*/'Format','FontSize' ] },
		{ name: 'colors', items : [ 'TextColor','BGColor' ] },
		{ name: 'tools', items : [ 'Maximize', 'ShowBlocks','-'/*,'About'*/ ] }
	];
	
	config.filebrowserBrowseUrl = '../kcfinder/browse.php?type=files';
	config.filebrowserImageBrowseUrl = '../kcfinder/browse.php?type=images';
	config.filebrowserFlashBrowseUrl = '../kcfinder/browse.php?type=flash';
	
	config.filebrowserUploadUrl = '../../kcfinder/upload.php?type=files';
	config.filebrowserImageUploadUrl = '../../kcfinder/upload.php?type=images';
	config.filebrowserFlashUploadUrl = '../../kcfinder/upload.php?type=flash';

// разрешить теги <style>
	config.protectedSource.push(/<(style)[^>]*>.*<\/style>/ig);
// разрешить теги <script>
	config.protectedSource.push(/<(script)[^>]*>.*<\/script>/ig);
// разрешить любые атрибуты в тегах p, div, img
	config.extraAllowedContent = 'p(*)[*]{*};div(*)[*]{*};img(*)[*]{*}';

};
