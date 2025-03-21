function editWorkbook( workbook_slug ) {
	"use strict";

	document.getElementById( "workbook_button" ).disabled = true;
	document.getElementById( "workbook_button" ).innerHTML = magicai_localize.please_wait;
	document.querySelector( '#app-loading-indicator' )?.classList?.remove( 'opacity-0' );
	tinyMCE.get( "workbook_text" ).save();

	var formData = new FormData();
	formData.append( 'workbook_slug', workbook_slug );
	formData.append( 'workbook_text', $( "#workbook_text" ).val() );
	formData.append( 'workbook_title', $( "#workbook_title" ).val() );

	$.ajax( {
		type: "post",
		url: "/dashboard/user/openai/documents/workbook-save",
		data: formData,
		contentType: false,
		processData: false,
		success: function ( data ) {
			toastr.success('Workbook Saved Succesfully')
			document.getElementById( "workbook_button" ).disabled = false;
			document.getElementById( "workbook_button" ).innerHTML = "Save";
			document.querySelector( '#app-loading-indicator' )?.classList?.add( 'opacity-0' );

		},
		error: function ( data ) {
			toastr.error('Workbook  Error')
			document.getElementById( "workbook_button" ).disabled = false;
			document.getElementById( "workbook_button" ).innerHTML = "Save";
			document.querySelector( '#app-loading-indicator' )?.classList?.add( 'opacity-0' );
		}
	} );
	return false;
}


document.addEventListener( "DOMContentLoaded", function () {
	"use strict";

	const tinymceOptions = {
		selector: '.tinymce',
		height: 600,
		menubar: false,
		statusbar: false,
		plugins: [
			'contextmenu', 'advlist', 'link', 'autolink', 'lists', 'code',
		],
		contextmenu: "copy | paste | link image inserttable | cell row column deletetable",
		toolbar: 'styles | forecolor backcolor emoticons | bold italic underline | link | bullist numlist | alignleft aligncenter alignright',
		directionality: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr'
	};
	if ( localStorage.getItem( "tablerTheme" ) === 'dark' ) {
		tinymceOptions.skin = 'oxide-dark';
		tinymceOptions.content_css = 'dark';
	}

	tinyMCE.init( tinymceOptions );

	$( 'body' ).on( 'click', '#workbook_regenerate', () => {
		sendOpenaiGeneratorForm();
	} );
	$( 'body' ).on( 'click', '#workbook_undo', () => {
		tinymce.activeEditor.execCommand( 'Undo' );
	} );
	$( 'body' ).on( 'click', '#workbook_redo', () => {
		tinymce.activeEditor.execCommand( 'Redo' );
	} );
	$( 'body' ).on( 'click', '#workbook_copy', () => {
		const codeOutput = document.querySelector( '#code-output' );
		if ( codeOutput && window.codeRaw ) {
			navigator.clipboard.writeText( window.codeRaw );
			toastr.success('Code copied to clipboard')
			return;
		}
		if ( tinymce?.activeEditor ) {
			tinymce.activeEditor.execCommand( 'selectAll', true );
			const content = tinymce.activeEditor.selection.getContent( { format: 'html' } );
			navigator.clipboard.writeText( content );
			toastr.success('Content copied to clipboard')
			return;
		}
	} );
	$( 'body' ).on( 'click', '.workbook_download', event => {
		const button = event.currentTarget;
		const docType = button.dataset.docType;
		const docName = button.dataset.docName || 'document';

		tinymce.activeEditor.execCommand( 'selectAll', true );
		const content = tinymce.activeEditor.selection.getContent( { format: 'html' } );

		const html = `
<html ${ this.doctype === 'doc' ? 'xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"' : '' }>
<head>
	<meta charset="utf-8" />
	<title>${ docName }</title>
</head>
<body>
	${ content }
</body>
</html>`;

		const url = `${ docType === 'doc' ? 'data:application/vnd.ms-word;charset=utf-8' : 'data:text/plain;charset=utf-8' },${ encodeURIComponent( html ) }`;

		const downloadLink = document.createElement( "a" );
		document.body.appendChild( downloadLink );
		downloadLink.href = url;
		downloadLink.download = `${ docName }.${ docType }`;
		downloadLink.click();

		document.body.removeChild( downloadLink );

	} );
} )
