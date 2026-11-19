/**
 * A thin wrapper around WordPress's built-in TinyMCE editor (wp.editor).
 *
 * Props:
 *   id                {string}   - Unique HTML id for the underlying <textarea>. Must be
 *                                  lowercase, no spaces (TinyMCE requirement).
 *   value             {string}   - Controlled value.
 *   onChange          {Function} - Called with the new HTML string on every editor change.
 *   rows              {number}   - Visible rows for the fallback <textarea> (default 10).
 *   extraMediaButtons {Array}    - Buttons to inject into the media-buttons area after init.
 *                                  Each item: { label, className, onClick, dataset }
 *                                  Mirrors what PHP's `media_buttons` action would output.
 *   tinymceExtra      {Object}   - Additional settings merged into the tinymce init object.
 *                                  Use this to set toolbar1/toolbar2, etc.
 *   onEditorSetup     {Function} - Called with the TinyMCE editor instance inside `setup`,
 *                                  before the editor is rendered. Use this to add custom
 *                                  toolbar buttons via editor.addButton().
 */

import { useEffect, useRef } from '@wordpress/element';
import './wp-editor.scss';

/**
 * The current content of an editor, in whichever of its modes is showing.
 *
 * Quicktags buttons write to the textarea without firing an event, so read this
 * rather than the last onChange value when the content is about to be used.
 *
 * @param {string} id       - Editor id.
 * @param {string} fallback - Returned when the editor isn't on the page.
 * @return {string} The content.
 */
export const getEditorContent = ( id, fallback = '' ) => {
	const editor = window.tinymce?.get( id );
	if ( editor && ! editor.isHidden() ) {
		return editor.getContent();
	}
	return document.getElementById( id )?.value ?? fallback;
};

const WpEditor = ( {
	id = 'wcap-wp-editor',
	value = '',
	onChange,
	rows = 10,
	extraMediaButtons = [],
	tinymceExtra = {},
	onEditorSetup = null,
} ) => {
	const initialValue    = useRef( value );
	const extraButtonsRef = useRef( extraMediaButtons );
	const tinymceExtraRef = useRef( tinymceExtra );
	const onEditorSetupRef = useRef( onEditorSetup );
	const lastEmittedRef  = useRef( value );

	const emit = ( content ) => {
		lastEmittedRef.current = content;
		onChange?.( content );
	};

	useEffect( () => {
		if ( ! window.wp?.editor ) return;

		// wp.editor.initialize renders both TinyMCE and QuickTags.
		window.wp.editor.initialize( id, {
			tinymce: {
				wpautop: false,
				forced_root_block: false,
				valid_children: '+body[style]',
				keep_styles: true,
				accessibility_focus: true,
				tabfocus_elements: 'major-publishing-actions',
				media_strict: false,
				paste_remove_styles: false,
				paste_remove_spans: false,
				paste_strip_class_attributes: 'none',
				paste_text_use_dialog: true,
				wpeditimage_disable_captions: true,
				remove_linebreaks: false,
				gecko_spellcheck: false,
				apply_source_formatting: true,
				cleanup: false,
				convert_newlines_to_brs: false,
				fullpage_default_xml_pi: false,
				convert_urls: false,
				remove_redundant_brs: false,
				plugins: 'charmap,colorpicker,fullscreen,hr,image,lists,media,paste,tabfocus,textcolor,wordpress,wpautoresize,wpdialogs,wpeditimage,wpemoji,wpgallery,wplink,wptextpattern',
				entity_encoding: 'raw',
				setup( editor ) {
					// Strip <style> blocks before TinyMCE renders them as "CSS"
					// placeholder widgets in visual mode. They are stored in a
					// closure variable and transparently re-injected on GetContent
					// so the value saved to React always includes them unchanged.
					let extractedStyles = '';

					editor.on( 'BeforeSetContent', ( e ) => {
						// Inserting at the cursor (a merge code, say) isn't a whole document.
						if ( e.selection ) return;

						const blocks = [];
						e.content = e.content.replace( /<style[\s\S]*?<\/style>/gi, ( match ) => {
							blocks.push( match );
							return '';
						} );
						extractedStyles = blocks.join( '' );
					} );

					editor.on( 'GetContent', ( e ) => {
						if ( extractedStyles && ! e.selection ) {
							e.content = extractedStyles + e.content;
						}
					} );

					// Let callers add custom toolbar buttons before the editor renders.
					onEditorSetupRef.current?.( editor );
					// Sync editor content to React state on every keyup / change.
					editor.on( 'change keyup', () => {
						emit( editor.getContent() );
					} );
				},
				...tinymceExtraRef.current,
			},
			quicktags: true,
			mediaButtons: true,
		} );

		// The Text tab edits the textarea, which TinyMCE doesn't see.
		const textarea = document.getElementById( id );
		const onTextInput = () => emit( textarea.value );
		textarea?.addEventListener( 'input', onTextInput );

		// Inject extra buttons into #wp-{id}-media-buttons.
		// wp.editor.initialize() builds that container synchronously, but we
		// retry with back-off in case the DOM write hasn't settled yet.
		let injected = false;

		const inject = () => {
			if ( injected ) return true;

			// The JS wp.editor.initialize() creates the wrap div with an id,
			// but the media-buttons child div only has a class (no id).
			// PHP wp_editor() adds `id="wp-{id}-media-buttons"` but the JS
			// version does not — so we must use the class selector inside the wrap.
			const wrap      = document.getElementById( `wp-${ id }-wrap` );
			const container = wrap?.querySelector( '.wp-media-buttons' );
			if ( ! container ) return false;

			injected = true;

			extraButtonsRef.current.forEach( ( btn ) => {
				const input     = document.createElement( 'input' );
				input.type      = 'button';
				input.className = btn.className || 'button';
				input.value     = btn.label || '';

				if ( btn.dataset ) {
					Object.entries( btn.dataset ).forEach( ( [ key, val ] ) => {
						input.setAttribute( `data-${ key }`, val );
					} );
				}

				if ( btn.onClick ) {
					input.addEventListener( 'click', btn.onClick );
				}

				container.appendChild( input );
			} );

			return true;
		};

		// Try immediately, then fall back to timed retries.
		if ( ! inject() ) {
			const delays = [ 50, 150, 300, 600 ];
			const timers = delays.map( ( ms ) => setTimeout( inject, ms ) );

			return () => {
				timers.forEach( clearTimeout );
				textarea?.removeEventListener( 'input', onTextInput );
				window.wp.editor.remove( id );
			};
		}

		return () => {
			textarea?.removeEventListener( 'input', onTextInput );
			window.wp.editor.remove( id );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ id ] );

	// When the controlled value changes externally, push it into the editor.
	useEffect( () => {
		if ( ! window.wp?.editor ) return;
		if ( value === lastEmittedRef.current ) return;
		lastEmittedRef.current = value;
		const editor = window.tinymce?.get( id );
		if ( editor && ! editor.isHidden() && editor.getContent() !== value ) {
			editor.setContent( value );
		} else if ( ! editor || editor.isHidden() ) {
			const textarea = document.getElementById( id );
			if ( textarea ) textarea.value = value;
		}
	}, [ id, value ] );

	// The <textarea> is the mount point for TinyMCE.
	// defaultValue is used so TinyMCE picks up the initial content on init.
	return (
		<textarea
			id={ id }
			defaultValue={ initialValue.current }
			rows={ rows }
			style={ { width: '100%' } }
		/>
	);
};

export default WpEditor;
