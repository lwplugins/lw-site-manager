/**
 * WordPress dependencies
 */
import { Button, Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../../components/Callout';
import CodeBlock from '../../components/CodeBlock';
import CopyButton from '../../components/CopyButton';
import Section from '../../components/Section';

const PLACEHOLDER = 'BASE64(user:application_password)';
const ENCODE_COMMAND = "printf '%s' 'username:application password' | base64";

/**
 * How to connect a client: the endpoint, then three steps — application
 * password, Authorization header, .mcp.json snippet. Shown while the server
 * is off too (with a note), so the client can be prepared first.
 *
 * @param {Object} props
 * @param {Object} props.state MCP state.
 */
export default function ConnectionSection( { state } ) {
	const snippet = JSON.stringify( state.snippet, null, 2 );

	return (
		<Section
			title={ __( 'Connect a client', 'lw-site-manager' ) }
			description={ __(
				'Point any MCP client at this site. It connects over HTTP with WordPress application passwords.',
				'lw-site-manager'
			) }
		>
			{ ! state.enabled && (
				<Callout>
					{ __(
						'The server is off. Turn it on above before you connect a client.',
						'lw-site-manager'
					) }
				</Callout>
			) }

			<div className="lw-sm-field">
				<span className="lw-sm-field__label">
					{ __( 'Endpoint', 'lw-site-manager' ) }
				</span>
				<div className="lw-admin-copy">
					<code>{ state.endpoint }</code>
					<CopyButton
						text={ state.endpoint }
						label={ __( 'the endpoint URL', 'lw-site-manager' ) }
					/>
				</div>
			</div>

			<ol className="lw-sm-steps">
				<li>
					<strong>
						{ __(
							'Create an application password',
							'lw-site-manager'
						) }
					</strong>
					<p>
						{ __(
							'Use an administrator account: the connection needs the manage_options capability. Give the password a name you will recognise, such as the client’s name.',
							'lw-site-manager'
						) }
					</p>
					{ state.appPasswords ? (
						<div>
							<Button
								__next40pxDefaultSize
								variant="secondary"
								href={ state.profileUrl }
							>
								{ __( 'Open your profile', 'lw-site-manager' ) }
							</Button>
						</div>
					) : (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'Application passwords are not available on this site. WordPress offers them only over HTTPS or on a local environment, and a plugin or filter can also turn them off.',
								'lw-site-manager'
							) }
						</Notice>
					) }
				</li>
				<li>
					<strong>
						{ __(
							'Build the Authorization header',
							'lw-site-manager'
						) }
					</strong>
					<p>
						{ createInterpolateElement(
							sprintf(
								/* translators: %s: placeholder text in the snippet, e.g. BASE64(user:application_password). */
								__(
									'Base64-encode your user name and the application password joined by a colon, and put the result in place of <code>%s</code>. On macOS or Linux:',
									'lw-site-manager'
								),
								PLACEHOLDER
							),
							{ code: <code /> }
						) }
					</p>
					<code className="lw-sm-command">{ ENCODE_COMMAND }</code>
				</li>
				<li>
					<strong>
						{ __(
							'Paste the snippet into your MCP client',
							'lw-site-manager'
						) }
					</strong>
					<p>
						{ __(
							'For Claude Code, add it to the .mcp.json file in your project folder. Other clients take the same URL and header in their own settings.',
							'lw-site-manager'
						) }
					</p>
					<CodeBlock
						caption=".mcp.json"
						code={ snippet }
						copyLabel={ __(
							'the .mcp.json snippet',
							'lw-site-manager'
						) }
					/>
				</li>
			</ol>
		</Section>
	);
}
