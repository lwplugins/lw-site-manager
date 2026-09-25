/**
 * Internal dependencies
 */
import CopyButton from './CopyButton';

/**
 * Read-only code with a caption and a Copy button in its header bar.
 *
 * @param {Object} props
 * @param {string} props.caption   File name or language shown in the bar.
 * @param {string} props.code      The code.
 * @param {string} props.copyLabel Screen-reader text of the Copy button.
 */
export default function CodeBlock( { caption, code, copyLabel } ) {
	return (
		<div className="lw-admin-code">
			<div className="lw-admin-code__bar">
				<span>{ caption }</span>
				<CopyButton text={ code } label={ copyLabel } />
			</div>
			<pre>
				<code>{ code }</code>
			</pre>
		</div>
	);
}
