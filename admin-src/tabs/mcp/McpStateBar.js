/**
 * WordPress dependencies
 */
import {
	Card,
	// Core has no stable ConfirmDialog yet (same as the sibling LW admins).
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalConfirmDialog as ConfirmDialog,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, caution, connection } from '@wordpress/icons';

/**
 * MCP state bar: icon, what the state means, the risk, the switch, and three
 * facts (bound domain, who can connect, what a clone does). Turning the
 * server on asks first; turning it off does not.
 *
 * @param {Object} props
 * @param {Object} props.state  MCP state.
 * @param {Object} props.toggle useMcpToggle().
 */
export default function McpStateBar( { state, toggle } ) {
	const [ confirming, setConfirming ] = useState( false );
	const { enabled, domain } = state;

	const onChange = ( value ) => {
		if ( value ) {
			setConfirming( true );
		} else {
			toggle.save( false );
		}
	};

	const facts = [
		{
			label: __( 'Bound to', 'lw-site-manager' ),
			value: <code>{ domain.locked || domain.current }</code>,
		},
		{
			label: __( 'Who can connect', 'lw-site-manager' ),
			value: __(
				'Administrators, with an application password',
				'lw-site-manager'
			),
		},
		{
			label: __( 'On another domain', 'lw-site-manager' ),
			value: __( 'Switches itself off', 'lw-site-manager' ),
		},
	];

	return (
		<Card
			className={ `lw-admin-section lw-sm-state${ enabled ? '' : ' is-off' }` }
		>
			<div className="lw-sm-state__bar">
				<span className="lw-sm-state__icon" aria-hidden="true">
					<Icon icon={ connection } size={ 28 } />
				</span>
				<div className="lw-sm-state__text">
					<strong>
						{ enabled
							? __( 'MCP server is on', 'lw-site-manager' )
							: __( 'MCP server is off', 'lw-site-manager' ) }
					</strong>
					<span>
						{ enabled
							? __(
									'AI agents can connect to this site and use its abilities as tools.',
									'lw-site-manager'
								)
							: __(
									'The MCP endpoint refuses every request. The Abilities REST API is not affected.',
									'lw-site-manager'
								) }
					</span>
					<span className="lw-sm-state__risk">
						<Icon icon={ caution } size={ 18 } />
						{ enabled
							? __(
									'Connected agents can run write and destructive abilities. Keep it on only on sites you control.',
									'lw-site-manager'
								)
							: __(
									'Once on, connected agents can run write and destructive abilities.',
									'lw-site-manager'
								) }
					</span>
				</div>
				<ToggleControl
					__nextHasNoMarginBottom
					label={
						enabled
							? __( 'On', 'lw-site-manager' )
							: __( 'Off', 'lw-site-manager' )
					}
					checked={ enabled }
					disabled={ toggle.isSaving }
					onChange={ onChange }
				/>
			</div>
			<dl className="lw-sm-state__facts">
				{ facts.map( ( fact ) => (
					<div key={ fact.label } className="lw-sm-state__fact">
						<dt>{ fact.label }</dt>
						<dd>{ fact.value }</dd>
					</div>
				) ) }
			</dl>
			<ConfirmDialog
				isOpen={ confirming }
				confirmButtonText={ __( 'Turn on', 'lw-site-manager' ) }
				onConfirm={ () => {
					setConfirming( false );
					toggle.save( true );
				} }
				onCancel={ () => setConfirming( false ) }
			>
				{ __(
					'Turn on the MCP server? Any AI agent with an administrator application password can then run every ability on this site, including ones that change or delete content. The server stays bound to this domain.',
					'lw-site-manager'
				) }
			</ConfirmDialog>
		</Card>
	);
}
