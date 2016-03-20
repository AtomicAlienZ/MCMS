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
		'/',
		{ name: 'insert', items : [ 'Image','Table','HorizontalRule','Smiley','SpecialChar' ] },
		{ name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','-','JustifyLeft','JustifyCenter','JustifyRight' ] },
		'/',
		{ name: 'styles', items : [ 'Format', 'FontSize' ] },
		{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','-','RemoveFormat' ] },
		{ name: 'colors', items : [ 'TextColor','BGColor' ] },
		{ name: 'tools', items : [ 'Maximize', 'ShowBlocks', ] }
	];
	
	// config.filebrowserBrowseUrl = '../kcfinder/browse.php?type=files';
	// config.filebrowserImageBrowseUrl = '../kcfinder/browse.php?type=images';
	// config.filebrowserFlashBrowseUrl = '../kcfinder/browse.php?type=flash';
	
	config.filebrowserUploadUrl = '/kcfinder/upload.php?type=files';
	config.filebrowserImageUploadUrl = '/kcfinder/upload.php?type=images';
	config.filebrowserFlashUploadUrl = '/kcfinder/upload.php?type=flash';
	
};
