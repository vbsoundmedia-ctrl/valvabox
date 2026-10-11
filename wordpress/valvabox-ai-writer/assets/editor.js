/* Valvabox AI Writer: block editor sidebar + block toolbar. No build step needed. */
( function ( wp, cfg ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var C = wp.components;
	var data = wp.data;
	var apiFetch = wp.apiFetch;
	var editorPkg = wp.editor || {};
	var editPost = wp.editPost || {};
	var PluginSidebar = editorPkg.PluginSidebar || editPost.PluginSidebar;
	var PluginSidebarMoreMenuItem = editorPkg.PluginSidebarMoreMenuItem || editPost.PluginSidebarMoreMenuItem;
	var BlockControls = wp.blockEditor.BlockControls;

	var INLINE_BLOCKS = [ 'core/paragraph', 'core/heading', 'core/list-item', 'core/verse' ];
	var REWRITES = [ 'improve', 'grammar', 'shorter', 'longer', 'simplify', 'tone', 'translate', 'summarize' ];
	var A = cfg.actions;

	var icon = el(
		'svg',
		{ width: 24, height: 24, viewBox: '0 0 24 24', 'aria-hidden': true },
		el( 'path', { d: 'M12 2l1.9 5.6L19.5 9.5l-5.6 1.9L12 17l-1.9-5.6L4.5 9.5l5.6-1.9L12 2zm7 11l.95 2.55L22.5 16.5l-2.55.95L19 20l-.95-2.55L15.5 16.5l2.55-.95L19 13zM5 15l.7 1.8 1.8.7-1.8.7L5 20l-.7-1.8-1.8-.7 1.8-.7L5 15z', fill: 'currentColor' } )
	);

	/* ---------- helpers ---------- */

	function editorSel() {
		return data.select( 'core/editor' );
	}
	function blockSel() {
		return data.select( 'core/block-editor' );
	}
	function blockDo() {
		return data.dispatch( 'core/block-editor' );
	}

	function postTitle() {
		return editorSel().getEditedPostAttribute( 'title' ) || '';
	}
	function postContent() {
		return ( editorSel().getEditedPostContent() || '' ).slice( 0, 20000 );
	}

	function notify( type, msg, id ) {
		data.dispatch( 'core/notices' ).createNotice( type, msg, { type: 'snackbar', id: id || 'vbai-notice', isDismissible: true } );
	}

	function errorText( err ) {
		return ( err && err.message ) || __( 'Something went wrong. Please try again.', 'valvabox-ai-writer' );
	}

	function generate( params ) {
		return apiFetch( {
			path: '/vbai/v1/generate',
			method: 'POST',
			data: Object.assign(
				{ title: postTitle(), tone: cfg.tone, language: cfg.language },
				params
			),
		} );
	}

	function markdownToBlocks( md ) {
		var blocks = wp.blocks.pasteHandler( { HTML: '', plainText: md, mode: 'BLOCKS' } );
		return Array.isArray( blocks ) ? blocks : [];
	}

	function insertMarkdown( md, afterClientId ) {
		var blocks = markdownToBlocks( md );
		if ( ! blocks.length ) {
			return;
		}
		if ( afterClientId ) {
			var root = blockSel().getBlockRootClientId( afterClientId ) || undefined;
			var index = blockSel().getBlockIndex( afterClientId ) + 1;
			blockDo().insertBlocks( blocks, index, root );
		} else {
			blockDo().insertBlocks( blocks );
		}
	}

	function copy( text ) {
		var done = function () {
			notify( 'info', __( 'Copied to clipboard.', 'valvabox-ai-writer' ) );
		};
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then( done );
			return;
		}
		var t = document.createElement( 'textarea' );
		t.value = text;
		document.body.appendChild( t );
		t.select();
		document.execCommand( 'copy' );
		t.remove();
		done();
	}

	/** What the user has selected: one inline block, or several/structural blocks. */
	function getSelection() {
		var ids = blockSel().getSelectedBlockClientIds();
		if ( ! ids.length ) {
			return null;
		}
		var blocks = blockSel().getBlocksByClientId( ids ).filter( Boolean );
		if ( 1 === blocks.length && INLINE_BLOCKS.indexOf( blocks[ 0 ].name ) !== -1 ) {
			return { ids: ids, inline: true, text: String( blocks[ 0 ].attributes.content || '' ), name: blocks[ 0 ].name };
		}
		return { ids: ids, inline: false, text: wp.blocks.serialize( blocks ) };
	}

	function applyRewrite( target, text ) {
		if ( target.inline ) {
			blockDo().updateBlockAttributes( target.ids[ 0 ], { content: text } );
		} else {
			var blocks = markdownToBlocks( text );
			if ( blocks.length ) {
				blockDo().replaceBlocks( target.ids, blocks );
			}
		}
	}

	function setTags( names ) {
		var core = data.dispatch( 'core' );
		var current = editorSel().getEditedPostAttribute( 'tags' ) || [];
		return Promise.all(
			names.map( function ( name ) {
				return apiFetch( { path: '/wp/v2/tags?search=' + encodeURIComponent( name ) + '&per_page=20' } ).then( function ( found ) {
					var match = found.filter( function ( t ) {
						return t.name.toLowerCase() === name.toLowerCase();
					} )[ 0 ];
					return match ? match.id : core.saveEntityRecord( 'taxonomy', 'post_tag', { name: name }, { throwOnError: true } ).then( function ( t ) {
						return t.id;
					} );
				} );
			} )
		).then( function ( ids ) {
			var merged = current.concat( ids ).filter( function ( id, i, all ) {
				return all.indexOf( id ) === i;
			} );
			data.dispatch( 'core/editor' ).editPost( { tags: merged } );
		} );
	}

	/* ---------- sidebar ---------- */

	function Sidebar() {
		var s = {
			topic: useState( '' ),
			length: useState( 'medium' ),
			tone: useState( cfg.tone ),
			language: useState( cfg.language ),
			question: useState( '' ),
			useContext: useState( true ),
			busy: useState( '' ),
			result: useState( null ),
			error: useState( '' ),
		};
		function v( k ) {
			return s[ k ][ 0 ];
		}
		function set( k, val ) {
			s[ k ][ 1 ]( val );
		}

		var supportsTags = data.useSelect( function ( select ) {
			return undefined !== select( 'core/editor' ).getEditedPostAttribute( 'tags' );
		}, [] );
		var selCount = data.useSelect( function ( select ) {
			return select( 'core/block-editor' ).getSelectedBlockClientIds().length;
		}, [] );

		function run( action, extra, meta ) {
			set( 'busy', action );
			set( 'error', '' );
			var req = Object.assign( { action: action, tone: v( 'tone' ), language: v( 'language' ) }, extra );
			return generate( req )
				.then( function ( res ) {
					set( 'result', Object.assign( { action: action, req: req, text: res.text, format: res.format, provider: res.provider }, meta || {} ) );
				} )
				.catch( function ( err ) {
					set( 'error', errorText( err ) );
				} )
				.finally( function () {
					set( 'busy', '' );
				} );
		}

		function retry() {
			var r = v( 'result' );
			run( r.action, Object.assign( {}, r.req, { fresh: true } ), { target: r.target } );
		}

		function runOnSelection( action ) {
			var sel = getSelection();
			if ( ! sel ) {
				set( 'error', __( 'Select a paragraph or several blocks in the editor first.', 'valvabox-ai-writer' ) );
				return;
			}
			run( action, { text: sel.text, mode: sel.inline ? 'inline' : 'markdown', context: '' }, { target: sel } );
		}

		function btn( action, onClick, label, primary ) {
			return el(
				C.Button,
				{
					key: action,
					variant: primary ? 'primary' : 'secondary',
					isBusy: v( 'busy' ) === action,
					disabled: !! v( 'busy' ),
					onClick: onClick,
					className: 'vbai-btn',
				},
				label || A[ action ]
			);
		}

		var result = v( 'result' );

		function resultPanel() {
			if ( ! result ) {
				return null;
			}
			var actions = [];
			var lines = result.text.split( '\n' );

			if ( 'titles' === result.action ) {
				return el(
					'div',
					{ className: 'vbai-result' },
					el( 'p', { className: 'vbai-hint' }, __( 'Click a title to use it:', 'valvabox-ai-writer' ) ),
					lines.map( function ( line, i ) {
						return el(
							C.Button,
							{
								key: i,
								variant: 'tertiary',
								className: 'vbai-choice',
								onClick: function () {
									data.dispatch( 'core/editor' ).editPost( { title: line } );
									notify( 'success', __( 'Title updated.', 'valvabox-ai-writer' ) );
								},
							},
							line
						);
					} ),
					resultFooter( [] )
				);
			}

			if ( 'tags' === result.action ) {
				if ( supportsTags ) {
					actions.push(
						el( C.Button, {
							key: 'tags',
							variant: 'primary',
							onClick: function () {
								setTags( lines ).then(
									function () {
										notify( 'success', __( 'Tags added.', 'valvabox-ai-writer' ) );
									},
									function ( err ) {
										notify( 'error', errorText( err ) );
									}
								);
							},
						}, __( 'Add as tags', 'valvabox-ai-writer' ) )
					);
				}
			} else if ( 'meta' === result.action || 'excerpt' === result.action ) {
				actions.push(
					el( C.Button, {
						key: 'excerpt',
						variant: 'primary',
						onClick: function () {
							data.dispatch( 'core/editor' ).editPost( { excerpt: result.text } );
							notify( 'success', __( 'Saved as the post excerpt.', 'valvabox-ai-writer' ) );
						},
					}, __( 'Use as excerpt', 'valvabox-ai-writer' ) )
				);
			} else if ( result.target ) {
				if ( 'summarize' !== result.action ) {
					actions.push(
						el( C.Button, {
							key: 'replace',
							variant: 'primary',
							onClick: function () {
								applyRewrite( result.target, result.text );
								set( 'result', null );
								notify( 'success', __( 'Replaced. Press Ctrl+Z to undo.', 'valvabox-ai-writer' ) );
							},
						}, __( 'Replace selection', 'valvabox-ai-writer' ) )
					);
				}
				actions.push(
					el( C.Button, {
						key: 'below',
						variant: 'summarize' === result.action ? 'primary' : 'secondary',
						onClick: function () {
							var ids = result.target.ids;
							insertMarkdown( result.text, ids[ ids.length - 1 ] );
							set( 'result', null );
						},
					}, __( 'Insert below', 'valvabox-ai-writer' ) )
				);
			} else {
				actions.push(
					el( C.Button, {
						key: 'insert',
						variant: 'primary',
						onClick: function () {
							var ids = blockSel().getSelectedBlockClientIds();
							insertMarkdown( result.text, ids.length ? ids[ ids.length - 1 ] : null );
							set( 'result', null );
							notify( 'success', __( 'Inserted into the post.', 'valvabox-ai-writer' ) );
						},
					}, __( 'Insert into post', 'valvabox-ai-writer' ) )
				);
			}

			var preview = result.target && result.target.inline
				? el( 'div', { className: 'vbai-preview', dangerouslySetInnerHTML: { __html: result.text } } )
				: el( 'div', { className: 'vbai-preview vbai-pre' }, result.text );

			return el( 'div', { className: 'vbai-result' }, preview, resultFooter( actions ) );
		}

		function resultFooter( actions ) {
			return el(
				Fragment,
				null,
				el( 'div', { className: 'vbai-row' },
					actions,
					el( C.Button, { variant: 'secondary', onClick: function () { copy( result.text ); } }, __( 'Copy', 'valvabox-ai-writer' ) ),
					el( C.Button, { variant: 'tertiary', disabled: !! v( 'busy' ), isBusy: !! v( 'busy' ), onClick: retry }, __( 'Try again', 'valvabox-ai-writer' ) ),
					el( C.Button, { variant: 'tertiary', isDestructive: true, onClick: function () { set( 'result', null ); } }, __( 'Discard', 'valvabox-ai-writer' ) )
				),
				el( 'p', { className: 'vbai-hint' }, sprintf( __( 'Written by %s', 'valvabox-ai-writer' ), result.provider ) )
			);
		}

		var toneOptions = cfg.tones.map( function ( t ) {
			return { label: t.charAt( 0 ).toUpperCase() + t.slice( 1 ), value: t };
		} );

		return el(
			Fragment,
			null,
			el( PluginSidebarMoreMenuItem, { target: 'vbai-sidebar', icon: icon }, __( 'AI Writer', 'valvabox-ai-writer' ) ),
			el(
				PluginSidebar,
				{ name: 'vbai-sidebar', title: __( 'AI Writer', 'valvabox-ai-writer' ), icon: icon, className: 'vbai-sidebar' },
				v( 'error' ) && el( C.Notice, { status: 'error', onRemove: function () { set( 'error', '' ); } },
					v( 'error' ),
					cfg.settingsUrl && el( 'a', { href: cfg.settingsUrl, style: { marginLeft: 4 } }, __( 'Settings', 'valvabox-ai-writer' ) )
				),
				result && el( C.PanelBody, { title: __( 'Result', 'valvabox-ai-writer' ), className: 'vbai-result-panel' }, resultPanel() ),

				el(
					C.PanelBody,
					{ title: __( 'Style', 'valvabox-ai-writer' ), initialOpen: false },
					el( C.SelectControl, { label: __( 'Tone', 'valvabox-ai-writer' ), value: v( 'tone' ), options: toneOptions, onChange: function ( x ) { set( 'tone', x ); }, __nextHasNoMarginBottom: true } ),
					el( C.TextControl, { label: __( 'Language', 'valvabox-ai-writer' ), value: v( 'language' ), onChange: function ( x ) { set( 'language', x ); }, help: __( 'Used for new writing and the Translate action.', 'valvabox-ai-writer' ), __nextHasNoMarginBottom: true } )
				),

				el(
					C.PanelBody,
					{ title: __( 'Write', 'valvabox-ai-writer' ), initialOpen: ! result },
					el( C.TextareaControl, {
						label: __( 'Topic or notes', 'valvabox-ai-writer' ),
						placeholder: __( 'e.g. 5 ways independent artists can promote a new single on a small budget', 'valvabox-ai-writer' ),
						value: v( 'topic' ),
						rows: 4,
						onChange: function ( x ) { set( 'topic', x ); },
						__nextHasNoMarginBottom: true,
					} ),
					el( C.SelectControl, {
						label: __( 'Length', 'valvabox-ai-writer' ),
						value: v( 'length' ),
						options: [
							{ label: __( 'Short (~200 words)', 'valvabox-ai-writer' ), value: 'short' },
							{ label: __( 'Medium (~500 words)', 'valvabox-ai-writer' ), value: 'medium' },
							{ label: __( 'Long (~1000 words)', 'valvabox-ai-writer' ), value: 'long' },
						],
						onChange: function ( x ) { set( 'length', x ); },
						__nextHasNoMarginBottom: true,
					} ),
					el( 'div', { className: 'vbai-row' },
						btn( 'write', function () { run( 'write', { text: v( 'topic' ) || postTitle(), length: v( 'length' ), context: '' } ); }, null, true ),
						btn( 'outline', function () { run( 'outline', { text: v( 'topic' ) || postTitle(), context: '' } ); } ),
						btn( 'continue', function () { run( 'continue', { text: '', context: postContent() } ); } )
					)
				),

				el(
					C.PanelBody,
					{ title: __( 'Edit selected text', 'valvabox-ai-writer' ) },
					el( 'p', { className: 'vbai-hint' },
						selCount
							? sprintf( __( '%d block(s) selected.', 'valvabox-ai-writer' ), selCount )
							: __( 'Click a paragraph, or drag to select several blocks.', 'valvabox-ai-writer' )
					),
					el( 'div', { className: 'vbai-row' },
						REWRITES.map( function ( a ) {
							var label = 'tone' === a
								? sprintf( __( 'Make %s', 'valvabox-ai-writer' ), v( 'tone' ) )
								: 'translate' === a
									? sprintf( __( 'Translate to %s', 'valvabox-ai-writer' ), v( 'language' ) )
									: null;
							return btn( a, function () { runOnSelection( a ); }, label );
						} )
					)
				),

				el(
					C.PanelBody,
					{ title: __( 'SEO & publishing', 'valvabox-ai-writer' ), initialOpen: false },
					el( 'p', { className: 'vbai-hint' }, __( 'Uses your title and post content.', 'valvabox-ai-writer' ) ),
					el( 'div', { className: 'vbai-row' },
						[ 'titles', 'meta', 'excerpt' ].concat( supportsTags ? [ 'tags' ] : [] ).map( function ( a ) {
							return btn( a, function () { run( a, { text: '', context: postContent() } ); } );
						} )
					)
				),

				el(
					C.PanelBody,
					{ title: __( 'Ask AI', 'valvabox-ai-writer' ), initialOpen: false },
					el( C.TextareaControl, {
						label: __( 'Your request', 'valvabox-ai-writer' ),
						placeholder: __( 'e.g. Write an FAQ section for this post', 'valvabox-ai-writer' ),
						value: v( 'question' ),
						rows: 3,
						onChange: function ( x ) { set( 'question', x ); },
						__nextHasNoMarginBottom: true,
					} ),
					el( C.CheckboxControl, {
						label: __( 'Include the post as context', 'valvabox-ai-writer' ),
						checked: v( 'useContext' ),
						onChange: function ( x ) { set( 'useContext', x ); },
						__nextHasNoMarginBottom: true,
					} ),
					el( 'div', { className: 'vbai-row' },
						btn( 'ask', function () { run( 'ask', { text: v( 'question' ), context: v( 'useContext' ) ? postContent() : '' } ); }, __( 'Ask', 'valvabox-ai-writer' ), true )
					)
				)
			)
		);
	}

	if ( PluginSidebar ) {
		wp.plugins.registerPlugin( 'vbai-writer', { render: Sidebar, icon: icon } );
	}

	/* ---------- block toolbar ---------- */

	var busyBlocks = {};

	function toolbarRun( action, clientId ) {
		if ( busyBlocks[ clientId ] ) {
			return;
		}
		var block = blockSel().getBlock( clientId );
		if ( ! block ) {
			return;
		}
		var content = String( block.attributes.content || '' );
		if ( 'continue' !== action && ! content.replace( /<[^>]*>/g, '' ).trim() ) {
			notify( 'warning', __( 'This block is empty. Type something first.', 'valvabox-ai-writer' ) );
			return;
		}
		busyBlocks[ clientId ] = true;
		notify( 'info', __( '✦ Writing…', 'valvabox-ai-writer' ), 'vbai-working' );

		var req = 'continue' === action
			? { action: 'continue', text: '', context: postContent() }
			: { action: action, text: content, mode: 'summarize' === action ? 'markdown' : 'inline', context: '' };

		generate( req )
			.then( function ( res ) {
				if ( 'continue' === action || 'summarize' === action ) {
					insertMarkdown( res.text, clientId );
				} else if ( blockSel().getBlock( clientId ) ) {
					blockDo().updateBlockAttributes( clientId, { content: res.text } );
				}
				data.dispatch( 'core/notices' ).removeNotice( 'vbai-working' );
				notify( 'success', __( 'Done. Press Ctrl+Z to undo.', 'valvabox-ai-writer' ) );
			} )
			.catch( function ( err ) {
				data.dispatch( 'core/notices' ).removeNotice( 'vbai-working' );
				notify( 'error', errorText( err ) );
			} )
			.finally( function () {
				delete busyBlocks[ clientId ];
			} );
	}

	var withToolbar = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			if ( ! props.isSelected || INLINE_BLOCKS.indexOf( props.name ) === -1 ) {
				return el( BlockEdit, props );
			}
			var items = [ 'improve', 'grammar', 'shorter', 'longer', 'simplify', 'tone', 'translate', 'summarize', 'continue' ];
			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el(
					BlockControls,
					{ group: 'other' },
					el( C.ToolbarDropdownMenu, {
						icon: icon,
						label: __( 'AI Writer', 'valvabox-ai-writer' ),
						controls: items.map( function ( a ) {
							var title = A[ a ];
							if ( 'tone' === a ) {
								title = sprintf( __( 'Make %s', 'valvabox-ai-writer' ), cfg.tone );
							} else if ( 'translate' === a ) {
								title = sprintf( __( 'Translate to %s', 'valvabox-ai-writer' ), cfg.language );
							}
							return { title: title, onClick: function () { toolbarRun( a, props.clientId ); } };
						} ),
					} )
				)
			);
		};
	}, 'withVbaiToolbar' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'vbai/toolbar', withToolbar );
} )( window.wp, window.vbaiEditor );
