/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';

/**
 * What keeps the server from working as expected (adapter problems, domain
 * change), from the server. The same adapter problems are admin notices on
 * other screens; here they are part of the page.
 *
 * @param {Object} props
 * @param {Array}  props.warnings [ { key, code, message, detail } ].
 */
export default function Warnings( { warnings } ) {
	if ( ! warnings.length ) {
		return null;
	}

	return (
		<div className="lw-sm-warnings">
			{ warnings.map( ( warning ) => (
				<Notice
					key={ warning.key }
					status="warning"
					isDismissible={ false }
				>
					<p>{ warning.message }</p>
					{ warning.detail && (
						<p>
							<code>{ warning.detail }</code>
						</p>
					) }
				</Notice>
			) ) }
		</div>
	);
}
