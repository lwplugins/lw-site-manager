/**
 * Main column header: the section title.
 *
 * @param {Object} props
 * @param {string} props.title Current section title.
 */
export default function TopBar( { title } ) {
	return (
		<header className="lw-admin-topbar">
			<h1 className="lw-admin-topbar__title">{ title }</h1>
		</header>
	);
}
